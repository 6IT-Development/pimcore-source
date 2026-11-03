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

namespace Pimcore\Bundle\GlossaryBundle\Controller;

use InvalidArgumentException;
use Pimcore\Bundle\AdminBundle\Helper\QueryParams;
use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Extension\Bundle\Exception\AdminClassicBundleNotFoundException;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/settings')]
class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/glossary', name: 'pimcore_bundle_glossary_settings_glossary', methods: [Request::METHOD_POST])]
    public function glossaryAction(Request $request): JsonResponse
    {
        // check glossary permissions
        $this->checkPermission('glossary');

        if ($request->request->has('data')) {
            $data = $this->decodeJson($request->request->getString('data'));

            if ($request->query->getString('xaction') === 'destroy') {
                $glossary = $this->findGlossary($data);
                if (!$glossary) {
                    return $this->notFoundResponse();
                }

                $glossary->delete();

                return $this->jsonResponse(['success' => true, 'data' => []]);
            } elseif ($request->query->getString('xaction') === 'update') {
                // save glossary
                $glossary = $this->findGlossary($data);
                if (!$glossary) {
                    return $this->notFoundResponse();
                }

                // already loaded, and a string id would fail setId()
                unset($data['id']);

                $data = $this->normalizeEmptyValues($data);

                if (!empty($data['link'])) {
                    if ($doc = Document::getByPath($data['link'])) {
                        $data['link'] = $doc->getId();
                    }
                }

                $glossary->setValues($data);

                try {
                    $glossary->save();
                } catch (InvalidArgumentException $e) {
                    return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
                }

                $this->showDocumentPath($glossary);

                return $this->jsonResponse(['data' => $glossary, 'success' => true]);
            } elseif ($request->query->getString('xaction') == 'create') {
                unset($data['id']);

                // save glossary
                $glossary = new Glossary();

                $data = $this->normalizeEmptyValues($data);

                if (!empty($data['link'])) {
                    if ($doc = Document::getByPath($data['link'])) {
                        $data['link'] = $doc->getId();
                    }
                }

                $glossary->setValues($data);

                try {
                    $glossary->save();
                } catch (InvalidArgumentException $e) {
                    return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
                }

                $this->showDocumentPath($glossary);

                return $this->jsonResponse(['data' => $glossary->getObjectVars(), 'success' => true]);
            }
        } else {
            if (!class_exists(QueryParams::class)) {
                throw new AdminClassicBundleNotFoundException('This action requires package "pimcore/admin-ui-classic-bundle" to be installed.');
            }

            $list = new Glossary\Listing();
            $list->setLimit($request->request->getInt('limit', 50));
            $list->setOffset($request->request->getInt('start'));

            $sortingSettings = QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($request->request->has('filter')) {
                $list->setCondition('`text` LIKE ' . $list->quote('%' . $request->request->getString('filter') . '%'));
            }

            $list->load();

            $glossaries = [];
            foreach ($list->getGlossary() as $glossary) {
                $this->showDocumentPath($glossary);

                $glossaries[] = $glossary->getObjectVars();
            }

            return $this->jsonResponse(['data' => $glossaries, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->jsonResponse(['success' => false]);
    }

    /**
     * The entry may have been deleted in another tab or by another user; the grid sends the id as number, other
     * clients may send it as string.
     */
    private function findGlossary(mixed $data): ?Glossary
    {
        $id = is_array($data) ? filter_var($data['id'] ?? null, FILTER_VALIDATE_INT) : false;
        if ($id === false || $id < 1) {
            return null;
        }

        return Glossary::getById($id);
    }

    private function notFoundResponse(): JsonResponse
    {
        return $this->jsonResponse(['success' => false, 'message' => 'Glossary entry not found'], Response::HTTP_NOT_FOUND);
    }

    /**
     * The grid shows and edits the path of the target document instead of its stored id.
     */
    private function showDocumentPath(Glossary $glossary): void
    {
        $documentId = Glossary::getDocumentId($glossary->getLink());
        if ($documentId !== null && $doc = Document::getById($documentId)) {
            $glossary->setLink($doc->getRealFullPath());
        }
    }

    /**
     * The grid sends every field back (writeAllFields), with null for the unused one of link / abbreviation and ''
     * for a cleared one; the setters accept null, and '' is stored as NULL as well.
     */
    private function normalizeEmptyValues(array $data): array
    {
        foreach (['text', 'link', 'abbr'] as $key) {
            if (($data[$key] ?? null) === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
