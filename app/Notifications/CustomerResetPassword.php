<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Recuperación de contraseña de una cuenta de comprador (el enlace usa la ruta password.reset y el broker "customers"). */
class CustomerResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.customers.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña | Overshark')
            ->greeting('Hola')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace vence en {$minutes} minutos y solo sirve una vez.")
            ->line('Si no lo pediste, no hace falta que hagas nada: tu contraseña sigue igual.')
            ->salutation('El equipo de Overshark');
    }
}
