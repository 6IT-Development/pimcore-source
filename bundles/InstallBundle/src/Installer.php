<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license GPLv3 and PCL
 */

namespace Pimcore\Bundle\InstallBundle;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\DriverManager;
use Exception;
use InvalidArgumentException;
use mysqli;
use PDO;
use Pimcore;
use Pimcore\Bundle\ApplicationLoggerBundle\PimcoreApplicationLoggerBundle;
use Pimcore\Bundle\CustomReportsBundle\PimcoreCustomReportsBundle;
use Pimcore\Bundle\GenericExecutionEngineBundle\PimcoreGenericExecutionEngineBundle;
use Pimcore\Bundle\GlossaryBundle\PimcoreGlossaryBundle;
use Pimcore\Bundle\InstallBundle\BundleConfig\BundleWriter;
use Pimcore\Bundle\InstallBundle\Event\BundleSetupEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Bundle\InstallBundle\Sql\SqlStatementSplitter;
use Pimcore\Bundle\InstallBundle\SystemConfig\ConfigWriter;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Bundle\SimpleBackendSearchBundle\PimcoreSimpleBackendSearchBundle;
use Pimcore\Bundle\StaticRoutesBundle\PimcoreStaticRoutesBundle;
use Pimcore\Bundle\UuidBundle\PimcoreUuidBundle;
use Pimcore\Bundle\WordExportBundle\PimcoreWordExportBundle;
use Pimcore\Bundle\XliffBundle\PimcoreXliffBundle;
use Pimcore\Config;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Db\Helper;
use Pimcore\Kernel;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tool\AssetsInstaller;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\Console;
use Pimcore\Tool\Requirements;
use Pimcore\Tool\Requirements\Check;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * @internal
 */
class Installer
{
    public const array RECOMMENDED_BUNDLES = [
        'PimcoreSimpleBackendSearchBundle',
        'PimcoreApplicationLoggerBundle',
        'PimcoreCustomReportsBundle',
        'PimcoreGlossaryBundle',
        'PimcoreSeoBundle',
        'PimcoreStaticRoutesBundle',
        'PimcoreUuidBundle',
        'PimcoreWordExportBundle',
        'PimcoreXliffBundle',
        'PimcoreGenericExecutionEngineBundle',
    ];

    public const array INSTALLABLE_BUNDLES = [
        'PimcoreApplicationLoggerBundle' => PimcoreApplicationLoggerBundle::class,
        'PimcoreCustomReportsBundle' => PimcoreCustomReportsBundle::class,
        'PimcoreGlossaryBundle' => PimcoreGlossaryBundle::class,
        'PimcoreSeoBundle' => PimcoreSeoBundle::class,
        'PimcoreSimpleBackendSearchBundle' => PimcoreSimpleBackendSearchBundle::class,
        'PimcoreStaticRoutesBundle' => PimcoreStaticRoutesBundle::class,
        'PimcoreUuidBundle' => PimcoreUuidBundle::class,
        'PimcoreWordExportBundle' => PimcoreWordExportBundle::class,
        'PimcoreXliffBundle' => PimcoreXliffBundle::class,
        'PimcoreGenericExecutionEngineBundle' => PimcoreGenericExecutionEngineBundle::class,
    ];

    /**
     * Steps which can be selected with --only-steps, in the order they run
     */
    public const array INSTALL_STEPS = [
        'write_database_config',
        'setup_database',
        'install_assets',
        'install_classes',
        'mark_migrations_as_done',
        'install_bundles',
        'clear_cache',
    ];

    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    /**
     * Predefined DB credentials from config, only contains the configured (non-empty) fields
     *
     */
    private array $dbCredentials = [];

    private ?PimcoreStyle $commandLineOutput = null;

    /**
     * When false, skips creating database structure during install
     *
     */
    private bool $createDatabaseStructure = true;

    /**
     * When false, skips importing all database data during install
     *
     */
    private bool $importDatabaseData = true;

    /**
     * When false, skips importing database data dump files (if available) during install
     * only imports needed base data
     *
     */
    private bool $importDatabaseDataDump = true;

    /**
     * skip writing database.yaml file
     *
     */
    private bool $skipDatabaseConfig = false;

