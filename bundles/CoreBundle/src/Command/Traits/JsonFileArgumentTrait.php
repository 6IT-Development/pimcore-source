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

namespace Pimcore\Bundle\CoreBundle\Command\Traits;

use InvalidArgumentException;
use JsonException;
use Pimcore\Console\AbstractCommand;

/**
 * Reads the JSON export file given in the `path` argument of the definition import commands.
 *
 * @internal
 *
 * @phpstan-require-extends AbstractCommand
 */
trait JsonFileArgumentTrait
{
    /**
     * Returns the path argument after checking that it is a readable file
     *
     * @throws InvalidArgumentException if the path is not a readable file (a directory is rejected as well)
     */
    protected function getPath(): string
    {
        $path = (string) $this->input->getArgument('path');
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exist or is not readable', $path));
        }

        return $path;
    }

    /**
     * Returns the content of the JSON file, after checking that it is valid JSON
     *
     * The content is returned as string, because the import services decode the JSON themselves.
     *
     * @throws InvalidArgumentException if the file can not be read
     * @throws JsonException if the file does not contain valid JSON
     */
    protected function getJson(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new InvalidArgumentException(sprintf('File "%s" could not be read', $path));
        }

        // fail early, before anything is changed, if the file is not valid JSON
        if (!json_validate($content)) {
            throw new JsonException(sprintf('File "%s" does not contain valid JSON: %s', $path, json_last_error_msg()));
        }

        return $content;
    }
}
