<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'company_id',
        'role_id',
        'name',
        'short_code',
        'email',
        'phone',
        'password',
        'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function managedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user');
    }

    public function getAllowedCompanyIds(): array
    {
        $managedIds = $this->managedCompanies()->pluck('companies.id')->toArray();

        if (!empty($managedIds)) {
            if ($this->company_id && !in_array($this->company_id, $managedIds)) {
                $managedIds[] = $this->company_id;
            }
            return array_values(array_unique($managedIds));
        }

        if ($this->company_id) {
            return [$this->company_id];
        }

        return Company::pluck('id')->toArray();
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canDo(string $perm): bool
    {
        if ($this->isAdmin()) return true;
        return $this->roleModel ? $this->roleModel->hasPermission($perm) : false;
    }

    public function getPermissions(): array
    {
        if ($this->isAdmin()) {
            return [
                'access_admin_panel',
                'manage_companies',
                'manage_users',
                'manage_clients',
                'manage_roles',
                'manage_files',
                'view_other_appointments',
                'edit_appointments',
                'manage_absences',
            ];
        }
        return $this->roleModel ? ($this->roleModel->permissions ?? []) : [];
    }

    public function companies()
    {
        return $this->belongsToMany(\App\Models\Company::class, 'company_user')->withTimestamps();
    }
}
