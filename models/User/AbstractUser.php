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

namespace Pimcore\Model\User;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\UserRoleEvents;
use Pimcore\Model;
use ReflectionClass;

/**
 * @method \Pimcore\Model\User\AbstractUser\Dao getDao()
 * @method void setLastLoginDate()
 */
abstract class AbstractUser extends Model\AbstractModel implements AbstractUserInterface
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    protected ?int $parentId = null;

    protected ?string $name = null;

    protected string $type = '';

    public static function getById(int $id): static|null
    {
        if ($id < 0) {
            return null;
        }

        $cacheKey = 'user_' . $id;

        try {
            if (RuntimeCache::isRegistered($cacheKey)) {
                $user = RuntimeCache::get($cacheKey);
            } else {
                $reflectionClass = new ReflectionClass(static::class);
                if ($reflectionClass->isAbstract()) {
                    // resolve the class from the row: the cache entry is shared by all user classes, so it must
                    // never hold e.g. a role row hydrated into a User
                    $type = Db::get()->fetchOne('SELECT `type` FROM users WHERE id = ?', [$id]);
                    $className = is_string($type) ? Service::getClassNameForType($type) : null;
                    if (!$className) {
                        return null;
                    }
                    /** @var AbstractUser $user */
                    $user = new $className();
                } else {
                    $user = new static();
                }
                $user->getDao()->getById($id);

                RuntimeCache::set($cacheKey, $user);
            }
        } catch (Model\Exception\NotFoundException) {
            return null;
        }

        if (!$user || !static::typeMatch($user)) {
            return null;
        }

        return $user;
    }

    public static function create(array $values = []): static
    {
        $user = new static();
        self::checkCreateData($values);
        $user->setValues($values);
        $user->save();

        return $user;
    }

    public static function getByName(string $name): ?static
    {
        try {
            $user = new static();
            $user->getDao()->getByName($name);

            return $user;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * @return $this
     */
    public function setParentId(int $parentId): static
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function save(): static
    {
        // the system user (id 0) is an existing row
        $isUpdate = $this->getId() !== null;

        // before the events: listeners must not act on a user that is then rejected
        $this->validateName($isUpdate);

        if ($isUpdate) {
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::PRE_UPDATE);
        } else {
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::PRE_ADD);
        }

        $this->beginTransaction();

        try {
            if (!$isUpdate) {
                $this->getDao()->create();
            }

            $this->update();

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();

            // the insert was rolled back: a retry must insert again instead of updating a row that does not exist
            if (!$isUpdate) {
                $this->id = null;
            }

            throw $e;
        }

        if ($isUpdate) {
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::POST_UPDATE);
        } else {
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::POST_ADD);
        }

        return $this;
    }

    /**
     * The character rule applies to new and renamed users / roles only: names stored before it (or by an SSO
     * provider) must not block later saves, e.g. on login
     *
     * @throws Exception
     */
    private function validateName(bool $isUpdate): void
    {
        $name = $this->getName();
        if ($name === null || strlen($name) < 2 || strlen($name) > 50) {
            throw new Exception('Name of user/role must be 2 to 50 characters long');
        }

        if ($isUpdate && $this->getDao()->getStoredName() === $name) {
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9\-\.~_@]+$/', $name)) {
            throw new Exception('Invalid name for user/role `' . $name . '` (allowed characters: a-z A-Z 0-9 -.~_@)');
        }
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        if ($this->getId() === null) {
            throw new Exception('Only a saved user or role can be deleted');
        }
        if ($this->getId() < 1) {
            throw new Exception('Deleting the system user is not allowed!');
        }
        $parentUserId = $this->getParentId();

        $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::PRE_DELETE);

        $type = $this->getType();

        // children, role relations and the row go together: a failure must not leave half a subtree deleted or
        // users pointing to a deleted role
        $this->beginTransaction();

        try {
            // delete all children
            $list = ($type === 'role' || $type === 'rolefolder') ? new Model\User\Role\Listing() : new Listing();
            $list->setCondition('parentId = ?', $this->getId());
            foreach ($list as $user) {
                $user->delete();
            }

            // remove user-role relations
            if ($type === 'role') {
                $this->cleanupUserRoleRelations();
            }

            // now delete the current user
            $this->getDao()->delete();

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();

            throw $e;
        }

        $cacheKey = 'user_' . $this->getId();
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::set($cacheKey, null);
        }

        if ($parentUserId && $parentUserId > 0) {
            $parentCacheKey = 'user_' . $parentUserId;
            if (RuntimeCache::isRegistered($parentCacheKey)) {
                RuntimeCache::set($parentCacheKey, null);
            }
        }

        $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::POST_DELETE);
    }

    /**
     * https://github.com/pimcore/pimcore/issues/7085
     *
     * @throws Exception
     */
    private function cleanupUserRoleRelations(): void
    {
        $userRoleListing = new Listing();
        $userRoleListing->setCondition('FIND_IN_SET(' . $this->getId() . ',roles)');
        $userRoleListing = $userRoleListing->load();
        if (count($userRoleListing)) {
            foreach ($userRoleListing as $relatedUser) {
                $userRoles = $relatedUser->getRoles();
                $key = array_search($this->getId(), $userRoles);
                if (false !== $key) {
                    unset($userRoles[$key]);
                    $relatedUser->setRoles($userRoles);
                    $relatedUser->save();
                }
            }
        }
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function update(): void
    {
        $this->getDao()->update();
    }

    /**
     * @internal
     */
    protected static function typeMatch(AbstractUser $user): bool
    {
        $staticType = static::class;
        if ($staticType !== AbstractUser::class && !$user instanceof $staticType) {
            return false;
        }

        return true;
    }
}
