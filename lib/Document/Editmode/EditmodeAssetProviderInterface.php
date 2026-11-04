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

namespace Pimcore\Document\Editmode;

use Pimcore\Model\Document;

/**
 * Provides the assets (scripts, stylesheets) of the admin UI that edits a document in editmode. The EditmodeListener
 * injects them into the page; without a provider, editmode pages are sent as they are.
 */
interface EditmodeAssetProviderInterface
{
    /**
     * HTML inserted before the closing head tag
     *
     * @param string $language language of the admin UI user
     */
    public function getHeadHtml(Document $document, string $language): string;

    /**
     * HTML inserted before the closing body tag, after the editable definitions
     */
    public function getBodyHtml(Document $document): string;
}
