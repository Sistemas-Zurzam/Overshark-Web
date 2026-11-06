<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** Se envía al correo NUEVO: el cambio solo se aplica cuando la clienta abre este enlace firmado. */
class CustomerConfirmNewEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $accountId, private readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('customer.email.confirm', now()->addMinutes(60), [
            'id' => $this->accountId,
            'hash' => sha1($this->newEmail),
        ]);

        return (new MailMessage)
            ->subject('Confirma tu nuevo correo | Overshark')
            ->greeting('Hola')
            ->line('Pediste usar este correo en tu cuenta de Overshark. Confírmalo para completar el cambio.')
            ->action('Confirmar correo', $url)
            ->line('Si no fuiste tú, ignora este mensaje: tu correo actual no cambia.')
            ->salutation('El equipo de Overshark');
    }
}
