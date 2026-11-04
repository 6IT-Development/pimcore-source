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

namespace Pimcore\Model\Document;

use Pimcore;
use Pimcore\Config;
use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;
use Pimcore\Tool\Text;

/**
 * @method \Pimcore\Model\Document\Page\Dao getDao()
 */
class Page extends PageSnippet
{
    /**
     * Contains the title of the page (meta-title)
     *
     * @internal
     *
     */
    protected string $title = '';

    /**
     * Contains the description of the page (meta-description)
     *
     * @internal
     *
     */
    protected string $description = '';

    protected string $type = 'page';

    /**
     * @internal
     *
     */
    protected ?string $prettyUrl = null;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return Text::removeLineBreaks($this->title);
    }

    public function setDescription(string $description): static
    {
        $this->description = str_replace("\n", ' ', $description);

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getFullPath(bool $force = false): string
    {
        $path = parent::getFullPath($force);

        // do not use pretty url's when in admin, the current document is wrapped by a hardlink or this document isn't in the current site
        if (!Pimcore::inAdmin() && !($this instanceof Hardlink\Wrapper\WrapperInterface) && Frontend::isDocumentInCurrentSite($this)) {
            // check for a pretty url
            $prettyUrl = $this->getPrettyUrl();
            if (!empty($prettyUrl) && strlen($prettyUrl) > 1) {
                return $prettyUrl;
            }
        }

        return $path;
    }

    public function setPrettyUrl(?string $prettyUrl): static
    {
        if (!$prettyUrl) {
            $this->prettyUrl = null;
        } else {
            $this->prettyUrl = '/' . trim($prettyUrl, ' /');
            if (strlen($this->prettyUrl) < 2) {
                $this->prettyUrl = null;
            }
        }

        return $this;
    }

    public function getPrettyUrl(): ?string
    {
        return $this->prettyUrl;
    }

    public function getPreviewImageFilesystemPath(): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/document-page-previews/document-page-screenshot-' . $this->getId() . '@2x.jpg';
    }

    protected function update(array $params = []): void
    {
        $this->validatePrettyUrl();

        parent::update($params);
    }

    /**
     * A pretty URL must be unique within the site of the page and must not be the real path of another document in
     * that site, otherwise the routing resolves one of them arbitrarily. Only checked when the pretty URL or the path
     * of the page changed, so already stored collisions do not block unrelated saves.
     *
     * @throws ValidationException
     */
    private function validatePrettyUrl(): void
    {
        $prettyUrl = $this->getPrettyUrl();
        if ($prettyUrl === null) {
            return;
        }

        $stored = $this->getDao()->getStoredPrettyUrlAndFullPath();
        if ($stored && $stored['prettyUrl'] === $prettyUrl && $stored['fullPath'] === $this->getRealFullPath()) {
            return;
        }

        $site = Frontend::getSiteForDocument($this);

        foreach ($this->getDao()->getOtherPageIdsByPrettyUrl($prettyUrl) as $otherPageId) {
            $otherPage = Document::getById($otherPageId);
            if ($otherPage && Frontend::getSiteIdForDocument($otherPage) === $site?->getId()) {
                throw new ValidationException(sprintf(
                    'Pretty URL "%s" is already used by document %s in the same site',
                    $prettyUrl,
                    $otherPage->getRealFullPath()
                ));
            }
        }

        $sitePath = $site ? rtrim((string) $site->getRootPath(), '/') : '';
        if ($this->getDao()->getOtherDocumentIdByRealFullPath($sitePath . $prettyUrl) !== null) {
            throw new ValidationException(sprintf(
                'Pretty URL "%s" is the path of another document in the same site',
                $prettyUrl
            ));
        }
    }

    public function save(array $parameters = []): static
    {
        $page = parent::save($parameters);

        // Dispatch page preview message, if preview is enabled.
        $documentsConfig = Config::getSystemConfiguration('documents');
        if ($documentsConfig['generate_preview'] ?? false) {
            Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new GeneratePagePreviewMessage($this->getId(), Tool::getHostUrl())
            );
        }

        return $page;
    }
}
