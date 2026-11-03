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

namespace Pimcore\Bundle\SeoBundle\EventListener;

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * @internal
 */
class ResponseExceptionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * Length of the http_error_log.uri column in characters
     */
    private const int URI_MAX_LENGTH = 1024;

    public function __construct(
        protected Connection $db,
        protected LoggerInterface $logger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run with high priority before handling real errors
            KernelEvents::EXCEPTION => ['onKernelException', 64],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $exception = $event->getThrowable();

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            return;
        }

        // further checks are only valid for default context
        $request = $event->getRequest();
        if ($this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            if (Pimcore::inDebugMode()) {
                return;
            }

            $exception = $event->getThrowable();

            $statusCode = 500;

            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
            }

            $this->logToHttpErrorLog($event->getRequest(), $statusCode);
        }
    }

    protected function logToHttpErrorLog(Request $request, int $statusCode): void
    {
        // a failing log entry must not replace the response of the actual error
        try {
            // the stored value is what gets hashed, so that every hit of a long URI ends up in the same row
            $uri = mb_substr(mb_scrub($request->getUri(), 'UTF-8'), 0, self::URI_MAX_LENGTH, 'UTF-8');
            $uriHash = hash('sha256', $uri);

            $now = time();

            $this->db->executeStatement(
                'INSERT INTO http_error_log (uri, uri_hash, code, parametersGet, `date`, `count`) VALUES (?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE `count` = `count` + 1, `date` = ?, code = ?',
                [$uri, $uriHash, $statusCode, serialize($request->query->all()), $now, $now, $statusCode]
            );
        } catch (Throwable $e) {
            $this->logger->warning('Could not write to the HTTP error log: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
