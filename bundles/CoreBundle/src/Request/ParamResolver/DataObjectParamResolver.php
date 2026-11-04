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

namespace Pimcore\Bundle\CoreBundle\Request\ParamResolver;

use Closure;
use LogicException;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Request\Attribute\DataObjectParam;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves controller arguments typed as a data object (or with #[DataObjectParam], see there for the options) from
 * the route parameter of the same name. It runs for every argument; to resolve only arguments that ask for it, use
 * `#[ValueResolver(DataObjectParamResolver::class)]`.
 *
 * The resolved object (or null) is also written back to the request attributes, as the former `@ParamConverter` did,
 * so templates and listeners that read `app.request.attributes.get('<param>')` keep getting the object.
 *
 * @internal
 */
class DataObjectParamResolver implements ValueResolverInterface
{
    /**
     * @var Closure(Request, Concrete): bool
     */
    private readonly Closure $isElementRequestByAdmin;

    /**
     * @param (Closure(Request, Concrete): bool)|null $isElementRequestByAdmin whether an admin previews the object
     *        (they get unpublished objects), defaults to Tool::isElementRequestByAdmin()
     */
    public function __construct(?Closure $isElementRequestByAdmin = null)
    {
        $this->isElementRequestByAdmin = $isElementRequestByAdmin ?? Tool::isElementRequestByAdmin(...);
    }

    /**
     * @throws NotFoundHttpException When invalid data object ID given
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attribute = $argument->getAttributesOfType(DataObjectParam::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null;

        $class = $attribute?->class ?? $argument->getType();
        if ($attribute?->class !== null) {
            $this->validateAttributeClass($attribute->class, $argument);
        }

        if (null === $class || !is_a($class, AbstractObject::class, true)) {
            return [];
        }

        $param = $argument->getName();
        if (!$request->attributes->has($param)) {
            return [];
        }

        $value = $request->attributes->get($param);

        if (!$value && $argument->isNullable()) {
            $request->attributes->set($param, null);

            return [null];
        }

        if ($value instanceof AbstractObject) {
            $object = $value;
        } else {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            $object = $id !== false ? $class::getById($id, $attribute?->options ?? []) : null;
        }

        if (!$object instanceof $class) {
            throw new NotFoundHttpException(sprintf('Invalid data object ID given for parameter "%s".', $param));
        } elseif (
            // only concrete objects can be unpublished, folders are always published
            $object instanceof Concrete
            && !$object->isPublished()
            && !$attribute?->unpublished
            && !($this->isElementRequestByAdmin)($request, $object)
        ) {
            throw new NotFoundHttpException(sprintf('Data object for parameter "%s" is not published.', $param));
        }

        $request->attributes->set($param, $object);

        return [$object];
    }

    private function validateAttributeClass(string $class, ArgumentMetadata $argument): void
    {
        if (!class_exists($class)) {
            throw new LogicException(sprintf(
                'The class "%s" of #[DataObjectParam] on argument "$%s" does not exist.',
                $class,
                $argument->getName()
            ));
        }

        $type = $argument->getType();
        if ($type !== null && (class_exists($type) || interface_exists($type)) && !is_a($class, $type, true)) {
            throw new LogicException(sprintf(
                'The class "%s" of #[DataObjectParam] on argument "$%s" is not a "%s".',
                $class,
                $argument->getName(),
                $type
            ));
        }
    }
}
