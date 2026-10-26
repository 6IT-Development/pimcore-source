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

namespace Pimcore\Bundle\CoreBundle\Command;

use InvalidArgumentException;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\User;
use Pimcore\Tool\Authentication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:user:reset-password',
    description: 'Reset a user\'s password',
    aliases: ['reset-password']
)]
class ResetPasswordCommand extends AbstractCommand
{
    private const MIN_PASSWORD_LENGTH = 10;

    private const MAX_PASSWORD_LENGTH = 4096;

    protected function configure(): void
    {
        $this
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                'Username or ID of user'
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Plaintext password - if not set, script will prompt for the new password (recommended, as passwords passed as option end up in the shell history and the process list)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userArgument = $input->getArgument('user');

        if (is_numeric($userArgument)) {
            $user = User::getById((int)$userArgument);
        } else {
            $user = User::getByName($userArgument);
        }

        if (!$user) {
            $this->writeError('User with name/ID ' . $userArgument . ' could not be found. Exiting');

            return self::INVALID;
        }

        $plainPassword = $input->getOption('password');

        if (is_string($plainPassword) && $plainPassword !== '') {
            $this->io->warning('Passing the password as option exposes it in the shell history and the process list. Omit --password to be prompted for it instead.');

            if ($error = $this->validatePassword($plainPassword)) {
                $this->writeError($error);

                return self::INVALID;
            }
        } elseif (!$input->isInteractive()) {
            $this->writeError('No password given. Pass --password or run the command interactively to be prompted for it.');

            return self::INVALID;
        } else {
            $plainPassword = $this->askForPassword();

            if ($plainPassword === null) {
                $this->writeError('The passwords do not match. Password was not changed.');

                return self::FAILURE;
            }
        }

        $password = Authentication::getPasswordHash($user->getName(), $plainPassword);
        $user->setPassword($password);
        $user->save();

        $this->output->writeln('Password for user ' . $user->getName() . ' reset successfully.');

        return self::SUCCESS;
    }

    /**
     * Prompts for the new password twice, returns null if the inputs do not match.
     */
    protected function askForPassword(): ?string
    {
        $password = $this->io->askHidden('Please enter the new password', function (?string $value): string {
            if ($error = $this->validatePassword((string)$value)) {
                throw new InvalidArgumentException($error);
            }

            return $value;
        });

        $repeated = $this->io->askHidden('Please repeat the new password', fn (?string $value): string => (string)$value);

        return hash_equals($password, $repeated) ? $password : null;
    }

    private function validatePassword(string $password): ?string
    {
        if (trim($password) === '') {
            return 'The password cannot be empty';
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return sprintf('The password must be at least %d characters long', self::MIN_PASSWORD_LENGTH);
        }

        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            return sprintf('The password must not exceed %d characters', self::MAX_PASSWORD_LENGTH);
        }

        return null;
    }
}
