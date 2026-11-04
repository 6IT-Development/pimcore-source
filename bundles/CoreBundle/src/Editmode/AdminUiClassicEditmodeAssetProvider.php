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

namespace Pimcore\Bundle\CoreBundle\Editmode;

use Pimcore;
use Pimcore\Document\Editmode\EditmodeAssetProviderInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Model\Document;
use Pimcore\Tool\Admin;
use Pimcore\Version;
use Symfony\Component\Routing\RouterInterface;

/**
 * Editmode assets of the classic admin UI (PimcoreAdminBundle, pimcore/admin-ui-classic-bundle) and the
 * FOSJsRoutingBundle. Only registered when the PimcoreAdminBundle is loaded (PimcoreCoreExtension), as the routes
 * and /bundles/pimcoreadmin/ paths exist only then.
 *
 * @internal
 */
final readonly class AdminUiClassicEditmodeAssetProvider implements EditmodeAssetProviderInterface
{
    public function __construct(
        private PimcoreBundleManager $bundleManager,
        private RouterInterface      $router
    )
    {
    }

    public function getHeadHtml(Document $document, string $language): string
    {
        $headHtml = "\n\n\n<!-- pimcore editmode -->\n";
        $headHtml .= '<meta name="google" content="notranslate">';
        $headHtml .= "\n\n";

        foreach ($this->getStylesheets() as $stylesheet) {
            $headHtml .= '<link rel="stylesheet" type="text/css" href="' . $this->addCacheBuster($stylesheet) . '" />';
            $headHtml .= "\n";
        }

        $headHtml .= "\n\n";

        foreach ($this->getLibraries() as $script) {
            $headHtml .= '<script src="' . $this->addCacheBuster($script) . '"></script>';
            $headHtml .= "\n";
        }

        // combine the pimcore scripts in non-devmode
        if (Pimcore::disableMinifyJs()) {
            foreach ($this->getScripts() as $script) {
                $headHtml .= '<script src="' . $this->addCacheBuster($script) . '"></script>';
                $headHtml .= "\n";
            }
        } else {
            $scriptContents = '';
            $externalScripts = [];
            foreach ($this->getScripts() as $scriptUrl) {
                // bundle paths can be absolute URLs or generated routes, which can't be read from the web root
                $file = PIMCORE_WEB_ROOT . $scriptUrl;
                if (str_starts_with($scriptUrl, '/') && !str_starts_with($scriptUrl, '//') && is_file($file) && ($content = file_get_contents($file)) !== false) {
                    $scriptContents .= $content . "\n\n\n";
                } else {
                    $externalScripts[] = $scriptUrl;
                }
            }

            $headHtml .= '<script src="' . $this->router->generate('pimcore_admin_misc_scriptproxy', Admin::getMinimizedScriptPath($scriptContents)) . '"></script>' . "\n";

            foreach ($externalScripts as $script) {
                $headHtml .= '<script src="' . $this->addCacheBuster($script) . '"></script>' . "\n";
            }
        }
        $path = $this->router->generate('pimcore_admin_misc_jsontranslationssystem', [
            'language' => $language,
            '_dc' => Version::getRevision(),
        ]);

        $headHtml .= '<script src="' . $path . '"></script>' . "\n";
        $headHtml .= '<script src="' . $this->router->generate('fos_js_routing_js', ['callback' => 'fos.Router.setData']) . '"></script>' . "\n";
        $headHtml .= "\n\n";

        // set var for editable configurations which is filled by Document\Tag::admin()
        $headHtml .= '<script>
            var editableDefinitions = [];
            var pimcore_document_id = ' . $document->getId() . ';
        </script>';

        $headHtml .= "\n\n<!-- /pimcore editmode -->\n\n\n";

        return $headHtml;
    }

    public function getBodyHtml(Document $document): string
    {
        return "\n\n" . '<script src="' . $this->addCacheBuster('/bundles/pimcoreadmin/js/pimcore/document/edit/startup.js') . '"></script>' . "\n\n";
    }

    private function addCacheBuster(string $url): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . '_dc=' . Version::getRevision();
    }

    /**
     * Loaded before the scripts and never combined
     *
     * @return string[]
     */
    private function getLibraries(): array
    {
        $disableMinifyJs = Pimcore::disableMinifyJs();

        return [
            '/bundles/pimcoreadmin/js/pimcore/common.js',
            '/bundles/pimcoreadmin/js/lib/class.js',
            '/bundles/pimcoreadmin/extjs/js/ext-all' . ($disableMinifyJs ? '-debug' : '') . '.js',
        ];
    }

    /**
     * @return string[]
     */
    private function getScripts(): array
    {
        return array_merge(
            [
                '/bundles/fosjsrouting/js/router.js',
                '/bundles/pimcoreadmin/js/pimcore/functions.js',
                '/bundles/pimcoreadmin/js/pimcore/overrides.js',
                '/bundles/pimcoreadmin/js/pimcore/tool/milestoneslider.js',
                '/bundles/pimcoreadmin/js/pimcore/element/tag/imagehotspotmarkereditor.js',
                '/bundles/pimcoreadmin/js/pimcore/element/tag/imagecropper.js',
                '/bundles/pimcoreadmin/js/pimcore/document/edit/helper.js',
                '/bundles/pimcoreadmin/js/pimcore/elementservice.js',
                '/bundles/pimcoreadmin/js/pimcore/document/edit/dnd.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editable.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/block.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/scheduledblock.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/date.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/relation.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/relations.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/checkbox.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/image.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/input.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/link.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/select.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/snippet.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/textarea.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/numeric.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/wysiwyg.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/renderlet.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/table.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/video.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/multiselect.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/area_abstract.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/areablock.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/area.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/pdf.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/embed.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/manager.js',
            ],
            $this->bundleManager->getEditmodeJsPaths()
        );
    }

    /**
     * @return string[]
     */
    private function getStylesheets(): array
    {
        return array_merge(
            [
                '/bundles/pimcoreadmin/css/icons.css',
                '/bundles/pimcoreadmin/extjs/css/PimcoreApp-all_1.css',
                '/bundles/pimcoreadmin/extjs/css/PimcoreApp-all_2.css',
                '/bundles/pimcoreadmin/css/editmode.css',
            ],
            $this->bundleManager->getEditmodeCssPaths()
        );
    }
}
