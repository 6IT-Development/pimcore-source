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

namespace Pimcore\Config;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Helper\StopMessengerWorkersTrait;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class LocationAwareConfigRepository
{
    use StopMessengerWorkersTrait;

    public const string LOCATION_SYMFONY_CONFIG = 'symfony-config';

    public const string LOCATION_SETTINGS_STORE = 'settings-store';

    public const string LOCATION_DISABLED = 'disabled';

    public const string READ_TARGET = 'read_target';

    public const string WRITE_TARGET = 'write_target';

    public const string CONFIG_LOCATION = 'config_location';

    public const string TYPE = 'type';

    public const string OPTIONS = 'options';

    public const string DIRECTORY = 'directory';

    protected array $containerConfig = [];

    protected ?string $settingsStoreScope = null;

    protected ?array $storageConfig = null;

    public function __construct(
        array   $containerConfig,
        ?string $settingsStoreScope,
        array   $storageConfig,
    )
    {
        $this->containerConfig = $containerConfig;
        $this->settingsStoreScope = $settingsStoreScope;
        $this->storageConfig = $storageConfig;
    }

    public function loadConfigByKey(string $key): array
    {
        $data = null;
        $dataSource = null;

        $loadType = $this->getReadTargets()[0] ?? null;
        if ($loadType === null) {
            // try to load from container config
            $data = $this->getDataFromContainerConfig($key, $dataSource);

            // try to load from SettingsStore
            if (!$data) {
                $data = $this->getDataFromSettingsStore($key, $dataSource);
            }
        } else {
            if ($loadType === self::LOCATION_SYMFONY_CONFIG) {
                $data = $this->getDataFromContainerConfig($key, $dataSource);
            } elseif ($loadType === self::LOCATION_SETTINGS_STORE) {
                $data = $this->getDataFromSettingsStore($key, $dataSource);
            }
        }

        return [
            $data,
            $dataSource,
        ];
    }

    private function getDataFromContainerConfig(string $key, ?string &$dataSource): mixed
    {
        if (isset($this->containerConfig[$key])) {
            $dataSource = self::LOCATION_SYMFONY_CONFIG;
        }

        return $this->containerConfig[$key] ?? null;
    }

    private function getDataFromSettingsStore(string $key, ?string &$dataSource): mixed
    {
        $settingsStoreEntryData = null;
        $settingsStoreEntry = SettingsStore::get($key, $this->settingsStoreScope);
        if ($settingsStoreEntry) {
            $settingsStoreEntryData = json_decode($settingsStoreEntry->getData(), true);
            $dataSource = self::LOCATION_SETTINGS_STORE;
        }

        return $settingsStoreEntryData;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function isWriteable(?string $key = null, ?string $dataSource = null): bool
    {
        $key = $key ?: uniqid('pimcore_random_key_', true);
        $writeTarget = $this->getWriteTarget();

        if ($writeTarget === self::LOCATION_SYMFONY_CONFIG && !Pimcore::getKernel()->isDebug()) {
            return false;
        } elseif ($writeTarget === self::LOCATION_DISABLED) {
            return false;
        } elseif ($dataSource === self::LOCATION_SYMFONY_CONFIG && !file_exists($this->getVarConfigFile($key))) {
            return false;
        } elseif ($dataSource && $dataSource !== $writeTarget) {
            return false;
        }

        return true;
    }

    /**
     * @return string Can be either yaml (var/config/...) or "settings-store". defaults to "yaml"
     *
     * @throws Exception
     */
    public function getWriteTarget(): string
    {
        $writeLocation = $this->storageConfig[self::WRITE_TARGET][self::TYPE];

        if (!in_array($writeLocation, [self::LOCATION_SETTINGS_STORE, self::LOCATION_SYMFONY_CONFIG, self::LOCATION_DISABLED])) {
            throw new Exception(sprintf('Invalid write location: %s', $writeLocation));
        }

        return $writeLocation;
    }

    public function getReadTargets(): array
    {
        if (!isset($this->storageConfig[self::READ_TARGET])) {
            return [];
        }

        $readLocation = $this->storageConfig[self::READ_TARGET][self::TYPE];

        if ($readLocation && !in_array($readLocation, [self::LOCATION_SETTINGS_STORE, self::LOCATION_SYMFONY_CONFIG, self::LOCATION_DISABLED])) {
            throw new Exception(sprintf('Invalid read location: %s', $readLocation));
        }

        return $readLocation ? [$readLocation] : [];
    }

    /**
     *
     * @throws Exception
     */
    public function saveConfig(string $key, mixed $data, ?callable $yamlStructureCallback = null): void
    {
        $writeLocation = $this->getWriteTarget();

        if ($writeLocation === self::LOCATION_SYMFONY_CONFIG) {
            if (is_callable($yamlStructureCallback)) {
                $data = $yamlStructureCallback($key, $data);
            }

            $this->writeYaml($key, $data);
        } elseif ($writeLocation === self::LOCATION_SETTINGS_STORE) {
            $settingsStoreData = json_encode($data);
            SettingsStore::set($key, $settingsStoreData, SettingsStore::TYPE_STRING, $this->settingsStoreScope);
        }

        $this->stopMessengerWorkers();
    }

    private function writeYaml(string $key, array $data): void
    {
        $yamlFilename = $this->getVarConfigFile($key);

        $data = $this->escapeContainerParameters($data);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($yamlFilename, Yaml::dump($data, 50));

        $this->invalidateConfigCache();
    }

    /**
     * The YAML files are loaded by the container, which resolves `%parameter%` and `%env(...)%` placeholders in keys and
     * values. Admin-entered data is literal text, so every `%` in a value is escaped (`%%`) - otherwise a value could
     * read secrets from the container or break the container build with an unknown parameter.
     *
     * The container resolves keys but never unescapes them, so keys cannot be escaped: a key containing a placeholder
     * is rejected instead.
     *
     * @throws InvalidArgumentException
     */
    private function escapeContainerParameters(array $data): array
    {
        $escaped = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match_all('/%%|%([^%\s]+)%/', $key, $matches) && array_filter($matches[1]) !== []) {
                throw new InvalidArgumentException(sprintf('Invalid configuration key "%s": "%%...%%" placeholders are not allowed', $key));
            }

            if (is_array($value)) {
                $value = $this->escapeContainerParameters($value);
            } elseif (is_string($value)) {
                $value = str_replace('%', '%%', $value);
            }

            $escaped[$key] = $value;
        }

