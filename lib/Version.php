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

namespace Pimcore;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * @internal
 */
final class Version
{
    public const string PACKAGE_NAME = 'six-it/pimcore-source';

    private const string PLATFORM_VERSION_PACKAGE_NAME = 'six-it/platform-version';

    private const int MAJOR_VERSION = 1;

    public static function getMajorVersion(): int
    {
        return self::MAJOR_VERSION;
    }

    /**
     * null if the package is not installed under PACKAGE_NAME or is replaced/provided by another package
     */
    public static function getVersion(): ?string
    {
        try {
            return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);
        } catch (OutOfBoundsException) {
            return null;
        }
    }

    /**
     * null if the package is not installed under PACKAGE_NAME or was installed without a reference (e.g. path repository)
     */
    public static function getRevision(): ?string
    {
        try {
            return InstalledVersions::getReference(self::PACKAGE_NAME);
        } catch (OutOfBoundsException) {
            return null;
        }
    }

    public static function getPlatformVersion(): ?string
    {
        try {
            return InstalledVersions::getPrettyVersion(self::PLATFORM_VERSION_PACKAGE_NAME);
        } catch (OutOfBoundsException) {
            return null;
        }
    }
}
