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

namespace Pimcore\Bundle\CoreBundle\Command\Traits;

use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * The required `--older-than-days` (`-d`) option of the cleanup commands.
 *
 * @internal
 *
 * @phpstan-require-extends Command
 */
trait OlderThanDaysOptionTrait
{
    /**
     * @param string $subject what the command deletes, e.g. "email logs", used in the option description
     *
     * @return $this
     */
    protected function addOlderThanDaysOption(string $subject): static
    {
        return $this->addOption(
            'older-than-days',
            'd',
            InputOption::VALUE_REQUIRED,
            sprintf('delete %s older than this number of days (required, positive integer)', $subject)
        );
    }

    /**
     * Returns the Unix timestamp `--older-than-days` days ago.
     *
     * @throws InvalidArgumentException if the option is missing or not a positive integer
     */
    protected function getOlderThanDaysTimestamp(InputInterface $input): int
    {
        $days = filter_var($input->getOption('older-than-days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($days === false) {
            throw new InvalidArgumentException('The "--older-than-days" option is required and must be a positive integer.', Command::INVALID);
        }

        return new DateTimeImmutable(sprintf('-%d days', $days))->getTimestamp();
    }
}
