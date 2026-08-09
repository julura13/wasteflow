<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserPendingApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $registeredUser
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'user_registration',
            'badge_type' => 'info',
            'badge_label' => 'new user',
            'title' => "New user registered: {$this->registeredUser->name}",
            'description' => "{$this->registeredUser->email} is awaiting approval before they can sign in.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New User Registration - WasteFlow Portal')
            ->view('emails.new-user-pending-approval', [
                'registeredUser' => $this->registeredUser,
                'usersUrl' => route('users.index', ['active' => '0']),
            ]);
    }
}
