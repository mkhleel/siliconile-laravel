<?php

namespace Modules\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements FilamentUser
{
    use HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $admin) {
            $admin->username = $admin->username ?? str($admin->email)->before('@')->lower().'_'.uniqid();
        });

        static::created(function (self $admin) {
            $admin->assignRole(Role::where('name', 'admin')->first());
        });

    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->id == 1 || $this->hasRole(Role::where('name', 'stuff')->first(), guard: 'admin')) {
            return true;
        }

        return false;
        //        return str_ends_with($this->email, '@' . config('app.domain'))/* && $this->hasVerifiedEmail()*/;
    }
}
