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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Parameters of the log grid. The time zone is not validated, an unknown one sent by a browser falls back to the
 * server's instead of failing the grid.
 *
 * @internal
 */
final readonly class LogGridRequest
{
    public const string DATE_TIME_PATTERN = '/^(?P<date>\d{4}\-\d{2}\-\d{2})T(?P<time>\d{2}:\d{2}:\d{2})$/';

    private const int MAX_LIMIT = 1000;

    public function __construct(
        #[Assert\PositiveOrZero]
        public int $start = 0,
        #[Assert\Range(min: 1, max: self::MAX_LIMIT)]
        public int $limit = 50,
        public string $sort = '',
        #[Assert\Choice(['', 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])]
        public string $priority = '',
        public string $timezone = '',
        #[Assert\Regex(self::DATE_TIME_PATTERN)]
        public string $fromDate = '',
        #[Assert\Regex(self::DATE_TIME_PATTERN)]
        public string $fromTime = '',
        #[Assert\Regex(self::DATE_TIME_PATTERN)]
        public string $toDate = '',
        #[Assert\Regex(self::DATE_TIME_PATTERN)]
        public string $toTime = '',
        public string $component = '',
        public string $relatedobject = '',
        public string $message = '',
        public ?int $pid = null
    )
    {
    }
}
