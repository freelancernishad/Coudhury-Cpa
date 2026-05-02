<?php

namespace Database\Seeders;

use App\Models\FinancialCalculator;
use Illuminate\Database\Seeder;

class FinancialCalculatorSeeder extends Seeder
{
    public function run(): void
    {
        $calculators = [
            [
                'name' => 'Business Valuation',
                'slug' => 'business-valuation',
                'description' => 'Estimate the market value of your business based on net income and industry multipliers.',
                'icon' => 'Briefcase',
                'inputs' => [
                    ['id' => 'net_income', 'label' => 'Annual Net Income ($)', 'placeholder' => 'e.g. 100000', 'type' => 'number'],
                    ['id' => 'multiplier', 'label' => 'Industry Multiplier', 'placeholder' => 'e.g. 3.5', 'type' => 'number'],
                ],
                'formula' => 'net_income * multiplier',
            ],
            [
                'name' => 'Debt to Equity Ratio',
                'slug' => 'debt-to-equity',
                'description' => 'Measure your business\'s financial leverage by comparing total liabilities to total equity.',
                'icon' => 'Scale',
                'inputs' => [
                    ['id' => 'total_liabilities', 'label' => 'Total Liabilities ($)', 'placeholder' => 'e.g. 50000', 'type' => 'number'],
                    ['id' => 'total_equity', 'label' => 'Total Equity ($)', 'placeholder' => 'e.g. 150000', 'type' => 'number'],
                ],
                'formula' => 'total_liabilities / total_equity',
            ],
            [
                'name' => 'Cash Flow Projection',
                'slug' => 'cash-flow-projection',
                'description' => 'Forecast your business cash balance based on monthly inflows and outflows.',
                'icon' => 'TrendingUp',
                'inputs' => [
                    ['id' => 'opening_balance', 'label' => 'Starting Balance ($)', 'placeholder' => 'e.g. 10000', 'type' => 'number'],
                    ['id' => 'monthly_inflow', 'label' => 'Estimated Monthly Inflow ($)', 'placeholder' => 'e.g. 5000', 'type' => 'number'],
                    ['id' => 'monthly_outflow', 'label' => 'Estimated Monthly Outflow ($)', 'placeholder' => 'e.g. 3500', 'type' => 'number'],
                    ['id' => 'months', 'label' => 'Projection Period (Months)', 'placeholder' => 'e.g. 12', 'type' => 'number'],
                ],
                'formula' => 'opening_balance + (monthly_inflow - monthly_outflow) * months',
            ],
            [
                'name' => 'CAGR Calculator',
                'slug' => 'cagr',
                'description' => 'Calculate the Compound Annual Growth Rate of an investment over a period of years.',
                'icon' => 'Zap',
                'inputs' => [
                    ['id' => 'beginning_value', 'label' => 'Beginning Value ($)', 'placeholder' => 'e.g. 5000', 'type' => 'number'],
                    ['id' => 'ending_value', 'label' => 'Ending Value ($)', 'placeholder' => 'e.g. 12000', 'type' => 'number'],
                    ['id' => 'years', 'label' => 'Number of Years', 'placeholder' => 'e.g. 5', 'type' => 'number'],
                ],
                'formula' => '(pow((ending_value / beginning_value), (1 / years)) - 1) * 100',
            ],
            [
                'name' => 'Freelancer Rate',
                'slug' => 'freelancer-rate',
                'description' => 'Determine your ideal hourly rate based on expenses, savings goals, and tax rate.',
                'icon' => 'DollarSign',
                'inputs' => [
                    ['id' => 'monthly_expenses', 'label' => 'Monthly Expenses ($)', 'placeholder' => 'e.g. 2000', 'type' => 'number'],
                    ['id' => 'desired_savings', 'label' => 'Monthly Savings Goal ($)', 'placeholder' => 'e.g. 1000', 'type' => 'number'],
                    ['id' => 'tax_rate', 'label' => 'Estimated Tax Rate (%)', 'placeholder' => 'e.g. 20', 'type' => 'number'],
                    ['id' => 'billable_hours', 'label' => 'Billable Hours Per Month', 'placeholder' => 'e.g. 120', 'type' => 'number'],
                ],
                'formula' => '((monthly_expenses + desired_savings) / (1 - (tax_rate / 100))) / billable_hours',
            ],
        ];

        foreach ($calculators as $calc) {
            FinancialCalculator::updateOrCreate(['slug' => $calc['slug']], $calc);
        }
    }
}
