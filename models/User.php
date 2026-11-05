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

namespace Pimcore\Model;

use Pimcore\Config;
use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Image;
use Pimcore\Logger;
use Pimcore\Model\User\Role;
use Pimcore\Tool;

/**
 * @method User\Dao getDao()
 */
final class User extends User\UserRole implements UserInterface
{
    use TemporaryFileHelperTrait;

    protected string $type = 'user';

    protected ?string $password = null;

    protected ?string $passwordRecoveryToken = null;

    protected ?string $firstname = null;

    protected ?string $lastname = null;

    protected ?string $email = null;

    protected string $language = 'en';

    protected ?string $datetimeLocale = null;

    protected bool $admin = false;

    protected bool $active = true;

    /**
     * @var int[]
     */
    protected array $roles = [];

    protected bool $welcomescreen = false;

    protected bool $closeWarning = true;

    protected bool $memorizeTabs = true;

    protected bool $allowDirtyClose = false;

    protected ?string $contentLanguages = '';

    protected ?string $activePerspective = null;

    protected ?int $lastLogin = null;

    protected ?string $keyBindings = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $twoFactorAuthentication = null;

    /**
     * OIDC Provider from pimcore/openid-connect
     */
    protected ?string $provider = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Expects a hash from Authentication::getPasswordHash(); null removes the password, which disables the login.
     * '' keeps the current password: forms send it for an empty password field.
     */
    public function setPassword(?string $password): static
    {
        if ($password === '') {
            return $this;
        }

        if ($password === null) {
            $this->password = null;

            return $this;
        }

        if (password_get_info($password)['algo'] === null) {
            // not rejected: a user row with such a value must stay loadable; the login fails for it
            Logger::warning(sprintf('The password of user %s is not a password hash, use %s::getPasswordHash()', $this->getName(), Tool\Authentication::class));
        }

        $this->password = $password;

        return $this;
    }

    /**
     * @internal
     */
    public function getPasswordRecoveryToken(): ?string
    {
        return $this->passwordRecoveryToken;
    }

    /**
     * @return $this
     * @internal
     *
     */
    public function setPasswordRecoveryToken(?string $passwordRecoveryToken): User
    {
        $this->passwordRecoveryToken = $passwordRecoveryToken;

        return $this;
    }

    /**
     * Alias for getName()
     *
     */
    public function getUsername(): ?string
    {
        return $this->getName();
    }

    /**
     * @return $this
     */
    public function setUsername(string $username): static
    {
        $this->setName($username);

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @return $this
     */
    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @return $this
     */
    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->getFirstname() . ' ' . $this->getLastname());
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return $this
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return $this
     */
    public function setLanguage(string $language): static
    {
        if ($language) {
            $this->language = $language;
        }

        return $this;
    }

    /**
     * @see getAdmin()
     *
     */
    public function isAdmin(): bool
    {
        return $this->getAdmin();
    }

    public function getAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * @return $this
     */
    public function setAdmin(bool $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @return $this
     */
    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->getActive();
    }

    public function isAllowed(string $key, string $type = 'permission'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($type == 'permission') {
            if (!$this->getPermission($key)) {
                // check roles
                foreach ($this->getRoleObjects() as $role) {
                    if ($role->getPermission($key)) {
                        return true;
                    }
                }
            }

            return $this->getPermission($key);
        } elseif ($type == 'class') {
            $classes = $this->getClasses();
            foreach ($this->getRoleObjects() as $role) {
                $classes = array_merge($classes, $role->getClasses());
            }

            if (!empty($classes)) {
                return in_array($key, $classes);
            } else {
                return true;
            }
        } elseif ($type == 'docType') {
            $docTypes = $this->getDocTypes();
            foreach ($this->getRoleObjects() as $role) {
                $docTypes = array_merge($docTypes, $role->getDocTypes());
            }

            if (!empty($docTypes)) {
                return in_array($key, $docTypes);
            } else {
                return true;
            }
        } elseif ($type == 'perspective') {
            //returns true if required perspective is allowed to use by the user
            return in_array($key, $this->getMergedPerspectives());
        }

        return false;
    }

