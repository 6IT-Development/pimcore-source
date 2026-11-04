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

namespace Pimcore\Tests\Unit\DependencyInjection;

use InvalidArgumentException;
use Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension;
use Pimcore\Bundle\CoreBundle\EventListener\TranslationDebugListener;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcoreCoreExtensionTest extends TestCase
{
    /**
     * I-56: the extension reads the config without guards, so the tree must provide every key it reads.
     */
    public function testLoadWithEmptyConfigRaisesNoNotices(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.project_dir', PIMCORE_PROJECT_ROOT);

        $notices = [];
        set_error_handler(static function (int $level, string $message) use (&$notices): bool {
            if ($level === E_DEPRECATED || $level === E_USER_DEPRECATED) {
                return false;
            }
            $notices[] = $message;

            return true;
        });

        try {
            new PimcoreCoreExtension()->load([], $container);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $notices);
        $this->assertSame([], $container->getParameter('pimcore.workflow'));
        $this->assertIsBool($container->getParameter('pimcore.documents.areas.autoload'));
        $this->assertFalse($container->hasParameter('router.request_context.host'));
        $this->assertTrue($container->hasDefinition(TranslationDebugListener::class));
    }

    public function testInvalidMainDomainIsRejected(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);

        $this->expectException(InvalidArgumentException::class);
        new PimcoreCoreExtension()->load([['general' => ['domain' => 'not a domain']]], $container);
    }
}
