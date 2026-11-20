<?php

namespace Database\Seeders;

use App\Actions\CreateWorkspace;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Local demo data: a verified user with a personal and a business workspace and four months of activity.
 * Never run in production.
 */
class DemoSeeder extends Seeder
{
    public function run(CreateWorkspace $createWorkspace): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        $home = $createWorkspace->handle($user, [
            'name' => 'Home', 'type' => 'personal', 'base_currency' => 'EUR',
            'timezone' => 'Europe/Tirane', 'country' => 'AL',
            'account_name' => 'Main bank', 'opening_balance' => '1200.00',
        ]);
        $cash = $home->accounts()->create(['name' => 'Cash', 'type' => 'cash', 'currency' => 'EUR', 'opening_balance' => '80.00']);

        $this->seedMonths($home, [
            ['Salary', 'income', 'Employer', [1450, 1450], 1],
            ['Housing', 'expense', 'Landlord', [450, 450], 2],
            ['Groceries', 'expense', 'Supermarket', [35, 70], 6],
            ['Dining out', 'expense', 'Café', [8, 30], 4],
            ['Transport', 'expense', 'Fuel station', [25, 50], 3],
            ['Utilities', 'expense', 'Electricity company', [40, 75], 1],
            ['Subscriptions', 'expense', 'Streaming service', [12, 12], 1],
            ['Entertainment', 'expense', 'Cinema', [10, 25], 2],
        ]);

        $home->transactions()->create([
            'type' => TransactionType::Transfer, 'account_id' => $home->accounts()->first()->id,
            'transfer_account_id' => $cash->id, 'amount' => '100.00', 'currency' => 'EUR',
            'transaction_date' => CarbonImmutable::now()->startOfMonth()->addDays(2)->toDateString(),
            'description' => 'ATM withdrawal',
        ]);

        $home->budgets()->create(['category_id' => null, 'amount' => '1300.00', 'alert_thresholds' => [70, 90, 100]]);
        foreach (['Groceries' => '350.00', 'Dining out' => '90.00', 'Transport' => '150.00'] as $name => $amount) {
            $home->budgets()->create([
                'category_id' => $home->categories()->where('name', $name)->value('id'),
                'amount' => $amount,
                'alert_thresholds' => [70, 90, 100],
            ]);
        }

        foreach (['Groceries' => '220.00', 'Dining out' => '60.00', 'Utilities' => '55.00'] as $name => $normal) {
            $home->categories()->where('name', $name)->update(['normal_amount' => $normal, 'normal_tolerance' => 20]);
        }

        $shop = $createWorkspace->handle($user, [
            'name' => 'Demo Shop', 'type' => 'business', 'base_currency' => 'EUR',
            'timezone' => 'Europe/Tirane', 'country' => 'AL',
            'account_name' => 'Business account', 'opening_balance' => '5000.00',
        ]);

        $this->seedMonths($shop, [
            ['Sales', 'income', 'Customers', [180, 420], 12],
            ['Suppliers', 'expense', 'Wholesaler', [300, 900], 3, [1.1, 1.6]],
            ['Rent', 'expense', 'Property owner', [800, 800], 1],
            ['Salaries', 'expense', 'Payroll', [1100, 1100], 1],
            ['Marketing', 'expense', 'Social media ads', [50, 200], 2],
            ['Software', 'expense', 'POS subscription', [29, 29], 1],
        ]);

        foreach (['Marketing' => '150.00', 'Suppliers' => '1500.00', 'Sales' => '3500.00'] as $name => $normal) {
            $shop->categories()->where('name', $name)->update(['normal_amount' => $normal, 'normal_tolerance' => 25]);
        }
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: array{0: int, 1: int}, 4: int, 5?: array{0: float, 1: float}}>  $patterns  [category, type, merchant, [min, max], times per month, optional return multiplier range]
     */
    private function seedMonths(Workspace $workspace, array $patterns): void
    {
        $account = $workspace->accounts()->orderBy('id')->first();
        $today = CarbonImmutable::now($workspace->timezone);

        for ($monthsAgo = 3; $monthsAgo >= 0; $monthsAgo--) {
            $month = $today->startOfMonth()->subMonths($monthsAgo);
            $lastDay = $monthsAgo === 0 ? $today->day : $month->daysInMonth;

            foreach ($patterns as $pattern) {
                [$categoryName, $type, $merchant, [$min, $max], $times] = $pattern;
                $returnRange = $pattern[5] ?? null;
                $categoryId = $workspace->categories()->where('name', $categoryName)->value('id');

                for ($i = 0; $i < $times; $i++) {
                    $amount = fake()->randomFloat(2, $min, $max);
                    $returnAmount = $returnRange
                        ? number_format($amount * fake()->randomFloat(2, $returnRange[0], $returnRange[1]), 2, '.', '')
                        : null;

                    $transaction = new Transaction([
                        'type' => $type,
                        'account_id' => $account->id,
                        'category_id' => $categoryId,
                        'amount' => number_format($amount, 2, '.', ''),
                        'return_amount' => $returnAmount,
                        'currency' => $workspace->base_currency,
                        'transaction_date' => $month->addDays(fake()->numberBetween(0, $lastDay - 1))->toDateString(),
                        'merchant' => $merchant,
                    ]);
                    $transaction->workspace()->associate($workspace);
                    $transaction->save();
                }
            }
        }
    }
}
