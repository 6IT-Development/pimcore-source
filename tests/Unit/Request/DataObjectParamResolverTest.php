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

namespace Pimcore\Tests\Unit\Request;

use LogicException;
use Pimcore\Bundle\CoreBundle\Request\ParamResolver\DataObjectParamResolver;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Request\Attribute\DataObjectParam;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The route parameters hold objects or invalid IDs, so nothing is loaded from the database.
 */
class DataObjectParamResolverTest extends TestCase
{
    /**
     * B-62: concrete classes, folders and AbstractObject arguments are resolved.
     *
     * @dataProvider provideObjectTypes
     */
    public function testObjectOfTheArgumentTypeIsResolved(string $type, AbstractObject $object): void
    {
        $request = new Request(attributes: ['object' => $object]);

        $this->assertSame([$object], $this->resolve($request, $this->argument($type)));
        $this->assertSame($object, $request->attributes->get('object'));
    }

    public static function provideObjectTypes(): array
    {
        return [
            'concrete class' => [DataObjectParamResolverTestObject::class, self::createObject(true)],
            'folder' => [Folder::class, new Folder()],
            'AbstractObject' => [AbstractObject::class, new Folder()],
        ];
    }

    public function testOtherArgumentTypesAreIgnored(): void
    {
        $request = new Request(attributes: ['object' => '12']);

        $this->assertSame([], $this->resolve($request, $this->argument('string')));
        $this->assertSame([], $this->resolve($request, $this->argument(null)));
        $this->assertSame([], $this->resolve(new Request(), $this->argument(Folder::class)));
    }

    public function testObjectOfAnotherClassIsNotFound(): void
    {
        $request = new Request(attributes: ['object' => new Folder()]);

        $this->expectException(NotFoundHttpException::class);
        $this->resolve($request, $this->argument(DataObjectParamResolverTestObject::class));
    }

    /**
     * B-63: a non-numeric ID is a 404, also for nullable arguments.
     */
    public function testNonNumericIdIsNotFound(): void
    {
        $request = new Request(attributes: ['object' => 'abc']);

        $this->expectException(NotFoundHttpException::class);
        $this->resolve($request, $this->argument(Folder::class, nullable: true));
    }

    public function testEmptyValueResolvesToNullForNullableArguments(): void
    {
        $request = new Request(attributes: ['object' => '']);

        $this->assertSame([null], $this->resolve($request, $this->argument(Folder::class, nullable: true)));
        $this->assertNull($request->attributes->get('object'));
    }

    public function testUnpublishedObjectIsNotFound(): void
    {
        $request = new Request(attributes: ['object' => self::createObject(false)]);

        $this->expectException(NotFoundHttpException::class);
        $this->resolve($request, $this->argument(DataObjectParamResolverTestObject::class));
    }

    public function testUnpublishedObjectIsResolvedWithTheAttributeOption(): void
    {
        $object = self::createObject(false);
        $request = new Request(attributes: ['object' => $object]);

        $argument = $this->argument(DataObjectParamResolverTestObject::class, attribute: new DataObjectParam(unpublished: true));

        $this->assertSame([$object], $this->resolve($request, $argument));
    }

    public function testUnpublishedObjectIsResolvedForAdminPreview(): void
    {
        $object = self::createObject(false);
        $request = new Request(attributes: ['object' => $object]);

        $this->assertSame([$object], $this->resolve($request, $this->argument(DataObjectParamResolverTestObject::class), isAdmin: true));
    }

    /**
     * B-63: the class of the attribute is used instead of the argument type and must be a subtype of it.
     */
    public function testAttributeClassOverridesTheArgumentType(): void
    {
        $object = self::createObject(true);
        $request = new Request(attributes: ['object' => $object]);

        $argument = $this->argument(Concrete::class, attribute: new DataObjectParam(class: DataObjectParamResolverTestObject::class));

        $this->assertSame([$object], $this->resolve($request, $argument));
    }

    public function testAttributeClassThatIsNoSubtypeOfTheArgumentFails(): void
    {
        $request = new Request(attributes: ['object' => new Folder()]);

        $argument = $this->argument(DataObjectParamResolverTestObject::class, attribute: new DataObjectParam(class: Folder::class));

        $this->expectException(LogicException::class);
        $this->resolve($request, $argument);
    }

    private function resolve(Request $request, ArgumentMetadata $argument, bool $isAdmin = false): array
    {
        $resolver = new DataObjectParamResolver(static fn () => $isAdmin);

        return [...$resolver->resolve($request, $argument)];
    }

    private function argument(?string $type, bool $nullable = false, ?DataObjectParam $attribute = null): ArgumentMetadata
    {
        return new ArgumentMetadata('object', $type, false, false, null, $nullable, $attribute ? [$attribute] : []);
    }

    private static function createObject(bool $published): DataObjectParamResolverTestObject
    {
        $object = new DataObjectParamResolverTestObject();
        $object->setPublished($published);

        return $object;
    }
}

class DataObjectParamResolverTestObject extends Concrete
{
}
