<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
/**
 * Register any application services.
 */
public function register(): void
{
    //
}

/**
 * Bootstrap any application services.
 */
public function boot(): void
{
    ResetPassword::toMailUsing(function ($notifiable, $token) {
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Passwort zurücksetzen - ' . config('app.name'))
            ->greeting('Hallo!')
            ->line('Du erhältst diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für dein Benutzerkonto erhalten haben.')
            ->action('Passwort zurücksetzen', $resetUrl)
            ->line('Dieser Link zum Zurücksetzen des Passworts ist für ' . $expire . ' Minuten gültig.')
            ->line('Falls du keine Anfrage zum Zurücksetzen des Passworts gestellt hast, ist keine weitere Aktion erforderlich.')
            ->salutation("Viele Grüße,\n" . config('app.name'));
    });
}
}
