<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // NOTE: `usertype`, `is_banned` and `is_blocked` are deliberately NOT mass-assignable.
    // They are privilege-sensitive, so they must be set explicitly (e.g. $user->usertype = ...;
    // $user->save()) — this prevents a User::create($request->all()) / update() from ever
    // letting a registrant make themselves an Administrator or unban their own account.
    protected $fillable = [
        'name',
        'email',
        'contactnum',
        'affiliation',
        'sex',
        'birthdate',
        'password',
        'patrontypes_id',
        'library_code',
        'region_id',
        'position1',
        'position2',
        'position3',
        'position4',
        'username',
        'location',
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
        'is_banned'  => 'boolean',
        'is_blocked' => 'boolean',
        'appearance' => 'array',   // App\Services\Appearance; null = the library default
    ];

    /**
     * A staff account: it can open the admin panel. Staff can also browse the OPAC (search, read,
     * download, share, save), but not as a patron: no profile, notifications, inquiries,
     * reservations or reviews, and their views, reads and downloads are not counted in the statistics.
     */
    public function isStaff(): bool
    {
        return in_array($this->usertype, ['Administrator', 'Developer'], true);
    }

    /** False for an account made with "Continue with Google" until its owner sets a password (App\Services\GoogleSignIn). */
    public function hasPassword(): bool
    {
        return filled($this->getAuthPassword());
    }

    // public function patrontypes()
    // {
    //     return $this->hasMany(Patrontype::class);
    // }
}
