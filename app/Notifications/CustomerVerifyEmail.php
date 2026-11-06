<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Verificación del correo de una cuenta de comprador (el enlace usa la ruta firmada verification.verify). */
class CustomerVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifica tu correo | Overshark')
            ->greeting('¡Bienvenido a Overshark!')
            ->line('Confirma tu correo para proteger tu cuenta y recibir las novedades de tus pedidos.')
            ->action('Verificar mi correo', $url)
            ->line('Si no creaste una cuenta, puedes ignorar este mensaje.')
            ->salutation('El equipo de Overshark');
    }
}
