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

namespace Pimcore\Bundle\InstallBundle\SystemConfig;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class ConfigWriter
{
    protected Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function writeDbConfig(array $config = []): void
    {
        if (count($config)) {
            // one key per line instead of inline maps, so the file can be reviewed and edited
            $content = Yaml::dump($config, 10);
            $configFile = PIMCORE_PROJECT_ROOT . '/config/local/database.yaml';
            $this->filesystem->dumpFile($configFile, $content);

            // the file contains the plain DB password: remove the permissions of other users (usually 0644 → 0640),
            // the owner and the group (e.g. of the web server) can still read it
            clearstatcache(true, $configFile);
            $permissions = fileperms($configFile);
            if (false !== $permissions) {
                $this->filesystem->chmod($configFile, $permissions & 0770);
            }
        }
    }
}
