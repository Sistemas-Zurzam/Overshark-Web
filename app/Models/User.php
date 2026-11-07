<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */ 
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'status',
        'jwt_version',
        'invited_at',
        'last_login_at',
        'all_brands_access',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'jwt_version' => 'integer',
            'invited_at' => 'datetime',
            'last_login_at' => 'datetime',
            'all_brands_access' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function brands()
    {
        return $this->belongsToMany(Brand::class);
    }

    /** Cualquier rol asignado puede entrar al panel; antes solo el rol "admin" podia hacerlo. */
    public function isAdmin(): bool
    {
        return $this->status && $this->role !== null;
    }

    public function isPendingInvite(): bool
    {
        return $this->invited_at !== null;
    }

    public function brandAccessLabel(): string
    {
        if ($this->all_brands_access) {
            return 'Todas';
        }

        $names = $this->relationLoaded('brands') ? $this->brands : $this->brands()->orderBy('sort_order')->get();

        return $names->isEmpty() ? 'Sin marcas' : $names->pluck('name')->implode(', ');
    }

    public function lastAccessLabel(): string
    {
        if (! $this->last_login_at) {
            return '—';
        }

        return $this->last_login_at->diffInMinutes(now()) < 5
            ? 'Ahora'
            : $this->last_login_at->format('d/m/Y H:i');
    }
}