    public function getPermission(string $permissionName): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return parent::getPermission($permissionName);
    }

    /**
     * @param int[]|string $roles
     *
     * @return $this
     */
    public function setRoles(array|string $roles): static
    {
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }

        // empty entries ('1,,2', a trailing comma) would become 0, the system user
        $roles = array_filter(array_map('intval', $roles), static fn (int $id): bool => $id > 0);
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    /**
     * The roles of the user; ids that are not (or no longer) a role, e.g. a deleted role or a role folder, are
     * skipped with a warning instead of failing every permission check
     *
     * @return Role[]
     */
    private function getRoleObjects(): array
    {
        $roles = [];
        foreach ($this->getRoles() as $roleId) {
            $role = Role::getById($roleId);
            if ($role instanceof Role) {
                $roles[] = $role;
            } else {
                Logger::warning(sprintf('User %s (%s) has the unknown role id %d', $this->getId(), $this->getName(), $roleId));
            }
        }

        return $roles;
    }

    /**
     * @return int[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return $this
     */
    public function setWelcomescreen(bool $welcomescreen): static
    {
        $this->welcomescreen = $welcomescreen;

        return $this;
    }

    public function getWelcomescreen(): bool
    {
        return $this->welcomescreen;
    }

    /**
     * @return $this
     */
    public function setCloseWarning(bool $closeWarning): static
    {
        $this->closeWarning = $closeWarning;

        return $this;
    }

    public function getCloseWarning(): bool
    {
        return $this->closeWarning;
    }

    /**
     * @return $this
     */
    public function setMemorizeTabs(bool $memorizeTabs): static
    {
        $this->memorizeTabs = $memorizeTabs;

        return $this;
    }

    public function getMemorizeTabs(): bool
    {
        return $this->memorizeTabs;
    }

    /**
     * @return $this
     */
    public function setAllowDirtyClose(bool $allowDirtyClose): static
    {
        $this->allowDirtyClose = $allowDirtyClose;

        return $this;
    }

    public function getAllowDirtyClose(): bool
    {
        return $this->allowDirtyClose;
    }

    /**
     * @internal
     */
    protected function getOriginalImageStoragePath(): string
    {
        return sprintf('/user-image/user-%s.png', $this->getId());
    }

    /**
     * @internal
     */
    protected function getThumbnailImageStoragePath(): string
    {
        return sprintf('/user-image/user-thumbnail-%s.png', $this->getId());
    }

    public function setImage(?string $path): void
    {
        $storage = Tool\Storage::get('admin');
        $originalFileStoragePath = $this->getOriginalImageStoragePath();
        $thumbFileStoragePath = $this->getThumbnailImageStoragePath();

        if ($storage->fileExists($originalFileStoragePath)) {
            $storage->delete($originalFileStoragePath);
        }

        if ($storage->fileExists($thumbFileStoragePath)) {
            $storage->delete($thumbFileStoragePath);
        }

        if ($path) {
            $handle = fopen($path, 'rb');
            $storage->writeStream($originalFileStoragePath, $handle);
            fclose($handle);
        }
    }

    /**
     *
     * @return resource
     */
    public function getImage(?int $width = null, ?int $height = null)
    {
        if (!$width) {
            $width = 46;
        }
        if (!$height) {
            $height = 46;
        }

        $storage = Tool\Storage::get('admin');
        if ($storage->fileExists($this->getOriginalImageStoragePath())) {
            if (!$storage->fileExists($this->getThumbnailImageStoragePath())) {
                $originalImageStream = $storage->readStream($this->getOriginalImageStoragePath());
                $localFile = self::getLocalFileFromStream($originalImageStream);
                @fclose($originalImageStream);
                $targetFile = File::getLocalTempFilePath('png');

                $image = Image::getInstance();
                if ($image->load($localFile)) {
                    $image->cover($width, $height);
                    $image->save($targetFile, 'png');
                    $storage->write($this->getThumbnailImageStoragePath(), file_get_contents($targetFile));
                }
            }

            if ($storage->fileExists($this->getThumbnailImageStoragePath())) {
                return $storage->readStream($this->getThumbnailImageStoragePath());
            }
        }

        return fopen($this->getFallbackImage(), 'rb');
    }

    /**
     * @return string[]
     */
    public function getContentLanguages(): array
    {
        if (is_string($this->contentLanguages) && strlen($this->contentLanguages)) {
            return explode(',', $this->contentLanguages);
        }

        return [];
    }

    /**
     * @param string[]|string|null $contentLanguages
     */
    public function setContentLanguages(array|string|null $contentLanguages): void
    {
        if (is_array($contentLanguages)) {
            $contentLanguages = implode(',', $contentLanguages);
        }
        $this->contentLanguages = $contentLanguages;
    }

    /**
     * The stored perspective while the user may still use it, otherwise the first allowed one
     */
    public function getActivePerspective(): string
    {
        // admins may use every defined perspective
        $allowed = $this->isAdmin() ? self::getDefinedPerspectiveNames() : $this->getMergedPerspectives();
        if ($this->activePerspective && in_array($this->activePerspective, $allowed, true)) {
            return $this->activePerspective;
        }

        return $this->getFirstAllowedPerspective();
    }

    public function setActivePerspective(?string $activePerspective): void
    {
        $this->activePerspective = $activePerspective;
    }

    /**
     * Returns an array of the defined perspective names related to user and all related roles
     *
     * Not cached on the object, like the class and doc type permissions: the user is shared through the runtime
     * cache, so a cached result would miss later changes to the user or its roles.
     *
     * @return string[]
     */
    private function getMergedPerspectives(): array
    {
        $perspectives = $this->getPerspectives();
        foreach ($this->getRoleObjects() as $userRole) {
            $perspectives = array_merge($perspectives, $userRole->getPerspectives());
        }

        $definedPerspectives = self::getDefinedPerspectiveNames();
        if (!$perspectives) {
            // nothing assigned: every perspective is allowed
            return $definedPerspectives;
        }

        // a deleted or renamed perspective is no longer allowed
        return array_values(array_intersect(array_unique($perspectives), $definedPerspectives));
    }

    /**
     * Returns the first perspective name
     *
     * @internal
     */
    public function getFirstAllowedPerspective(): string
    {
        // the assigned perspectives first, also for admins
        return $this->getMergedPerspectives()[0]
            ?? ($this->isAdmin() ? self::getDefinedPerspectiveNames()[0] : 'default');
    }

    /**
     * Names of the defined perspectives (pimcore.perspectives.definitions), or "default" when none
     * is defined - the admin UIs fall back to a built-in perspective of that name then.
     *
     * Reads the config directly instead of through an admin UI bundle, so permission checks work
     * with any admin installed.
     *
     * @return string[]
     */
    private static function getDefinedPerspectiveNames(): array
    {
        $config = Config::getSystemConfiguration() ?? [];
        $repository = new LocationAwareConfigRepository(
            $config['perspectives']['definitions'] ?? [],
            'pimcore_perspectives',
            $config['config_location']['perspectives'] ?? []
        );

        $names = array_map('strval', $repository->fetchAllKeys());

        return $names ?: ['default'];
    }

    /**
     * Returns array of website translation languages for editing related to user and all related roles
     *
     * @return string[]
     */
    private function getMergedWebsiteTranslationLanguagesEdit(): array
    {
        $languages = $this->getWebsiteTranslationLanguagesEdit();
        foreach ($this->getRoleObjects() as $userRole) {
            $languages = array_merge($languages, $userRole->getWebsiteTranslationLanguagesEdit());
        }

        return array_values(array_unique($languages));
    }

    /**
     * Returns array of languages allowed for editing. If edit and view languages are empty all languages are allowed.
     * If only edit languages are empty (but view languages not) empty array is returned.
     *
     * @return string[]|null
     * @internal
     *
     */
    public function getAllowedLanguagesForEditingWebsiteTranslations(): ?array
    {
        $mergedWebsiteTranslationLanguagesEdit = $this->getMergedWebsiteTranslationLanguagesEdit();
        if (
            (!$mergedWebsiteTranslationLanguagesEdit && !$this->getMergedWebsiteTranslationLanguagesView()) ||
            $this->isAdmin()
        ) {
            return Tool::getValidLanguages();
        }

        return $mergedWebsiteTranslationLanguagesEdit;
    }

    /**
     * Returns array of website translation languages for viewing related to user and all related roles
     *
     * @return string[]
     */
    private function getMergedWebsiteTranslationLanguagesView(): array
    {
        $languages = $this->getWebsiteTranslationLanguagesView();
        foreach ($this->getRoleObjects() as $userRole) {
            $languages = array_merge($languages, $userRole->getWebsiteTranslationLanguagesView());
        }

        return array_values(array_unique($languages));
    }

    /**
     * Returns array of languages allowed for viewing. If view languages are empty all languages are allowed.
     *
     * @return string[]|null
     * @internal
     *
     */
    public function getAllowedLanguagesForViewingWebsiteTranslations(): ?array
    {
        $mergedWebsiteTranslationLanguagesView = $this->getMergedWebsiteTranslationLanguagesView();
        if (empty($mergedWebsiteTranslationLanguagesView) || $this->isAdmin()) {
            return Tool::getValidLanguages();
        }

        return $mergedWebsiteTranslationLanguagesView;
    }

    public function getLastLogin(): ?int
    {
        return $this->lastLogin;
    }

    /**
     * @return $this
     */
    public function setLastLogin(int $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getKeyBindings(): ?string
    {
        return $this->keyBindings;
    }

    public function setKeyBindings(string $keyBindings): void
    {
        $this->keyBindings = $keyBindings;
    }

    public function getTwoFactorAuthentication(?string $key = null): mixed
    {
        if ($this->twoFactorAuthentication === null) {
            // set defaults if no data is present
            $this->twoFactorAuthentication = [
                'required' => false,
                'enabled' => false,
                'secret' => '',
                'type' => '',
            ];
        }

        if ($key) {
            return $this->twoFactorAuthentication[$key] ?? null;
        }

        return $this->twoFactorAuthentication;
    }

    /**
     * You can either pass an array for setting the entire 2fa settings, or a key and a value as the second argument
     *
     * @param array<string, mixed>|string $key
     */
    public function setTwoFactorAuthentication(array|string $key, mixed $value = null): void
    {
        if (is_string($key) && $value === null && strlen($key) > 3) {
            $this->twoFactorAuthentication = json_decode($key, true);
        } elseif (is_array($key)) {
            $this->twoFactorAuthentication = $key;
        } else {
            if ($this->twoFactorAuthentication === null) {
                // load defaults
                $this->getTwoFactorAuthentication();
            }

            $this->twoFactorAuthentication[$key] = $value;
        }
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): void
    {
        $this->provider = $provider;
    }

    public function hasImage(): bool
    {
        return Tool\Storage::get('admin')->fileExists($this->getOriginalImageStoragePath());
    }

    /**
     * @internal
     */
    protected function getFallbackImage(): string
    {
        return PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/avatar.png';
    }

    public function getDatetimeLocale(): ?string
    {
        return $this->datetimeLocale;
    }

    public function setDatetimeLocale(?string $datetimeLocale): User
    {
        $this->datetimeLocale = $datetimeLocale;

        return $this;
    }
}
