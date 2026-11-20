<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class BudgetThresholdReached extends Notification
{
    use Queueable;

    /**
     * @param  array{id: int, category_name: string, amount: string, spent: string, percent_used: float}  $budget
     */
    public function __construct(
        public Workspace $workspace,
        public array $budget,
        public int $threshold,
        public string $period,
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
        return (new MailMessage)
            ->subject($this->headline())
            ->greeting('Budget alert')
            ->line($this->message())
            ->line('Workspace: '.$this->workspace->name.' · Period: '.$this->period)
            ->action('Review budget', url('/app/budgets'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'kind' => 'budget_threshold',
            'workspace_id' => $this->workspace->id,
            'budget_id' => $this->budget['id'],
            'threshold' => $this->threshold,
            'period' => $this->period,
            'title' => $this->headline(),
            'message' => $this->message(),
            'link' => '/app/budgets',
        ];
    }

    private function headline(): string
    {
        return $this->threshold >= 100
            ? "{$this->budget['category_name']} budget is used up"
            : "{$this->budget['category_name']} has reached {$this->threshold}% of its budget";
    }

    private function message(): string
    {
        $currency = $this->workspace->base_currency;
        $spent = Number::currency((float) $this->budget['spent'], $currency);
        $amount = Number::currency((float) $this->budget['amount'], $currency);

        return "You have spent {$spent} of your {$amount} monthly budget ({$this->budget['percent_used']}%), which crossed your {$this->threshold}% alert.";
    }
}
