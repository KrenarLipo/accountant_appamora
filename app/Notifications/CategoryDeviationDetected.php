<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class CategoryDeviationDetected extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $facts
     */
    public function __construct(
        public Workspace $workspace,
        public array $facts,
        public ?string $aiExplanation,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting($this->title())
            ->line($this->message());

        if ($this->aiExplanation) {
            $mail->line('AI explanation: '.$this->aiExplanation);
        }

        return $mail
            ->line('Workspace: '.$this->workspace->name)
            ->action('Review transactions', url('/app/transactions'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'kind' => 'category_deviation',
            'workspace_id' => $this->workspace->id,
            'direction' => $this->facts['direction'],
            'period' => $this->facts['month'],
            'title' => $this->title(),
            'message' => $this->message(),
            'ai_explanation' => $this->aiExplanation,
            'link' => '/app/transactions',
        ];
    }

    private function title(): string
    {
        $category = $this->facts['category'];

        return $this->facts['direction'] === 'over'
            ? "{$category} is higher than normal"
            : "{$category} was lower than normal";
    }

    private function message(): string
    {
        $currency = $this->facts['currency'];
        $actual = Number::currency((float) $this->facts['actual_amount'], $currency);
        $normal = Number::currency((float) $this->facts['normal_amount'], $currency);
        $difference = abs($this->facts['difference_percent']);
        $word = $this->facts['direction'] === 'over' ? 'above' : 'below';

        return "{$this->facts['category']} totals {$actual} for {$this->facts['month']}, {$difference}% {$word} your normal {$normal} (you allowed ±{$this->facts['allowed_difference_percent']}%).";
    }
}