        return $escaped;
    }

    private function getVarConfigFile(string $key): string
    {
        // the key becomes a file name, it must not leave the config directory
        if ($key === '' || $key === '.' || $key === '..' || strpbrk($key, "/\\\0") !== false) {
            throw new InvalidArgumentException(sprintf('Invalid configuration key "%s"', $key));
        }

        $directory = rtrim($this->storageConfig[self::WRITE_TARGET][self::OPTIONS][self::DIRECTORY], '/\\');

        return $directory . '/' . $key . '.yaml';
    }

    /**
     *
     * @throws Exception
     */
    public function deleteData(string $key, ?string $dataSource): void
    {
        if (!$this->isWriteable($key)) {
            throw new Exception('You are trying to delete a non-writable configuration.');
        }

        if ($dataSource === self::LOCATION_SYMFONY_CONFIG) {
            unlink($this->getVarConfigFile($key));
            $this->invalidateConfigCache();
        } elseif ($dataSource === self::LOCATION_SETTINGS_STORE) {
            SettingsStore::delete($key, $this->settingsStoreScope);
        }

        $this->stopMessengerWorkers();
    }

    public function fetchAllKeys(): array
    {
        return array_unique(array_merge(
            SettingsStore::getIdsByScope($this->settingsStoreScope),
            array_keys($this->containerConfig)
        ));
    }

    /**
     * Returns the keys that loadConfigByKey() can load, following the same read-target rule: no read target means
     * container config and settings store.
     *
     * @throws Exception on an invalid read target
     */
    public function fetchAllKeysByReadTargets(): array
    {
        return match ($this->getReadTargets()[0] ?? null) {
            self::LOCATION_SYMFONY_CONFIG => array_keys($this->containerConfig),
            self::LOCATION_SETTINGS_STORE => array_values(array_unique(SettingsStore::getIdsByScope($this->settingsStoreScope))),
            self::LOCATION_DISABLED => [],
            default => array_values($this->fetchAllKeys()),
        };
    }

    private function invalidateConfigCache(): void
    {
        // invalidate container config cache if debug flag on kernel is set
        $servicesConfig = PIMCORE_PROJECT_ROOT . '/config/services.yaml';
        if (is_file($servicesConfig)) {
            touch($servicesConfig);
        }
    }

    /**
     * Returns the directory whose YAML files make up the Symfony config of a `config_location` key, or null when the
     * key is read from the settings store only. Shared by the kernel (Pimcore core configs) and the bundles.
     *
     * @param array $containerConfig the processed config of the extension, including its `config_location` node
     */
    public static function resolveSymfonyConfigDirectory(array $containerConfig, string $configKey): ?string
    {
        $readTargetConf = $containerConfig[self::CONFIG_LOCATION][$configKey][self::READ_TARGET] ?? null;
        $writeTargetConf = $containerConfig[self::CONFIG_LOCATION][$configKey][self::WRITE_TARGET];

        $configDir = null;
        if ($readTargetConf !== null) {
            if ($readTargetConf[self::TYPE] === self::LOCATION_SETTINGS_STORE ||
                ($readTargetConf[self::TYPE] !== self::LOCATION_SYMFONY_CONFIG && $writeTargetConf[self::TYPE] !== self::LOCATION_SYMFONY_CONFIG)
            ) {
                return null;
            }

            if ($readTargetConf[self::TYPE] === self::LOCATION_SYMFONY_CONFIG) {
                $configDir = $readTargetConf[self::OPTIONS][self::DIRECTORY] ?? null;
            }
        }

        $configDir ??= $writeTargetConf[self::OPTIONS][self::DIRECTORY];

        return rtrim($configDir, '/\\');
    }

    public static function loadSymfonyConfigFiles(ContainerBuilder $container, string $containerKey, string $configKey): void
    {
        $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, $containerKey);

        $configDir = self::resolveSymfonyConfigDirectory($containerConfig, $configKey);
        if ($configDir === null) {
            return;
        }

        $configLoader = new YamlFileLoader(
            $container,
            new FileLocator($configDir)
        );

        foreach (ConfigurationHelper::getSymfonyConfigFiles($configDir) as $config) {
            $configLoader->load($config);
        }
    }
}
