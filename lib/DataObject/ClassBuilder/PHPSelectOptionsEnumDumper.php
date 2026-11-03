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

namespace Pimcore\DataObject\ClassBuilder;

use ParseError;
use Pimcore\Model\DataObject\SelectOptions\Config;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class PHPSelectOptionsEnumDumper implements PHPSelectOptionsEnumDumperInterface
{
    public function __construct(
        protected SelectOptionsEnumBuilderInterface $enumBuilder,
        protected Filesystem                        $filesystem,
    )
    {
    }

    public function dumpPHPEnum(Config $config): void
    {
        $filePath = $config->getPhpClassFile();
        $enum = $this->enumBuilder->buildEnum($config);

        // never replace a working enum with a file that does not parse (every request using it would fail)
        try {
            token_get_all($enum, TOKEN_PARSE);
        } catch (ParseError $e) {
            throw new RuntimeException(sprintf('Generated enum %s is not valid PHP: %s', $config->getEnumName(), $e->getMessage()), 0, $e);
        }

        $this->filesystem->dumpFile($filePath, $enum);
    }
}
