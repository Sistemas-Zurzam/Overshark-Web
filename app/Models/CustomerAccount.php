<?php

namespace App\Models;

use App\Models\Admin\Order;
use App\Notifications\CustomerResetPassword;
use App\Notifications\CustomerVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cuenta de comprador de la tienda (guard "customer"). Es independiente de User, que es solo del administrador:
 * no tiene rol, no usa el JWT y no puede entrar al panel. Sus datos comerciales siguen en `clientes`.
 */
class CustomerAccount extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $fillable = ['name', 'apellidos', 'email', 'phone', 'pending_email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_account_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->orderBy('id');
    }

    public function fullName(): string
    {
        return trim($this->name.' '.$this->apellidos);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomerVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPassword($token));
    }
}
