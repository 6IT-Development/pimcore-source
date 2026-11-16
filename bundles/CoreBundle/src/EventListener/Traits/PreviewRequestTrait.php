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

namespace Pimcore\Bundle\CoreBundle\EventListener\Traits;

use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
trait PreviewRequestTrait
{
    protected function isPreviewRequest(Request $request): bool
    {
        if ($request->server->get('HTTP_X_PURPOSE') === 'preview') {
            return true;
        }

        if ($request->server->get('HTTP_PURPOSE') === 'preview') {
            return true;
        }

        // public query parameter: a non-boolean or array value is read as false instead of a 400
        if (filter_var($request->query->all()['pimcore_preview'] ?? false, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE)) {
            return true;
        }

        return false;
    }
}
