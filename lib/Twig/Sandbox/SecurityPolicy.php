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

namespace Pimcore\Twig\Sandbox;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use League\Flysystem\FilesystemOperator;
use Pimcore\Model\Dao\DaoInterface;
use Pimcore\Model\User;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Note: Reused with relaxed checks on object methods and properties: getters (get*, is*, has*), __toString and
 * public properties are allowed on every object that is not an instance of one of the DENIED_CLASSES. Further
 * methods can be allowed per class via $allowedMethods.
 *
 * Represents a security policy which need to be enforced when sandbox mode is enabled.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    /**
     * No method or property may be accessed on instances of these classes, as they give access to services,
     * the database, the request/session or credentials. This also applies to methods allowed via $allowedMethods.
     */
    private const array DENIED_CLASSES = [
        ContainerInterface::class,
        KernelInterface::class,
        AppVariable::class,
        Request::class,
        RequestStack::class,
        SessionInterface::class,
        Environment::class,
        Connection::class,
        ObjectManager::class,
        FilesystemOperator::class,
        DaoInterface::class,
    ];

    /**
     * Methods that are never allowed, even though they are getters (lowercased).
     */
    private const array DENIED_METHODS = [
        User::class => ['getpassword', 'getpasswordrecoverytoken', 'gettwofactorauthentication'],
    ];

    private array $allowedTags;

    private array $allowedFilters;

    private array $allowedFunctions;

    /**
     * @var array<string, string[]> class name => lowercased method names
     */
    private array $allowedMethods;

    /**
     * @param array<string, string[]> $allowedMethods class name => method names
     */
    public function __construct(array $allowedTags = [], array $allowedFilters = [], array $allowedFunctions = [], array $allowedMethods = [])
    {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->allowedFunctions = $allowedFunctions;
        $this->setAllowedMethods($allowedMethods);
    }

    public function setAllowedTags(array $tags): void
    {
        $this->allowedTags = $tags;
    }

    public function setAllowedFilters(array $filters): void
    {
        $this->allowedFilters = $filters;
    }

    public function setAllowedFunctions(array $functions): void
    {
        $this->allowedFunctions = $functions;
    }

    /**
     * @param array<string, string[]> $methods class name => method names
     */
    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = [];
        foreach ($methods as $class => $classMethods) {
            $this->allowedMethods[$class] = array_map(strtolower(...), (array) $classMethods);
        }
    }

    /**
     * @param string[] $tags
     * @param string[] $filters
     * @param string[] $functions
     */
    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (!in_array($tag, $this->allowedTags)) {
                throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (!in_array($filter, $this->allowedFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            //check if a function is allowed or a pimcore twig functions
            if (!in_array($function, $this->allowedFunctions) && !str_starts_with($function, 'pimcore_')) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    /**
     * @param object $obj
     * @param string $method
     */
    public function checkMethodAllowed($obj, $method): void
    {
        $this->checkClassAllowed($obj, $method, true);

        $lcMethod = strtolower($method);

        foreach (self::DENIED_METHODS as $class => $methods) {
            if ($obj instanceof $class && in_array($lcMethod, $methods, true)) {
                throw $this->createMethodError($obj, $method);
            }
        }

        if ($lcMethod === '__tostring' || preg_match('/^(get|is|has)/', $lcMethod)) {
            return;
        }

        foreach ($this->allowedMethods as $class => $methods) {
            if ($obj instanceof $class && in_array($lcMethod, $methods, true)) {
                return;
            }
        }

        throw $this->createMethodError($obj, $method);
    }

    /**
     * @param object $obj
     * @param string $property
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        $this->checkClassAllowed($obj, $property, false);
    }

    private function checkClassAllowed(object $obj, string $member, bool $isMethod): void
    {
        foreach (self::DENIED_CLASSES as $class) {
            if ($obj instanceof $class) {
                throw $isMethod
                    ? $this->createMethodError($obj, $member)
                    : new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $member, $obj::class), $obj::class, $member);
            }
        }
    }

    private function createMethodError(object $obj, string $method): SecurityNotAllowedMethodError
    {
        return new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $obj::class), $obj::class, $method);
    }
}
