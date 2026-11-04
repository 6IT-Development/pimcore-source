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

namespace Pimcore\Model\Document\Hardlink;

use Exception;
use Pimcore\Model\Document;
use Pimcore\Tool\Serialize;

class Service
{
    /**
     * @throws Exception
     */
    public static function wrap(Document $doc): Wrapper\WrapperInterface|Wrapper\Hardlink|null
    {
        if ($doc instanceof Document\Hardlink) {
            if ($sourceDoc = $doc->getSourceDocument()) {
                /** @var Document\Hardlink\Wrapper\Hardlink $destDoc */
                $destDoc = self::upperCastDocument($sourceDoc);
                $destDoc->setKey($doc->getKey());
                $destDoc->setPath($doc->getRealPath());
                $destDoc->initDao(get_class($sourceDoc), true);
                $destDoc->setHardLinkSource($doc);
                $destDoc->setSourceDocument($sourceDoc);

                return $destDoc;
            }
        } else {
            $destDoc = self::upperCastDocument($doc);
            $destDoc->initDao(get_class($doc), true);
            $destDoc->setSourceDocument($doc);

            return $destDoc;
        }

        return null;
    }

    /**
     * Wrapper classes by the document class they wrap, most specific first; a custom document type (configured in
     * documents.type_definitions) has no wrapper of its own and is wrapped by the one of the class it extends
     *
     * @var array<class-string<Document>, class-string<Wrapper\WrapperInterface>>
     */
    private const WRAPPER_CLASSES = [
        Document\Email::class => Wrapper\Email::class,
        Document\Page::class => Wrapper\Page::class,
        Document\Snippet::class => Wrapper\Snippet::class,
        Document\Link::class => Wrapper\Link::class,
        Document\Hardlink::class => Wrapper\Hardlink::class,
        Document\Folder::class => Wrapper\Folder::class,
    ];

    /**
     * @return Document\Hardlink\Wrapper\WrapperInterface
     *
     * @throws Exception if no wrapper exists for the document's class
     *
     * @internal
     *
     */
    public static function upperCastDocument(Document $doc): Wrapper\WrapperInterface
    {
        $to_class = 'Pimcore\\Model\\Document\\Hardlink\\Wrapper\\' . ucfirst($doc->getType());
        if (!is_a($to_class, Wrapper\WrapperInterface::class, true)) {
            $to_class = null;
            foreach (self::WRAPPER_CLASSES as $documentClass => $wrapperClass) {
                if ($doc instanceof $documentClass) {
                    $to_class = $wrapperClass;

                    break;
                }
            }
            if ($to_class === null) {
                throw new Exception(sprintf('No hardlink wrapper for document type "%s" (%s)', $doc->getType(), get_class($doc)));
            }
        }

        $old_serialized_prefix = 'O:' . strlen(get_class($doc));
        $old_serialized_prefix .= ':"' . get_class($doc) . '":';

        // unset eventually existing children, because of performance reasons when serializing the document
        $doc->setChildren(null);

        $old_serialized_object = Serialize::serialize($doc);
        $new_serialized_object = 'O:' . strlen($to_class) . ':"' . $to_class . '":';
        $new_serialized_object .= substr($old_serialized_object, strlen($old_serialized_prefix));

        return Serialize::unserialize($new_serialized_object);
    }

    /**
     * @throws Exception
     *
     * @internal
     *
     * this is used to get children below a hardlink by a path
     * for example: the requested path is /de/service/contact but /de/service is a hardlink to /en/service
     * then $hardlink would be /en/service and $path /de/service/contact and this function returns then /en/service/contact
     */
    public static function getChildByPath(Document\Hardlink $hardlink, string $path): Wrapper\WrapperInterface|Wrapper\Hardlink|null
    {
        if ($hardlink->getChildrenFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace('@^' . preg_quote($hardlink->getRealFullPath(), '@') . '@', $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $hardLinkedDocument = Document::getByPath($hardlinkRealPath);
            if ($hardLinkedDocument instanceof Document) {
                $hardLinkedDocument = self::wrap($hardLinkedDocument);
                $hardLinkedDocument->setHardLinkSource($hardlink);

                $_path = $path != '/' ? dirname($path) : $path;
                $_path = str_replace('\\', '/', $_path); // windows patch
                $_path .= $_path != '/' ? '/' : '';

                $hardLinkedDocument->setPath($_path);

                return $hardLinkedDocument;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public static function getNearestChildByPath(Document\Hardlink $hardlink, string $path): Wrapper\WrapperInterface|Wrapper\Hardlink|null
    {
        if ($hardlink->getChildrenFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace('@^' . preg_quote($hardlink->getRealFullPath(), '@') . '@', $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $pathes = [];

            $pathes[] = '/';
            $pathParts = explode('/', $hardlinkRealPath);
            $tmpPathes = [];
            foreach ($pathParts as $pathPart) {
                $tmpPathes[] = $pathPart;
                $t = implode('/', $tmpPathes);
                $pathes[] = $t;
            }

            $pathes = array_reverse($pathes);

            foreach ($pathes as $p) {
                $hardLinkedDocument = Document::getByPath($p);
                if ($hardLinkedDocument instanceof Document) {
                    $hardLinkedDocument = self::wrap($hardLinkedDocument);
                    $hardLinkedDocument->setHardLinkSource($hardlink);

                    $_path = $path != '/' ? dirname($p) : $p;
                    $_path = str_replace('\\', '/', $_path); // windows patch
                    $_path .= $_path != '/' ? '/' : '';

                    $_path = preg_replace('@^' . preg_quote($hardlink->getSourceDocument()->getRealPath(), '@') . '@', $hardlink->getRealPath(), $_path);

                    $hardLinkedDocument->setPath($_path);

                    return $hardLinkedDocument;
                }
            }
        }

        return null;
    }
}
