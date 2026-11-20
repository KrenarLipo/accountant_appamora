<?php

namespace App\Services\Ai;

/**
 * Accepts AI text only when it is short plain text and every number in it comes from the facts we sent.
 */
class AlertExplanationGuard
{
    private const MAX_LENGTH = 700;

    /**
     * @param  array<string, mixed>  $facts
     */
    public function accept(string $text, array $facts): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '' || mb_strlen($text) > self::MAX_LENGTH) {
            return null;
        }

        if (preg_match('/<[a-z\/!]|https?:|www\.|```|\*\*/i', $text)) {
            return null;
        }

        $allowed = $this->allowedNumbers($facts);

        preg_match_all('/\d+(?:[.,]\d+)*/', $text, $matches);

        foreach ($matches[0] as $number) {
            if (! $this->isAllowed($number, $allowed)) {
                return null;
            }
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $facts
     * @return array<string, true>
     */
    private function allowedNumbers(array $facts): array
    {
        $allowed = [];

        foreach (range(1, 12) as $small) {
            $allowed[$this->key((float) $small)] = true;
        }

        array_walk_recursive($facts, function (mixed $value) use (&$allowed): void {
            $candidates = [];

            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                $candidates[] = (float) $value;
            } elseif (is_string($value)) {
                preg_match_all('/\d+(?:\.\d+)?/', $value, $parts);
                $candidates = array_map('floatval', $parts[0]);
            }

            foreach ($candidates as $number) {
                foreach ([abs($number), round(abs($number), 1), round(abs($number))] as $variant) {
                    $allowed[$this->key($variant)] = true;
                }
            }
        });

        return $allowed;
    }

    /**
     * @param  array<string, true>  $allowed
     */
    private function isAllowed(string $number, array $allowed): bool
    {
        $interpretations = [
            str_replace(',', '', $number),
            str_replace(['.', ','], ['', '.'], $number),
        ];

        foreach ($interpretations as $candidate) {
            if (is_numeric($candidate) && isset($allowed[$this->key((float) $candidate)])) {
                return true;
            }
        }

        return false;
    }

    private function key(float $number): string
    {
        return number_format($number, 2, '.', '');
    }
}
