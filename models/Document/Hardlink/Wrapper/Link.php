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

namespace Pimcore\Model\Document\Hardlink\Wrapper;

use Pimcore\Model;
use Pimcore\Model\Document\Hardlink\Dao;

/**
 * @method Dao getDao()
 */
class Link extends Model\Document\Link implements Model\Document\Hardlink\Wrapper\WrapperInterface
{
    use Model\Document\Hardlink\Wrapper;

    public function getHref(): string
    {
        // the hardlink loads its source again, which is gone once the source was deleted
        $hardLink = $this->getHardLinkSource();
        $hardLinkSourceDocument = $hardLink->getSourceDocument();
        if ($hardLinkSourceDocument && $this->getLinktype() === 'internal' && $this->getInternalType() === 'document') {
            $element = $this->getElement();
            if (
                $element instanceof Model\Document &&
                (
                    str_starts_with($element->getRealFullPath(), $hardLinkSourceDocument->getRealFullPath() . '/') ||
                    $hardLinkSourceDocument->getRealFullPath() === $element->getRealFullPath()
                )
            ) {
                // link target is child of hardlink source
                $c = Model\Document\Hardlink\Service::wrap($element);
                if ($c instanceof WrapperInterface) {
                    $c->setHardLinkSource($hardLink);

                    if ($hardLinkSourceDocument->getRealFullpath() == $c->getRealFullPath()) {
                        $c->setPath($hardLink->getPath());
                        $c->setKey($hardLink->getKey());
                    } else {
                        $c->setPath(preg_replace('@^' . preg_quote($hardLinkSourceDocument->getRealFullpath(), '@') . '@', $hardLink->getRealFullpath(), $c->getRealPath()));
                    }

                    $this->setElement($c);
                }
            }
        }

        return parent::getHref();
    }
}
