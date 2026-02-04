<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EmployeeResetPassword extends Notification
{
    /**
     * Ссылка для установки пароля.
     */
    protected string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Установка пароля для доступа к графику смен')
            ->greeting('Здравствуйте, ' . ($notifiable->name ?? 'сотрудник') . '!')
            ->line('Администратор создал для вас аккаунт в системе планирования смен.')
            ->line('Чтобы установить пароль и получить доступ к своему графику, нажмите кнопку ниже.')
            ->action('Установить пароль', $this->url)
            ->line('Ссылка для установки пароля действительна 60 минут.')
            ->line('Если вы не ожидали это письмо, просто проигнорируйте его.');
    }
}
