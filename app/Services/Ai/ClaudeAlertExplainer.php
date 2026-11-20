<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Contracts\ExplainsAlerts;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

/**
 * Asks Claude to explain an alert in plain language. Figures always come from the application;
 * the reply is validated before use and any failure falls back to the app's own wording.
 */
class ClaudeAlertExplainer implements ExplainsAlerts
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        You write short alert explanations for Accountant, a money-tracking app used by households and small businesses.
        You receive figures the app has already calculated for one category in one month, compared with the amount the user set as normal.

        Write 2 or 3 plain sentences for the account owner:
        1. What happened, in everyday words.
        2. The most likely reason, based only on the listed transactions and previous months.
        3. One concrete thing to check or do next.

        Rules:
        - Use only numbers that appear in the data. Do not calculate new totals, averages or percentages.
        - Write amounts with the currency code given, e.g. "EUR 540".
        - Plain text only: no markdown, lists, links or greetings.
        - Never call a payment fraudulent; say "unusual" and suggest reviewing it.
        - This is not tax, legal or investment advice; do not give any.
        PROMPT;

    public function __construct(private AlertExplanationGuard $guard) {}

    public function isAvailable(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function explain(Workspace $workspace, array $facts): ?string
    {
        if (! $workspace->ai_insights_enabled || ! $this->isAvailable()) {
            return null;
        }

        try {
            $client = new Client(apiKey: config('services.anthropic.key'));

            $message = $client->beta->messages->create(
                maxTokens: 4000,
                messages: [[
                    'role' => 'user',
                    'content' => "Alert data (JSON):\n".json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ]],
                model: config('services.anthropic.model'),
                fallbacks: 'default',
                outputConfig: ['effort' => 'low'],
                system: self::SYSTEM_PROMPT,
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: ['timeout' => 30.0, 'maxRetries' => 2],
            );
        } catch (AuthenticationException $exception) {
            Log::error('Claude API key was rejected; alert explanations are disabled until it is fixed.', ['status' => $exception->status]);

            return null;
        } catch (RateLimitException $exception) {
            Log::warning('Claude rate limit reached while explaining an alert.', ['status' => $exception->status]);

            return null;
        } catch (APIStatusException $exception) {
            Log::warning('Claude returned an error while explaining an alert.', ['status' => $exception->status, 'type' => $exception->getType()]);

            return null;
        } catch (APIConnectionException $exception) {
            Log::warning('Could not reach Claude to explain an alert.', ['error' => $exception->getMessage()]);

            return null;
        }

        if ($message->stopReason === 'refusal' || $message->stopReason === 'max_tokens') {
            return null;
        }

        $text = '';

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $accepted = $this->guard->accept($text, $facts);

        if ($accepted === null) {
            Log::info('Discarded an AI alert explanation that failed validation.');
        }

        return $accepted;
    }
}
