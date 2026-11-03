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
use Pimcore\Model\Site;
use Pimcore\Tool;
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

    /**
     * Fields the admin grid may set; all others (id, dates) are maintained by the model.
     */
    private const array EDITABLE_FIELDS = ['text', 'link', 'abbr', 'language', 'casesensitive', 'exactmatch', 'site'];

    /**
     * Length of the varchar columns text, link and abbr.
     */
    private const int MAX_LENGTH = 255;

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

                try {
                    $glossary->setValues($this->getValidatedValues($data));
                    $glossary->save();
                } catch (InvalidArgumentException $e) {
                    return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
                }

                $this->showDocumentPath($glossary);

                return $this->jsonResponse(['data' => $glossary->getObjectVars(), 'success' => true]);
            } elseif ($request->query->getString('xaction') == 'create') {
                // save glossary
                $glossary = new Glossary();

                try {
                    $glossary->setValues($this->getValidatedValues($data, true));
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
                // % and _ in the search term are literal characters, not wildcards
                $list->setCondition('`text` LIKE ?', ['%' . $list->escapeLike($request->request->getString('filter')) . '%']);
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
     * Only the editable fields are taken from the client (no id, creation date, dao, …), and they are validated.
     *
     * The grid sends every field back (writeAllFields), with null for the unused one of link / abbreviation and ''
     * for a cleared one; the setters accept null, and '' is stored as NULL as well.
     *
     * @throws InvalidArgumentException
     */
    private function getValidatedValues(mixed $data, bool $isNew = false): array
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid glossary data.');
        }

        $values = array_intersect_key($data, array_flip(self::EDITABLE_FIELDS));

        foreach (['text', 'link', 'abbr', 'language', 'site'] as $key) {
            if (($values[$key] ?? null) === '') {
                $values[$key] = null;
            }
        }

        foreach (['text', 'link', 'abbr', 'language'] as $key) {
            if (isset($values[$key])) {
                if (!is_scalar($values[$key])) {
                    throw new InvalidArgumentException(sprintf('Invalid value for the glossary field "%s".', $key));
                }
                $values[$key] = (string)$values[$key];
            }
        }

        // the grid shows the path of internal targets, they are stored as document id
        if (isset($values['link']) && $doc = Document::getByPath($values['link'])) {
            $values['link'] = (string)$doc->getId();
        }

        if (($isNew || array_key_exists('text', $values)) && trim($values['text'] ?? '') === '') {
            throw new InvalidArgumentException('The glossary text must not be empty.');
        }

        foreach (['text', 'link', 'abbr'] as $key) {
            if (isset($values[$key]) && mb_strlen($values[$key]) > self::MAX_LENGTH) {
                throw new InvalidArgumentException(sprintf('The glossary field "%s" must not be longer than %d characters.', $key, self::MAX_LENGTH));
            }
        }

        if (isset($values['language'])) {
            $validLanguages = Tool::getValidLanguages();
            if ($validLanguages ? !in_array($values['language'], $validLanguages, true) : strlen($values['language']) > 10) {
                throw new InvalidArgumentException(sprintf('The language "%s" is not a valid language.', $values['language']));
            }
        }

        if (isset($values['site'])) {
            $siteId = filter_var($values['site'], FILTER_VALIDATE_INT);
            if ($siteId === 0) {
                $values['site'] = null;
            } elseif ($siteId === false || $siteId < 0 || !Site::getById($siteId)) {
                throw new InvalidArgumentException('The selected site does not exist.');
            } else {
                $values['site'] = $siteId;
            }
        }

        foreach (['casesensitive', 'exactmatch'] as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = filter_var($values[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $values;
    }
}
