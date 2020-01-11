<?php

namespace TCG\Voyager\Traits;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Traits\HasCache;

/**
 * @property  \Illuminate\Database\Eloquent\Collection  roles
 */
trait VoyagerUser
{
    /**
     * Return default User Role.
     */
    public function role()
    {
        return $this->belongsTo(Voyager::modelClass('Role'));
    }

    /**
     * Return alternative User Roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Voyager::modelClass('Role'), 'user_roles', 'user_id', 'role_id');
    }

    /**
     * Return all User Roles, merging the default and alternative roles.
     */
    public function roles_all()
    {
        $this->loadRolesRelations();

        return collect([$this->role])->merge($this->roles);
    }

    /**
     * Check if User has a Role(s) associated.
     *
     * @param string|array $name The role(s) to check.
     *
     * @return bool
     */
    public function hasRole($name)
    {
        $roles = $this->roles_all()->pluck('name')->toArray();

        foreach ((is_array($name) ? $name : [$name]) as $role) {
            if (in_array($role, $roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set default User Role.
     *
     * @param string $name The role name to associate.
     */
    public function setRole($name)
    {
        $role = Voyager::model('Role')->where('name', '=', $name)->first();

        if ($role) {
            $this->role()->associate($role);
            $this->save();
        }

        return $this;
    }

    public function hasPermission($name)
    {
        $_permissions = $this->roles_all()
                              ->pluck('permissions')->flatten()
                              ->pluck('key')->unique()->toArray();

        return in_array($name, $_permissions);
    }

    public function hasPermissionOrFail($name)
    {
        if (!$this->hasPermission($name)) {
            throw new UnauthorizedHttpException(null);
        }

        return true;
    }

    public function hasPermissionOrAbort($name, $statusCode = 403)
    {
        if (!$this->hasPermission($name)) {
            return abort($statusCode);
        }

        return true;
    }

    private function loadRolesRelations()
    {
        if (!$this->relationLoaded('role') || !$this->relationLoaded('roles')) {
            $rolesAll = Voyager::model('Role')->getCachedWith(['permissions' => Voyager::modelClass('Permission')]);
        }

        if (!$this->relationLoaded('role')) {
            $role = $rolesAll->where('id', $this->role_id)->first();

            $this->setRelation('role', $role);
        }

        if (!$this->relationLoaded('roles')) {
            if (in_array(HasCache::class, class_uses_recursive($this))) {
                $user = static::getCachedWith(['roles' => Voyager::modelClass('Role')])->where($this->getKeyName(), $this->getKey())->first();

                $roles = $user->roles;
            } else {
                $roles = $rolesAll->whereIn('id', $this->roles()->get()->pluck('id')->toArray());
            }

            $this->setRelation('roles', $roles);
        }
    }
}
