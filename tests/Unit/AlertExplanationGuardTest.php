<?php

namespace Tests\Unit;

use App\Services\Ai\AlertExplanationGuard;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class AlertExplanationGuardTest extends TestCase
{
    private const FACTS = [
        'currency' => 'EUR',
        'month' => '2026-11',
        'normal_amount' => '500.00',
        'actual_amount' => '1650.00',
        'difference_percent' => 30.0,
        'previous_months' => [['month' => '2026-10', 'total' => '480.00']],
    ];

    #[TestWith(['Marketing reached EUR 1,650.00, 30% above the normal EUR 500. Review the ads.'])]
    #[TestWith(['Spending was EUR 1.650,00 in 2026-11 compared with EUR 480 in October.'])]
    #[TestWith(['Over the last 3 months it stayed near normal.'])]
    public function test_it_accepts_text_that_only_uses_given_numbers(string $text): void
    {
        $this->assertSame($text, (new AlertExplanationGuard)->accept($text, self::FACTS));
    }

    #[TestWith(['Spending rose by EUR 1,150, so cut EUR 200 next month.'])]
    #[TestWith(['**Marketing** is over budget.'])]
    #[TestWith(['See https://example.com for help.'])]
    #[TestWith(['   '])]
    public function test_it_rejects_invented_numbers_markdown_links_and_empty_text(string $text): void
    {
        $this->assertNull((new AlertExplanationGuard)->accept($text, self::FACTS));
    }

    public function test_it_rejects_overly_long_text(): void
    {
        $this->assertNull((new AlertExplanationGuard)->accept(str_repeat('Too long. ', 100), self::FACTS));
    }
}
