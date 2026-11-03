<?php

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

namespace Pimcore\Bundle\InstallBundle\BundleConfig;

use ParseError;
use Pimcore\File;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BundleWriter
{
    /**
     * Adds the bundles which are not enabled yet at the end of the returned array. The rest of the file (comments,
     * conditional entries, the order of the existing bundles) is kept as it is.
     *
     * @param string[] $bundles Bundle classes to add
     * @param string[] $availableBundles Bundle classes which may be added
     *
     * @throws RuntimeException If the file does not return an array or the bundles cannot be added
     */
    public function addBundlesToConfig(array $bundles, array $availableBundles, ?string $bundlesPhpFile = null): void
    {
        $bundlesPhpFile ??= PIMCORE_PROJECT_ROOT . '/config/bundles.php';

        if (!file_exists($bundlesPhpFile)) {
            throw new FileNotFoundException("File \"$bundlesPhpFile\" not found!");
        }

        // get installed bundles, they have to stay in the bundles.php, but won't be added a second time
        $enabledBundles = include $bundlesPhpFile;

        if (!is_array($enabledBundles)) {
            throw new RuntimeException(sprintf('The file "%s" does not return an array of bundles.', $bundlesPhpFile));
        }

        $bundlesToAdd = [];
        foreach ($bundles as $bundle) {
            // check against available bundles since they can change
            if (in_array($bundle, $availableBundles, true)
                && !array_key_exists($bundle, $enabledBundles)
                && !in_array($bundle, $bundlesToAdd, true)) {
                $bundlesToAdd[] = $bundle;
            }
        }

        if ([] === $bundlesToAdd) {
            return;
        }

        $contents = file_get_contents($bundlesPhpFile);
        if (false === $contents) {
            throw new RuntimeException(sprintf('Unable to read the file "%s".', $bundlesPhpFile));
        }

        File::putPhpFile($bundlesPhpFile, $this->insertBundles($contents, $bundlesToAdd, $bundlesPhpFile));
    }

    /**
     * Inserts the bundles before the closing bracket of the returned array (the last "]" of the file)
     *
     * @param string[] $bundles
     */
    private function insertBundles(string $contents, array $bundles, string $bundlesPhpFile): string
    {
        $offset = 0;
        $closingOffset = null;
        $lastEntryEnd = null;
        $lastEntryToken = null;
        $previousEnd = null;
        $previousToken = null;
        $candidate = null;

        foreach (token_get_all($contents) as $token) {
            $text = is_array($token) ? $token[1] : $token;
            $significant = !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);

            if ($significant) {
                // only a "]" which ends the statement closes the returned array
                if (null !== $candidate && ';' === $token) {
                    [$closingOffset, $lastEntryEnd, $lastEntryToken] = $candidate;
                }
                $candidate = ']' === $token ? [$offset, $previousEnd, $previousToken] : null;
            }

            $offset += strlen($text);

            if ($significant) {
                $previousEnd = $offset;
                $previousToken = $text;
            }
        }

        if (null === $closingOffset || null === $lastEntryEnd) {
            throw new RuntimeException(sprintf(
                'Unable to find the returned bundle array in "%s". Please add the following bundles manually: %s',
                $bundlesPhpFile,
                implode(', ', $bundles)
            ));
        }

        $lines = '';
        foreach ($bundles as $bundle) {
            $lines .= sprintf("    %s::class => ['all' => true],\n", ltrim($bundle, '\\'));
        }

        // insert at the beginning of the line of the closing bracket if it is on a line of its own
        $lineStart = strrpos(substr($contents, 0, $closingOffset), "\n");
        if (false !== $lineStart && '' === trim(substr($contents, $lineStart + 1, $closingOffset - $lineStart - 1))) {
            $insertAt = $lineStart + 1;
        } else {
            $insertAt = $closingOffset;
            $lines = "\n" . $lines;
        }

        $newContents = substr($contents, 0, $insertAt) . $lines . substr($contents, $insertAt);

        // the last existing entry needs a trailing comma
        if ('[' !== $lastEntryToken && ',' !== $lastEntryToken) {
            $newContents = substr($newContents, 0, $lastEntryEnd) . ',' . substr($newContents, $lastEntryEnd);
        }

        try {
            token_get_all($newContents, TOKEN_PARSE);
        } catch (ParseError $e) {
            throw new RuntimeException(sprintf(
                'Adding the bundles to "%s" would produce invalid PHP (%s). Please add the following bundles manually: %s',
                $bundlesPhpFile,
                $e->getMessage(),
                implode(', ', $bundles)
            ), 0, $e);
        }

        return $newContents;
    }
}