    /**
     * When true, the database structure is created even if the target database already contains tables
     * (install.sql drops and re-creates all Pimcore tables)
     *
     */
    private bool $dropExistingTables = false;

    /**
     * Bundles that will be installed
     *
     */
    private array $bundlesToInstall = [];

    /**
     * This bundles might be different to the predefined one, due to the bundle event
     *
     * @var array|string[]
     */
    private array $availableBundles = self::INSTALLABLE_BUNDLES;

    /**
     * This bundles should not be added in the bundles php, e.g. for higher priority
     * Bundles need to be registered in the Kernel then e.g.
     *
     * @var array|string[]
     */
    private array $excludeFromBundlesPhp = [];

    public function setSkipDatabaseConfig(bool $skipDatabaseConfig): void
    {
        $this->skipDatabaseConfig = $skipDatabaseConfig;
    }

    private array $stepEvents = [
        'validate_parameters' => 'Validating input parameters...',
        'check_prerequisites' => 'Checking prerequisites...',
        'start_install' => 'Starting installation...',
        'create_config_files' => 'Creating config files...',
        'boot_kernel' => 'Booting new kernel...',
        'setup_database' => 'Running database setup...',
        'install_assets' => 'Installing assets...',
        'install_classes' => 'Installing classes...',
        'migrations' => 'Marking all migrations as done...',
        'install_bundles' => 'Installing bundles...',
        'complete' => 'Install complete!',
    ];

    private array $runInstallSteps = self::INSTALL_STEPS;

    public function __construct(
        LoggerInterface          $logger,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setDbCredentials(array $dbCredentials = []): void
    {
        // env placeholders are resolved at runtime and may resolve to an empty value
        $this->dbCredentials = array_filter(
            $dbCredentials,
            static fn ($value) => null !== $value && '' !== $value
        );
    }

    public function setCommandLineOutput(PimcoreStyle $commandLineOutput): void
    {
        $this->commandLineOutput = $commandLineOutput;
    }

    public function setDropExistingTables(bool $dropExistingTables): void
    {
        $this->dropExistingTables = $dropExistingTables;
    }

    public function setCreateDatabaseStructure(bool $createDatabaseStructure): void
    {
        $this->createDatabaseStructure = $createDatabaseStructure;
    }

    public function setImportDatabaseData(bool $importDatabaseData): void
    {
        $this->importDatabaseData = $importDatabaseData;
    }

    public function setImportDatabaseDataDump(bool $importDatabaseDataDump): void
    {
        $this->importDatabaseDataDump = $importDatabaseDataDump;
    }

    public function needsDbCredentials(): bool
    {
        return empty($this->dbCredentials);
    }

    /**
     * Whether one of the given fields (user, password, dbname, host, port, unix_socket) is set via config
     */
    public function isDbCredentialConfigured(string ...$fields): bool
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $this->dbCredentials)) {
                return true;
            }
        }

        return false;
    }

    public function setBundlesToInstall(array $bundlesToInstall = [], array $availableBundles = [], array $excludeFromBundlesPhp = []): void
    {
        // map and filter the bundles
        $bundlesToInstall = array_filter(array_map(
            static fn(string $bundle) => $availableBundles[$bundle] ?? null,
            $bundlesToInstall,
        ));
        $this->availableBundles = $availableBundles;
        $this->bundlesToInstall = $bundlesToInstall;
        $this->excludeFromBundlesPhp = $excludeFromBundlesPhp;
    }

    public function dispatchBundleSetupEvent(): BundleSetupEvent
    {
        return $this->eventDispatcher->dispatch(new BundleSetupEvent(self::INSTALLABLE_BUNDLES, self::RECOMMENDED_BUNDLES), InstallEvents::EVENT_BUNDLE_SETUP);
    }

    public function checkPrerequisites(?Connection $db = null): array
    {
        $checks = array_merge(
            Requirements::checkFilesystem(),
            Requirements::checkPhp(),
            null !== $db ? Requirements::checkMysql($db) : []
        );

        return $this->formatPrerequisiteMessages($checks);
    }

    /**
     * @param Check[] $checks
     *
     */
    public function formatPrerequisiteMessages(array $checks, array $filterStates = [Check::STATE_ERROR]): array
    {
        $messages = [];
        foreach ($checks as $check) {
            if (empty($filterStates) || !in_array($check->getState(), $filterStates)) {
                continue;
            }

            if ($check->getLink()) {
                if ('cli' === php_sapi_name()) {
                    $messages[] = sprintf('%s (see %s)', $check->getMessage(), $check->getLink());
                } else {
                    $messages[] = sprintf('<a href="%s" target="_blank">%s</a>', $check->getLink(), $check->getMessage());
                }
            } else {
                $messages[] = $check->getMessage();
            }
        }

        return $messages;
    }

    public function getStepEventCount(): int
    {
        return count($this->stepEvents);
    }

    private function dispatchStepEvent(string $type, ?string $message = null): InstallerStepEvent
    {
        if (!isset($this->stepEvents[$type])) {
            throw new InvalidArgumentException(sprintf('Trying to dispatch unsupported event type "%s"', $type));
        }

        $message = $message ?? $this->stepEvents[$type];
        $step = array_search($type, array_keys($this->stepEvents)) + 1;

        $event = new InstallerStepEvent($type, $message, $step, $this->getStepEventCount());

        $this->eventDispatcher->dispatch($event, InstallEvents::EVENT_NAME_STEP);

        return $event;
    }

    /**
     *
     * @return array Array of errors
     */
    public function install(array $params): array
    {
        $this->dispatchStepEvent('validate_parameters');

        $dbConfig = $this->resolveDbConfig($params);
        $errors = $this->validateDbConfig($dbConfig);

        if (!empty($errors)) {
            return $errors;
        }

        // try to establish a mysql connection
        try {
            $config = new Configuration();

            $db = DriverManager::getConnection($dbConfig, $config);
            $db->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            $db->getDatabasePlatform()->registerDoctrineTypeMapping('bit', 'boolean');

            $this->dispatchStepEvent('check_prerequisites');

            // check all db-requirements before install
            $errors = $this->checkPrerequisites($db);

            if (count($errors) > 0) {
                return $errors;
            }
        } catch (Exception $e) {
            $errors[] = sprintf('Couldn\'t establish connection to MySQL: %s', $e->getMessage());

            return $errors;
        }

        // check username & password
        $adminUser = $params['admin_username'] ?? '';
        $adminPass = $params['admin_password'] ?? '';

        if (strlen($adminPass) < 4 || strlen($adminUser) < 4) {
            $errors[] = 'Username and password should have at least 4 characters';
        }

        if (!empty($errors)) {
            return $errors;
        }

        try {
            $errors = $this->checkExistingTables($db);
        } catch (Exception $e) {
            $errors[] = sprintf('Couldn\'t check the existing tables of the target database: %s', $e->getMessage());
        }

        if (!empty($errors)) {
            return $errors;
        }

        $this->dispatchStepEvent('start_install');

        try {
            return $this->runInstall(
                $dbConfig,
                [
                    'username' => $adminUser,
                    'password' => $adminPass,
                ],
                $db
            );
        } catch (Throwable $e) {
            $this->logger->error((string)$e);

            return [
                $e->getMessage(),
            ];
        }
    }

    /**
     * Creating the database structure drops and re-creates every Pimcore table, so refuse to run it against a
     * database that already contains tables unless dropping them was explicitly requested.
     *
     * @return string[] Array of errors
     */
    public function checkExistingTables(Connection $db): array
    {
        if (!$this->createDatabaseStructure || $this->dropExistingTables
            || !in_array('setup_database', $this->getRunInstallSteps(), true)) {
            return [];
        }

        $tables = $db->fetchFirstColumn(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME'
        );

        if (empty($tables)) {
            return [];
        }

        $database = (string) $db->fetchOne('SELECT DATABASE()');
        $isPimcore = (bool) array_intersect(['users', 'settings_store', 'migration_versions', 'assets', 'documents', 'objects'], $tables);

        return [
            sprintf(
                'The database "%s" is not empty (%d table(s)%s). Installing would drop all Pimcore tables and their data. '
                . 'Use an empty database, skip the structure creation with --skip-database-structure '
                . 'or pass --drop-existing-tables to drop and re-create the tables.',
                $database,
                count($tables),
                $isPimcore ? ', contains an existing Pimcore installation' : ''
            ),
        ];
    }

    /**
     * Fields configured via pimcore_install.parameters.database_credentials take precedence, all other fields are
     * taken from the parameters (CLI options, env vars or prompts)
     */
    public function resolveDbConfig(array $params): array
    {
        $dbConfig = [
            'host' => 'localhost',
            'port' => 3306,
            'driver' => 'pdo_mysql',
        ];

        foreach (['user' => 'mysql_username', 'password' => 'mysql_password', 'dbname' => 'mysql_database'] as $field => $param) {
            if (array_key_exists($param, $params)) {
                $dbConfig[$field] = $params[$param];
            }
        }

        // database configuration host/unix socket
        $hostSocketValue = $params['mysql_host_socket'] ?? null;

        if (null !== $hostSocketValue && '' !== $hostSocketValue) {
            // use value as unix socket if file exists
            if (file_exists($hostSocketValue)) {
                $dbConfig['unix_socket'] = $hostSocketValue;
            } else {
                $dbConfig['host'] = $hostSocketValue;
                $dbConfig['port'] = $params['mysql_port'] ?? $dbConfig['port'];
            }
        }

        $dbConfig = array_merge($dbConfig, $this->dbCredentials);

        $mysqlSslCertPath = $params['mysql_ssl_cert_path'] ?? null;
        if (!empty($mysqlSslCertPath)) {
            $dbConfig['driverOptions'] = [
                PDO::MYSQL_ATTR_SSL_CA => $mysqlSslCertPath,
            ];
        }

        return $dbConfig;
    }

    /**
     * @return string[] Array of errors
     */
    private function validateDbConfig(array $dbConfig): array
    {
        $errors = [];

        foreach (['user' => 'mysql-username', 'dbname' => 'mysql-database'] as $field => $option) {
            if (!isset($dbConfig[$field]) || '' === (string) $dbConfig[$field]) {
                $errors[] = sprintf(
                    'The database credential "%s" is missing. Configure pimcore_install.parameters.database_credentials.%s or pass --%s.',
                    $field,
                    $field,
                    $option
                );
            }
        }

        if (empty($dbConfig['host']) && empty($dbConfig['unix_socket'])) {
            $errors[] = 'The database host or unix socket is missing. Configure pimcore_install.parameters.database_credentials.host '
                . '(or unix_socket) or pass --mysql-host-socket.';
        }

        return $errors;
    }

    private function runInstall(array $dbConfig, array $userCredentials, Connection $db): array
    {
        $errors = [];
        $stepsToRun = $this->getRunInstallSteps();

        if (in_array('write_database_config', $stepsToRun)) {
            $this->dispatchStepEvent('create_config_files');

            unset($dbConfig['driver']);
            unset($dbConfig['wrapperClass']);

            if (isset($dbConfig['driverOptions'])) {
                $dbConfig['options'] = $dbConfig['driverOptions'];
                unset($dbConfig['driverOptions']);
            }

            $dbConfig['mapping_types'] = [
                'enum' => 'string',
                'bit' => 'boolean',
            ];

            $doctrineConfig = [
                'doctrine' => [
                    'dbal' => [
                        'connections' => [
                            'default' => $dbConfig,
                        ],
                    ],
                ],
            ];

            $this->createConfigFiles($doctrineConfig);
        }

        $this->dispatchStepEvent('boot_kernel');

        // resolve environment with default=dev here as we set debug mode to true and want to
        // load the kernel for the same environment as the app.php would do. the kernel booted here
        // will always be in "dev" with the exception of an environment set via env vars
        $environment = Config::getEnvironment();

        $kernel = \App\Kernel::class;

        if (isset($_ENV['PIMCORE_KERNEL_CLASS'])) {
            $kernel = $_ENV['PIMCORE_KERNEL_CLASS'];
        }

        $kernel = new $kernel($environment, true);

        if (in_array('clear_cache', $stepsToRun)) {
            $this->clearKernelCacheDir();
        }

        // the assets installer and the bundle installation (settings store) need the container of the app kernel,
        // the installer kernel has no database connection
        $needsAppKernel = in_array('install_assets', $stepsToRun, true)
            || (!empty($this->bundlesToInstall) && in_array('install_bundles', $stepsToRun, true));

        if ($needsAppKernel) {
            Pimcore::setKernel($kernel);
            $kernel->boot();
        }

        if (in_array('setup_database', $stepsToRun)) {
            $this->dispatchStepEvent('setup_database');

            $errors = $this->setupDatabase($db, $userCredentials, $errors);

            if (!empty($errors)) {
                // the following steps need a complete database
                return $errors;
            }

            if (!$this->skipDatabaseConfig && in_array('write_database_config', $stepsToRun)) {
                // now we're able to write the server version to the database.yaml
                if ($db instanceof Connection) {
                    $connection = $db->getWrappedConnection();
                    if ($connection instanceof ServerInfoAwareConnection) {
                        $writer = new ConfigWriter();
                        $doctrineConfig['doctrine']['dbal']['connections']['default']['server_version'] = $connection->getServerVersion();
                        $writer->writeDbConfig($doctrineConfig);
                    }
                }
            }
        }

        if (in_array('install_assets', $stepsToRun)) {
            $this->dispatchStepEvent('install_assets');

            // missing public assets do not break the installation, continue with the other steps
            if (null !== $error = $this->installAssets($kernel)) {
                $errors[] = $error;
            }
        }

        if (in_array('install_classes', $stepsToRun)) {
            $this->dispatchStepEvent('install_classes');

            if (null !== $error = $this->installClasses()) {
                $errors[] = $error;

                return $errors;
            }
        }

        if (in_array('mark_migrations_as_done', $stepsToRun)) {
            $this->dispatchStepEvent('migrations');

            // without the migrations marked as done, the next migrate run would execute all core migrations again
            if (null !== $error = $this->markMigrationsAsDone()) {
                $errors[] = $error;

                return $errors;
            }
        }

        if (!empty($this->bundlesToInstall) && in_array('install_bundles', $stepsToRun)) {
            $this->dispatchStepEvent('install_bundles');
            $errors = array_merge($errors, $this->installBundles());
        }

        $this->dispatchStepEvent('complete');

        if (in_array('clear_cache', $stepsToRun)) {
            // the container files of a booted kernel are loaded lazily, so it must not be used after its cache is gone
            if ($needsAppKernel) {
                $kernel->shutdown();
            }

            $this->clearKernelCacheDir();
        }

        return $errors;
    }

    /**
     * @return string|null Error message if the command failed
     */
    private function runCommand(array $arguments, string $taskName): ?string
    {
        $io = $this->commandLineOutput;

        try {
            array_splice($arguments, 0, 0, [
                Console::getPhpCli(),
                PIMCORE_PROJECT_ROOT . '/bin/console',
            ]);

            $this->logger->info('Running {command} command', ['command' => $arguments]);

            $process = new Process($arguments);
            $process->setTimeout(0);
            $process->setWorkingDirectory(PIMCORE_PROJECT_ROOT);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $io?->writeln($process->getOutput());
        } catch (ProcessFailedException $e) {
            return $this->handleFailedProcess($e, $taskName);
        }

        return null;
    }

    /**
     * Logs the failure, writes the process output to the error output and returns the error message
     * including the command that has to be run manually
     */
    private function handleFailedProcess(ProcessFailedException $e, string $taskName): string
    {
        $this->logger->error($e->getMessage());

        $process = $e->getProcess();
        $commandLine = str_replace(["'", '\\'], ['', '\\\\'], $process->getCommandLine());

        $stdErr = $this->commandLineOutput?->getErrorStyle();
        if (null !== $stdErr) {
            $stdErr->write($process->getOutput());
            $stdErr->write($process->getErrorOutput());
            $stdErr->note($taskName . ' failed. Please run the following command manually:');
            $stdErr->writeln('  ' . $commandLine);
        }

        $errorOutput = trim($process->getErrorOutput()) ?: trim($process->getOutput());
        $lines = $errorOutput !== '' ? preg_split('/\R/', $errorOutput) : [];
        $lastLine = trim((string) end($lines));

        return sprintf(
            '%s failed%s. Please run the following command manually: %s',
            $taskName,
            $lastLine !== '' ? ' (' . $lastLine . ')' : '',
            $commandLine
        );
    }

    private function markMigrationsAsDone(): ?string
    {
        return $this->runCommand([
            'doctrine:migrations:sync-metadata-storage',
            '-q',
        ], 'Sync migrations metadata storage')
            ?? $this->runCommand([
                'doctrine:migrations:version',
                '--all', '--add', '--prefix=Pimcore\\Bundle\\CoreBundle', '-n', '-q',
            ], 'Marking all migrations as done');
    }

    private function installClasses(): ?string
    {
        return $this->runCommand([
            'pimcore:deployment:classes-rebuild',
            '-c',
        ], 'Installing class definitions');
    }

    /**
     * A failed bundle does not stop the installation of the other bundles
     *
     * @return string[] Array of errors
     */
    private function installBundles(): array
    {
        $errors = [];

        $this->writeBundlesToConfig();
        foreach ($this->bundlesToInstall as $bundle) {
            if (in_array($bundle, $this->availableBundles) && !$this->isBundleInstalled($bundle)) {
                $error = $this->runCommand([
                    'pimcore:bundle:install',
                    $bundle,
                ], 'Installing ' . $bundle);

                if (null !== $error) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    private function writeBundlesToConfig(): void
    {
        // some bundles need to be excluded
        $bundlesToInstall = $this->bundlesToInstall;
        $availableBundles = $this->availableBundles;

        if (!empty($this->excludeFromBundlesPhp)) {
            $bundlesToInstall = array_diff($bundlesToInstall, array_values($this->excludeFromBundlesPhp));
            $availableBundles = array_diff($availableBundles, $this->excludeFromBundlesPhp);
        }
        $writer = new BundleWriter();
        $writer->addBundlesToConfig($bundlesToInstall, $availableBundles);
    }

    /**
     * @return string|null Error message if the assets could not be installed
     */
    private function installAssets(KernelInterface $kernel): ?string
    {
        $this->logger->info('Running {command} command', ['command' => 'assets:install']);

        $assetsInstaller = $kernel->getContainer()->get(AssetsInstaller::class);
        $io = $this->commandLineOutput;

        try {
            $ansi = null !== $io && $io->isDecorated();

            $process = $assetsInstaller->install([
                'ansi' => $ansi,
            ]);

            $io?->writeln($process->getOutput());
        } catch (ProcessFailedException $e) {
            return $this->handleFailedProcess($e, 'Installing assets');
        }

        return null;
    }

    private function createConfigFiles(array $config): void
    {
        $writer = new ConfigWriter();

        if (!$this->skipDatabaseConfig) {
            $writer->writeDbConfig($config);
        }
    }

    private function clearKernelCacheDir(): void
    {
        // we don't use $kernel->getCacheDir() here, since we want to have a fully clean cache dir at this point
        $cacheDir = PIMCORE_SYMFONY_CACHE_DIRECTORY;

        if (!file_exists($cacheDir)) {
            return;
        }

        // see Symfony's cache:clear command
        $oldCacheDir = substr($cacheDir, 0, -1) . '~';

        $filesystem = new Filesystem();

        try {
            if ($filesystem->exists($oldCacheDir)) {
                $filesystem->remove($oldCacheDir);
            }

            $filesystem->rename($cacheDir, $oldCacheDir);
            $filesystem->mkdir($cacheDir);
        } catch (IOException $e) {
            // the cache dir cannot be renamed if it is a mount point (e.g. a docker volume), empty it instead
            $this->logger->info('Could not rename the cache directory, removing its contents instead: {message}', [
                'message' => $e->getMessage(),
            ]);

            $this->emptyDirectory($filesystem, $cacheDir);

            return;
        }

        try {
            $filesystem->remove($oldCacheDir);
        } catch (IOException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function emptyDirectory(Filesystem $filesystem, string $directory): void
    {
        $entries = scandir($directory);
        if (false === $entries) {
            throw new IOException(sprintf('Failed to read the directory "%s".', $directory), 0, null, $directory);
        }

        foreach (array_diff($entries, ['.', '..']) as $entry) {
            $filesystem->remove($directory . '/' . $entry);
        }
    }

    public function setupDatabase(Connection $db, array $userCredentials, array $errors = []): array
    {
        $db->executeQuery('SET FOREIGN_KEY_CHECKS=0;');

        if ($this->createDatabaseStructure) {
            // execute every statement with a separate call, comments are left to the server
            foreach (SqlStatementSplitter::splitFile(__DIR__ . '/../dump/install.sql') as $sql) {
                $db->executeStatement($sql);
            }

            $cacheAdapter = new DoctrineDbalAdapter($db);
            $cacheAdapter->createTable();

            $doctrineTransportConn = new \Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection([], $db);
            $doctrineTransportConn->setup();
        }

        if ($this->importDatabaseData) {
            $dataFiles = $this->getDataFiles();

            try {
                //create a system user with id 0
                $this->insertSystemUser($db);

                if (empty($dataFiles) || !$this->importDatabaseDataDump) {
                    // empty installation
                    $this->insertDatabaseContents($db);
                    $this->createOrUpdateUser($db, $userCredentials);
                } else {
                    foreach ($dataFiles as $dbFile) {
                        $this->logger->info('Importing DB file {dbFile}', ['dbFile' => $dbFile]);
                        $this->insertDatabaseDump($db, $dbFile);
                    }

                    $this->createOrUpdateUser($db, $userCredentials);
                }
            } catch (Exception $e) {
                $this->logger->error((string)$e);
                $errors[] = $e->getMessage();
            }
        }

        $db->executeQuery('SET FOREIGN_KEY_CHECKS=1;');

        // close connections and collection garbage ... in order to avoid too many connections error
        // when installing demos
        if (Pimcore::getKernel() instanceof Kernel) {
            Pimcore::collectGarbage();
        }

        return $errors;
    }

    protected function getDataFiles(): array
    {
        return glob(PIMCORE_PROJECT_ROOT . '/dump/*.sql*');
    }

    protected function createOrUpdateUser(Connection $db, array $config = []): void
    {
        $defaultConfig = [
            'username' => 'admin',
            'password' => bin2hex(random_bytes(16)),
        ];

        $settings = array_replace_recursive($defaultConfig, $config);

        $db->delete('users', ['name' => $settings['username']]);

        $db->insert('users', [
            'parentId' => 0,
            'name' => $settings['username'],
            'password' => Authentication::getPasswordHash($settings['username'], $settings['password']),
            'active' => 1,
            'admin' => 1,
            'type' => 'user',
            'language' => 'en',
        ]);
    }

    /**
     *
     * @throws Exception
     */
    protected function insertDatabaseDump(Connection $db, string $file): void
    {
        if (str_ends_with($file, '.gz')) {
            $file = 'compress.zlib://' . $file;
        }

        // the dump is imported as it is: comments are parsed by the server, stripping them would also
        // change string data that contains comment sequences (CSS, JS, HTML)
        if (str_contains($file, 'atomic')) {
            // atomic dumps are sent as one multi-statement query (e.g. for routines without DELIMITER)
            $dumpFile = file_get_contents($file);
            if ($dumpFile === false) {
                throw new RuntimeException(sprintf('Unable to read the database dump %s', $file));
            }

            $this->executeMultiStatementQuery($db, $dumpFile, $file);

            return;
        }

        // execute the statements one by one: each one is a packet of its own (max_allowed_packet) and
        // the error of every single statement is reported
        foreach (SqlStatementSplitter::splitFile($file) as $number => $sql) {
            try {
                $db->executeStatement($sql);
            } catch (Exception $e) {
                throw new RuntimeException(sprintf(
                    'Importing the database dump %s failed at statement %d (%s): %s',
                    basename($file),
                    $number,
                    preg_replace('/\s+/', ' ', substr($sql, 0, 100)) . (strlen($sql) > 100 ? '...' : ''),
                    $e->getMessage()
                ), 0, $e);
            }
        }
    }

    /**
     * Executes a multi-statement query and checks the result of every statement, not only of the first one
     *
     * @throws Exception
     */
    private function executeMultiStatementQuery(Connection $db, string $sql, string $file): void
    {
        $native = $db->getNativeConnection();
        $number = 1;

        try {
            if ($native instanceof PDO) {
                $statement = $native->query($sql);
                if ($statement === false) {
                    throw new RuntimeException(implode(' ', $native->errorInfo()));
                }

                // fetching the next rowset raises the error of the next statement
                do {
                    $number++;
                } while ($statement->nextRowset());

                if ($statement->errorCode() !== '00000') {
                    throw new RuntimeException(implode(' ', $statement->errorInfo()));
                }

                $statement->closeCursor();
            } elseif ($native instanceof mysqli) {
                if (!$native->multi_query($sql)) {
                    throw new RuntimeException($native->error);
                }

                do {
                    $native->store_result();
                    if (!$native->more_results()) {
                        break;
                    }
                    $number++;
                } while ($native->next_result());

                if ($native->errno) {
                    throw new RuntimeException($native->error);
                }
            } else {
                $db->executeStatement($sql);
            }
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                'Importing the database dump %s failed at statement %d: %s',
                basename($file),
                $number,
                $e->getMessage()
            ), 0, $e);
        }
    }

    protected function insertDatabaseContents(Connection $db): void
    {
        $db->insert('assets', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'folder',
            'filename' => '',
            'path' => '/',
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));
        $db->insert('documents', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'page',
            'key' => '',
            'path' => '/',
            'index' => 999999,
            'published' => 1,
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));
        $db->insert('documents_page', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'controller' => 'App\\Controller\\DefaultController::defaultAction',
            'template' => '',
            'title' => '',
            'description' => '',
        ]));
        $db->insert('objects', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'folder',
            'key' => '',
            'path' => '/',
            'index' => 999999,
            'published' => 1,
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));
        $userPermissions = [
            'assets',
            'classes',
            'selectoptions',
            'clear_cache',
            'clear_fullpage_cache',
            'clear_temp_files',
            'dashboards',
            'document_types',
            'documents',
            'emails',
            'notes_events',
            'objects',
            'predefined_properties',
            'asset_metadata',
            'recyclebin',
            'redirects',
            'seemode',
            'share_configurations',
            'system_settings',
            'tags_configuration',
            'tags_assignment',
            'tags_search',
            'thumbnails',
            'translations',
            'users',
            'website_settings',
            'workflow_details',
            'notifications',
            'notifications_send',
            'sites',
            'objects_sort_method',
            'objectbricks',
            'fieldcollections',
            'quantityValueUnits',
            'classificationstore',
        ];

        foreach ($userPermissions as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }

    protected function insertSystemUser(Connection $db): void
    {
        $db->insert('users', [
            'parentId' => 0,
            'name' => 'system',
            'admin' => 1,
            'active' => 1,
        ]);

        // set the id of the system user to 0
        $db->update('users', ['id' => 0], ['name' => 'system', 'type' => 'user']);
    }

    private function isBundleInstalled(string $bundle): bool
    {
        return null !== SettingsStore::get('BUNDLE_INSTALLED__' . $bundle, 'pimcore');
    }

    public function getRunInstallSteps(): array
    {
        return $this->runInstallSteps;
    }

    /**
     * @param string[] $runInstallSteps
     *
     * @throws InvalidArgumentException If the list is empty or contains unknown steps
     */
    public function setRunInstallSteps(array $runInstallSteps): void
    {
        $runInstallSteps = array_values(array_unique($runInstallSteps));

        if ([] === $runInstallSteps) {
            throw new InvalidArgumentException(sprintf(
                'No install step given. Available steps: %s',
                implode(', ', self::INSTALL_STEPS)
            ));
        }

        $unknownSteps = array_diff($runInstallSteps, self::INSTALL_STEPS);
        if ([] !== $unknownSteps) {
            throw new InvalidArgumentException(sprintf(
                'Unknown install step(s): %s. Available steps: %s',
                implode(', ', array_map(static fn ($step) => '"' . $step . '"', $unknownSteps)),
                implode(', ', self::INSTALL_STEPS)
            ));
        }

        $this->runInstallSteps = $runInstallSteps;
    }
}
