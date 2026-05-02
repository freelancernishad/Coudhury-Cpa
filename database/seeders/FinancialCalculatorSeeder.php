<?php

namespace Database\Seeders;

use App\Models\FinancialCalculator;
use App\Models\FinancialCalculatorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FinancialCalculatorSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name' => 'Automobile',
                'calculators' => [
                    [
                        'name' => 'Car Depreciation Calculator',
                        'inputs' => [
                            ['id' => 'car_purchase_price', 'label' => 'Car Purchase Price', 'type' => 'number', 'default_value' => '35000', 'note' => 'The original price paid for your car.'],
                            ['id' => 'years_owned', 'label' => 'Years Owned', 'type' => 'number', 'default_value' => '5', 'note' => 'How many years you will own or keep the car.'],
                            ['id' => 'annual_depreciation_rate', 'label' => 'Annual Depreciation Rate', 'type' => 'percentage', 'default_value' => '15', 'note' => 'Estimated yearly depreciation percentage.'],
                        ],
                        'results' => [
                            ['label' => 'Car Value After Ownership', 'formula' => 'car_purchase_price * pow( ( 1 - ( annual_depreciation_rate / 100 ) ) , years_owned )'],
                            ['label' => 'Total Depreciation Loss', 'formula' => 'car_purchase_price - ( car_purchase_price * pow( ( 1 - ( annual_depreciation_rate / 100 ) ) , years_owned ) )'],
                            ['label' => 'Percent Value Remaining', 'formula' => '( ( car_purchase_price * pow( ( 1 - ( annual_depreciation_rate / 100 ) ) , years_owned ) ) / car_purchase_price ) * 100'],
                        ]
                    ],
                    [
                        'name' => 'Accelerated Payoff Calculator',
                        'inputs' => [
                            ['id' => 'loan_amount', 'label' => 'Loan Amount', 'type' => 'number', 'default_value' => '20000'],
                            ['id' => 'interest_rate', 'label' => 'Interest Rate', 'type' => 'percentage', 'default_value' => '7'],
                            ['id' => 'remaining_term', 'label' => 'Remaining Term (months)', 'type' => 'number', 'default_value' => '60'],
                            ['id' => 'new_monthly_payment', 'label' => 'New Monthly Payment', 'type' => 'number', 'default_value' => '500'],
                        ],
                        'results' => [
                            ['label' => 'Standard Monthly Payment', 'formula' => '( loan_amount * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , remaining_term ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , remaining_term ) - 1 )'],
                            ['label' => 'Total Paid (Standard)', 'formula' => '( ( loan_amount * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , remaining_term ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , remaining_term ) - 1 ) ) * remaining_term'],
                            ['label' => 'Interest Savings', 'formula' => '( ( ( loan_amount * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , remaining_term ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , remaining_term ) - 1 ) ) * remaining_term ) - ( new_monthly_payment * ( log( new_monthly_payment / ( new_monthly_payment - loan_amount * ( interest_rate / 100 / 12 ) ) ) / log( 1 + ( interest_rate / 100 / 12 ) ) ) )'],
                        ]
                    ],
                    [
                        'name' => 'Electric Vehicle (EV) Savings',
                        'inputs' => [
                            ['id' => 'miles_per_year', 'label' => 'Miles Driven Per Year', 'type' => 'number', 'default_value' => '12000'],
                            ['id' => 'gas_mpg', 'label' => 'Gas Vehicle Fuel Efficiency (MPG)', 'type' => 'number', 'default_value' => '28'],
                            ['id' => 'gas_price', 'label' => 'Average Gas Price Per Gallon', 'type' => 'number', 'default_value' => '4'],
                            ['id' => 'ev_efficiency', 'label' => 'EV Efficiency (Miles per kWh)', 'type' => 'number', 'default_value' => '4'],
                            ['id' => 'electricity_price', 'label' => 'Average Electricity Price per kWh', 'type' => 'number', 'default_value' => '0.15'],
                            ['id' => 'ownership_years', 'label' => 'Ownership Period (Years)', 'type' => 'number', 'default_value' => '5'],
                        ],
                        'results' => [
                            ['label' => 'Total Gasoline Cost', 'formula' => '( miles_per_year / gas_mpg ) * gas_price * ownership_years'],
                            ['label' => 'Total EV Cost', 'formula' => '( miles_per_year / ev_efficiency ) * electricity_price * ownership_years'],
                            ['label' => 'Total Savings', 'formula' => '( ( miles_per_year / gas_mpg ) * gas_price * ownership_years ) - ( ( miles_per_year / ev_efficiency ) * electricity_price * ownership_years )'],
                        ]
                    ],
                    [
                        'name' => 'How Much Can I Afford For A Car?',
                        'inputs' => [
                            ['id' => 'down_payment', 'label' => 'Total Down Payment Amount (including trade-in)', 'type' => 'number', 'default_value' => '5000'],
                            ['id' => 'monthly_budget', 'label' => 'Amount of monthly payment I can afford', 'type' => 'number', 'default_value' => '400'],
                            ['id' => 'interest_rate', 'label' => 'Loan APR', 'type' => 'percentage', 'default_value' => '4'],
                            ['id' => 'loan_term', 'label' => 'Loan Term (in months)', 'type' => 'number', 'default_value' => '60'],
                        ],
                        'results' => [
                            ['label' => 'Total Car Value You Can Afford', 'formula' => '( ( monthly_budget * ( 1 - pow( 1 + ( interest_rate / 100 / 12 ) , - loan_term ) ) ) / ( interest_rate / 100 / 12 ) ) + down_payment'],
                        ]
                    ],
                    [
                        'name' => 'How Much Will My Car Payments Be?',
                        'inputs' => [
                            ['id' => 'price', 'label' => 'Price of Auto', 'type' => 'number', 'default_value' => '25000'],
                            ['id' => 'rebate', 'label' => 'Cash Rebate', 'type' => 'number', 'default_value' => '1000'],
                            ['id' => 'trade_in', 'label' => 'Trade-in Value', 'type' => 'number', 'default_value' => '2000'],
                            ['id' => 'owed_on_trade', 'label' => 'Owed on Trade-in', 'type' => 'number', 'default_value' => '0'],
                            ['id' => 'cash_down', 'label' => 'Cash Down', 'type' => 'number', 'default_value' => '2000'],
                            ['id' => 'interest_rate', 'label' => 'Loan APR', 'type' => 'percentage', 'default_value' => '4'],
                            ['id' => 'loan_term', 'label' => 'Loan Term (in months)', 'type' => 'number', 'default_value' => '60'],
                        ],
                        'results' => [
                            ['label' => 'Monthly Payment', 'formula' => '( ( price - rebate - trade_in + owed_on_trade - cash_down ) * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , loan_term ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , loan_term ) - 1 )'],
                            ['label' => 'Total Interest Paid', 'formula' => '( ( ( price - rebate - trade_in + owed_on_trade - cash_down ) * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , loan_term ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , loan_term ) - 1 ) * loan_term ) - ( price - rebate - trade_in + owed_on_trade - cash_down )'],
                        ]
                    ],
                    [
                        'name' => 'Lease or Buy a Car?',
                        'inputs' => [
                            ['id' => 'purchase_price', 'label' => 'Purchase Price', 'type' => 'number', 'default_value' => '30000'],
                            ['id' => 'down_payment', 'label' => 'Total Down Payment Amount', 'type' => 'number', 'default_value' => '3000'],
                            ['id' => 'months', 'label' => 'Number of Monthly Payments', 'type' => 'number', 'default_value' => '36'],
                            ['id' => 'lease_payment', 'label' => 'Lease Monthly Payment amount', 'type' => 'number', 'default_value' => '350'],
                            ['id' => 'lease_fees', 'label' => 'Lease Fees & Security Deposit', 'type' => 'number', 'default_value' => '500'],
                            ['id' => 'interest_rate', 'label' => 'Loan APR (if buying)', 'type' => 'percentage', 'default_value' => '5'],
                            ['id' => 'market_value', 'label' => 'End of Loan Market Value of Car', 'type' => 'number', 'default_value' => '15000'],
                        ],
                        'results' => [
                            ['label' => 'Total Lease Cost', 'formula' => '( lease_payment * months ) + lease_fees'],
                            ['label' => 'Total Loan Cost', 'formula' => '( ( ( purchase_price - down_payment ) * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , months ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , months ) - 1 ) * months ) + down_payment - market_value'],
                        ]
                    ],
                    [
                        'name' => 'Loan to Value Ratio – Auto Loan Calculator',
                        'inputs' => [
                            ['id' => 'vehicle_value', 'label' => 'Vehicle Value', 'type' => 'number', 'default_value' => '20000'],
                            ['id' => 'loan_amount', 'label' => 'Loan Amount', 'type' => 'number', 'default_value' => '15000'],
                        ],
                        'results' => [
                            ['label' => 'Loan to Value Ratio', 'formula' => '( loan_amount / vehicle_value ) * 100'],
                        ]
                    ],
                    [
                        'name' => 'Loan vs. 0% Dealer Financing',
                        'inputs' => [
                            ['id' => 'price', 'label' => 'Vehicle Price', 'type' => 'number', 'default_value' => '35000'],
                            ['id' => 'rebate', 'label' => 'Cash Rebate Amount', 'type' => 'number', 'default_value' => '3000'],
                            ['id' => 'interest_rate', 'label' => 'Loan Interest Rate', 'type' => 'percentage', 'default_value' => '5'],
                            ['id' => 'loan_term_years', 'label' => 'Loan Term (Years)', 'type' => 'number', 'default_value' => '5'],
                            ['id' => 'down_payment', 'label' => 'Down Payment', 'type' => 'number', 'default_value' => '5000'],
                        ],
                        'results' => [
                            ['label' => 'Total Paid (With Rebate)', 'formula' => '( ( ( price - rebate - down_payment ) * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , loan_term_years * 12 ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , loan_term_years * 12 ) - 1 ) * loan_term_years * 12 ) + down_payment'],
                            ['label' => 'Total Paid (0% Financing)', 'formula' => 'price'],
                            ['label' => 'Savings by Choosing Rebate', 'formula' => 'price - ( ( ( ( price - rebate - down_payment ) * ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , loan_term_years * 12 ) ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , loan_term_years * 12 ) - 1 ) * loan_term_years * 12 ) + down_payment )'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Business',
                'calculators' => [
                    [
                        'name' => 'Business Valuation Calculator',
                        'inputs' => [
                            ['id' => 'annual_profit', 'label' => 'Annual Net Profit', 'type' => 'number', 'default_value' => '120000'],
                            ['id' => 'multiplier', 'label' => 'Earnings Multiplier', 'type' => 'number', 'default_value' => '2.5'],
                            ['id' => 'assets', 'label' => 'Total Business Assets', 'type' => 'number', 'default_value' => '200000'],
                            ['id' => 'liabilities', 'label' => 'Total Business Liabilities', 'type' => 'number', 'default_value' => '80000'],
                        ],
                        'results' => [
                            ['label' => 'Valuation (Earnings Multiplier)', 'formula' => 'annual_profit * multiplier'],
                            ['label' => 'Valuation (Asset-Based)', 'formula' => 'assets - liabilities'],
                        ]
                    ],
                    [
                        'name' => 'Debt-to-Equity Ratio Calculator',
                        'inputs' => [
                            ['id' => 'total_liabilities', 'label' => 'Total Liabilities', 'type' => 'number', 'default_value' => '120000'],
                            ['id' => 'total_equity', 'label' => 'Total Equity', 'type' => 'number', 'default_value' => '100000'],
                        ],
                        'results' => [
                            ['label' => 'Debt-to-Equity Ratio', 'formula' => 'total_liabilities / total_equity'],
                        ]
                    ],
                    [
                        'name' => 'Cash Flow Projection Calculator',
                        'inputs' => [
                            ['id' => 'starting_cash', 'label' => 'Beginning Cash Balance', 'type' => 'number', 'default_value' => '25000'],
                            ['id' => 'm1_in', 'label' => 'Month 1: Cash Inflows', 'type' => 'number', 'default_value' => '15000'],
                            ['id' => 'm1_out', 'label' => 'Month 1: Cash Outflows', 'type' => 'number', 'default_value' => '12000'],
                            ['id' => 'm2_in', 'label' => 'Month 2: Cash Inflows', 'type' => 'number', 'default_value' => '18000'],
                            ['id' => 'm2_out', 'label' => 'Month 2: Cash Outflows', 'type' => 'number', 'default_value' => '14000'],
                            ['id' => 'm3_in', 'label' => 'Month 3: Cash Inflows', 'type' => 'number', 'default_value' => '20000'],
                            ['id' => 'm3_out', 'label' => 'Month 3: Cash Outflows', 'type' => 'number', 'default_value' => '16000'],
                        ],
                        'results' => [
                            ['label' => 'Net Cash Flow (Month 1)', 'formula' => 'm1_in - m1_out'],
                            ['label' => 'Ending Cash (Month 1)', 'formula' => 'starting_cash + m1_in - m1_out'],
                            ['label' => 'Ending Cash (Month 3)', 'formula' => 'starting_cash + m1_in - m1_out + m2_in - m2_out + m3_in - m3_out'],
                        ]
                    ],
                    [
                        'name' => 'Compound Annual Growth Rate (CAGR) Calculator',
                        'inputs' => [
                            ['id' => 'start_val', 'label' => 'Beginning Value', 'type' => 'number', 'default_value' => '50000'],
                            ['id' => 'end_val', 'label' => 'Ending Value', 'type' => 'number', 'default_value' => '120000'],
                            ['id' => 'years', 'label' => 'Number of Years', 'type' => 'number', 'default_value' => '5'],
                        ],
                        'results' => [
                            ['label' => 'Total Growth', 'formula' => 'end_val - start_val'],
                            ['label' => 'CAGR', 'formula' => '( pow( end_val / start_val , 1 / years ) - 1 ) * 100'],
                        ]
                    ],
                    [
                        'name' => 'Freelancer Rate',
                        'inputs' => [
                            ['id' => 'goal', 'label' => 'Annual Income Goal', 'type' => 'number', 'default_value' => '90000'],
                            ['id' => 'weeks', 'label' => 'Weeks Worked Per Year', 'type' => 'number', 'default_value' => '48'],
                            ['id' => 'hours', 'label' => 'Billable Hours Per Week', 'type' => 'number', 'default_value' => '25'],
                            ['id' => 'expenses', 'label' => 'Annual Business Expenses', 'type' => 'number', 'default_value' => '6000'],
                            ['id' => 'tax_rate', 'label' => 'Estimated Tax Rate', 'type' => 'percentage', 'default_value' => '25'],
                            ['id' => 'margin', 'label' => 'Desired Profit Margin', 'type' => 'percentage', 'default_value' => '10'],
                        ],
                        'results' => [
                            ['label' => 'Gross Income Needed', 'formula' => '( goal + expenses ) / ( 1 - ( tax_rate / 100 ) )'],
                            ['label' => 'Recommended Hourly Rate', 'formula' => '( ( goal + expenses ) / ( 1 - ( tax_rate / 100 ) ) * ( 1 + ( margin / 100 ) ) ) / ( weeks * hours )'],
                        ]
                    ],
                    [
                        'name' => 'Net Profit Margin Calculator',
                        'inputs' => [
                            ['id' => 'revenue', 'label' => 'Total Revenue', 'type' => 'number', 'default_value' => '100000'],
                            ['id' => 'cogs', 'label' => 'Cost of Goods Sold (COGS)', 'type' => 'number', 'default_value' => '60000'],
                            ['id' => 'op_exp', 'label' => 'Operating Expenses', 'type' => 'number', 'default_value' => '20000'],
                            ['id' => 'other_exp', 'label' => 'Other Expenses', 'type' => 'number', 'default_value' => '5000'],
                        ],
                        'results' => [
                            ['label' => 'Net Profit', 'formula' => 'revenue - cogs - op_exp - other_exp'],
                            ['label' => 'Net Profit Margin', 'formula' => '( ( revenue - cogs - op_exp - other_exp ) / revenue ) * 100'],
                        ]
                    ],
                    [
                        'name' => 'ROI Calculator',
                        'inputs' => [
                            ['id' => 'invested', 'label' => 'Amount Invested', 'type' => 'number', 'default_value' => '10000'],
                            ['id' => 'returned', 'label' => 'Amount Returned', 'type' => 'number', 'default_value' => '12500'],
                        ],
                        'results' => [
                            ['label' => 'Total Return', 'formula' => 'returned - invested'],
                            ['label' => 'ROI', 'formula' => '( ( returned - invested ) / invested ) * 100'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Credit Cards',
                'calculators' => [
                    [
                        'name' => 'Buy Now vs. Wait Calculator',
                        'inputs' => [
                            ['id' => 'item_cost', 'label' => 'Item Cost', 'type' => 'number', 'default_value' => '1500'],
                            ['id' => 'current_savings', 'label' => 'Current Savings', 'type' => 'number', 'default_value' => '200'],
                            ['id' => 'monthly_savings', 'label' => 'Monthly Savings Amount', 'type' => 'number', 'default_value' => '150'],
                            ['id' => 'savings_rate', 'label' => 'Savings Annual Interest Rate (%)', 'type' => 'percentage', 'default_value' => '3'],
                            ['id' => 'borrow_rate', 'label' => 'Borrowing Annual Interest Rate (%)', 'type' => 'percentage', 'default_value' => '19'],
                            ['id' => 'min_payment', 'label' => 'Minimum Monthly Payment (if borrowing)', 'type' => 'number', 'default_value' => '60'],
                        ],
                        'results' => [
                            ['label' => 'Months to Save Up', 'formula' => '( item_cost - current_savings ) / monthly_savings'],
                            ['label' => 'Total Interest if Borrowed', 'formula' => '( ( item_cost - current_savings ) * ( borrow_rate / 100 / 12 ) * pow( 1 + ( borrow_rate / 100 / 12 ) , 24 ) ) / ( pow( 1 + ( borrow_rate / 100 / 12 ) , 24 ) - 1 ) * 24 - ( item_cost - current_savings )'],
                        ]
                    ],
                    [
                        'name' => 'Credit Card Balance Transfer Savings',
                        'inputs' => [
                            ['id' => 'balance', 'label' => 'Current Credit Card Balance', 'type' => 'number', 'default_value' => '5000'],
                            ['id' => 'current_apr', 'label' => 'Current Card APR (%)', 'type' => 'percentage', 'default_value' => '21.9'],
                            ['id' => 'monthly_pay', 'label' => 'Current Monthly Payment', 'type' => 'number', 'default_value' => '200'],
                            ['id' => 'transfer_apr', 'label' => 'Balance Transfer APR (%)', 'type' => 'percentage', 'default_value' => '0'],
                            ['id' => 'transfer_fee', 'label' => 'Balance Transfer Fee (%)', 'type' => 'percentage', 'default_value' => '3'],
                            ['id' => 'intro_period', 'label' => 'APR Introductory Period (months)', 'type' => 'number', 'default_value' => '12'],
                        ],
                        'results' => [
                            ['label' => 'Transfer Fee Amount', 'formula' => 'balance * ( transfer_fee / 100 )'],
                            ['label' => 'Interest on Old Card (12mo)', 'formula' => 'balance * ( current_apr / 100 )'],
                            ['label' => 'Total Savings (during intro)', 'formula' => '( balance * ( current_apr / 100 ) ) - ( balance * ( transfer_fee / 100 ) )'],
                        ]
                    ],
                    [
                        'name' => 'Credit Card Cash Advance',
                        'inputs' => [
                            ['id' => 'amount', 'label' => 'Cash Advance Amount', 'type' => 'number', 'default_value' => '500'],
                            ['id' => 'apr', 'label' => 'Cash Advance APR (%)', 'type' => 'percentage', 'default_value' => '25.9'],
                            ['id' => 'fee_percent', 'label' => 'Advance Fee Percentage (%)', 'type' => 'percentage', 'default_value' => '5'],
                            ['id' => 'payment', 'label' => 'Monthly Payment', 'type' => 'number', 'default_value' => '125'],
                        ],
                        'results' => [
                            ['label' => 'Advance Fee Amount', 'formula' => 'amount * ( fee_percent / 100 )'],
                            ['label' => 'Total Amount Borrowed', 'formula' => 'amount + ( amount * ( fee_percent / 100 ) )'],
                        ]
                    ],
                    [
                        'name' => 'Credit Card Minimum Payment',
                        'inputs' => [
                            ['id' => 'balance', 'label' => 'Credit Card Balance', 'type' => 'number', 'default_value' => '3000'],
                            ['id' => 'apr', 'label' => 'Annual Interest Rate (%)', 'type' => 'percentage', 'default_value' => '20'],
                            ['id' => 'min_percent', 'label' => 'Minimum Payment (%)', 'type' => 'percentage', 'default_value' => '2'],
                            ['id' => 'floor', 'label' => 'Minimum Payment Floor ($)', 'type' => 'number', 'default_value' => '15'],
                        ],
                        'results' => [
                            ['label' => 'Starting Minimum Payment', 'formula' => 'max( balance * ( min_percent / 100 ) , floor )'],
                            ['label' => 'Monthly Interest Cost', 'formula' => 'balance * ( apr / 100 / 12 )'],
                        ]
                    ],
                    [
                        'name' => 'Credit Card Payoff Calculator',
                        'inputs' => [
                            ['id' => 'balance', 'label' => 'Current Credit Card Balance', 'type' => 'number', 'default_value' => '1000'],
                            ['id' => 'apr', 'label' => 'Annual Interest Rate (%)', 'type' => 'percentage', 'default_value' => '18'],
                            ['id' => 'payment', 'label' => 'Monthly Payment ($)', 'type' => 'number', 'default_value' => '50'],
                        ],
                        'results' => [
                            ['label' => 'Months to Pay Off', 'formula' => 'log( payment / ( payment - balance * ( apr / 100 / 12 ) ) ) / log( 1 + ( apr / 100 / 12 ) )'],
                            ['label' => 'Total Interest Paid', 'formula' => '( payment * ( log( payment / ( payment - balance * ( apr / 100 / 12 ) ) ) / log( 1 + ( apr / 100 / 12 ) ) ) ) - balance'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Mortgage',
                'calculators' => [
                    [
                        'name' => 'How Much Can I Afford For A Home?',
                        'inputs' => [
                            ['id' => 'annual_income', 'label' => 'Total Annual Income', 'type' => 'number', 'default_value' => '150000'],
                            ['id' => 'monthly_debt', 'label' => 'Monthly Debt Payments', 'type' => 'number', 'default_value' => '800'],
                            ['id' => 'down_payment', 'label' => 'Down Payment', 'type' => 'number', 'default_value' => '50000'],
                            ['id' => 'interest_rate', 'label' => 'Interest Rate', 'type' => 'percentage', 'default_value' => '7'],
                        ],
                        'results' => [
                            ['label' => 'Maximum Home Price', 'formula' => '( ( annual_income * 0.36 - monthly_debt ) / ( ( interest_rate / 100 / 12 ) * pow( 1 + ( interest_rate / 100 / 12 ) , 360 ) / ( pow( 1 + ( interest_rate / 100 / 12 ) , 360 ) - 1 ) ) ) + down_payment'],
                            ['label' => 'Monthly Payment (P&I)', 'formula' => 'annual_income * 0.36 - monthly_debt'],
                        ]
                    ],
                    [
                        'name' => 'Mortgage Calculator',
                        'inputs' => [
                            ['id' => 'price', 'label' => 'Home Price', 'type' => 'number', 'default_value' => '400000'],
                            ['id' => 'down', 'label' => 'Down Payment', 'type' => 'number', 'default_value' => '80000'],
                            ['id' => 'term', 'label' => 'Loan Term (Years)', 'type' => 'number', 'default_value' => '30'],
                            ['id' => 'rate', 'label' => 'Interest Rate (%)', 'type' => 'percentage', 'default_value' => '6'],
                        ],
                        'results' => [
                            ['label' => 'Monthly P&I Payment', 'formula' => '( ( price - down ) * ( rate / 100 / 12 ) * pow( 1 + ( rate / 100 / 12 ) , term * 12 ) ) / ( pow( 1 + ( rate / 100 / 12 ) , term * 12 ) - 1 )'],
                            ['label' => 'Total Interest Paid', 'formula' => '( ( ( price - down ) * ( rate / 100 / 12 ) * pow( 1 + ( rate / 100 / 12 ) , term * 12 ) ) / ( pow( 1 + ( rate / 100 / 12 ) , term * 12 ) - 1 ) * term * 12 ) - ( price - down )'],
                        ]
                    ],
                    [
                        'name' => 'Is Refinancing My Mortgage a Good Idea?',
                        'inputs' => [
                            ['id' => 'balance', 'label' => 'Current Balance', 'type' => 'number', 'default_value' => '250000'],
                            ['id' => 'current_rate', 'label' => 'Current Rate (%)', 'type' => 'percentage', 'default_value' => '6.5'],
                            ['id' => 'new_rate', 'label' => 'New Rate (%)', 'type' => 'percentage', 'default_value' => '5.5'],
                            ['id' => 'costs', 'label' => 'Refinancing Costs ($)', 'type' => 'number', 'default_value' => '5000'],
                        ],
                        'results' => [
                            ['label' => 'Monthly Savings', 'formula' => '( ( balance * ( current_rate / 100 / 12 ) * pow( 1 + ( current_rate / 100 / 12 ) , 360 ) ) / ( pow( 1 + ( current_rate / 100 / 12 ) , 360 ) - 1 ) ) - ( ( balance * ( new_rate / 100 / 12 ) * pow( 1 + ( new_rate / 100 / 12 ) , 360 ) ) / ( pow( 1 + ( new_rate / 100 / 12 ) , 360 ) - 1 ) )'],
                            ['label' => 'Months to Break Even', 'formula' => 'costs / ( ( ( balance * ( current_rate / 100 / 12 ) * pow( 1 + ( current_rate / 100 / 12 ) , 360 ) ) / ( pow ( 1 + ( current_rate / 100 / 12 ) , 360 ) - 1 ) ) - ( ( balance * ( new_rate / 100 / 12 ) * pow( 1 + ( new_rate / 100 / 12 ) , 360 ) ) / ( pow ( 1 + ( new_rate / 100 / 12 ) , 360 ) - 1 ) ) )'],
                        ]
                    ],
                    [
                        'name' => 'Adjustable Rate Mortgage (ARM) Calculator',
                        'inputs' => [
                            ['id' => 'amount', 'label' => 'Loan Amount', 'type' => 'number', 'default_value' => '300000'],
                            ['id' => 'rate', 'label' => 'Initial Interest Rate (%)', 'type' => 'percentage', 'default_value' => '5'],
                            ['id' => 'term', 'label' => 'Loan Term (Years)', 'type' => 'number', 'default_value' => '30'],
                            ['id' => 'increase', 'label' => 'Expected Interest Increase (%)', 'type' => 'percentage', 'default_value' => '1'],
                        ],
                        'results' => [
                            ['label' => 'Initial Monthly Payment', 'formula' => '( amount * ( rate / 100 / 12 ) * pow( 1 + ( rate / 100 / 12 ) , term * 12 ) ) / ( pow( 1 + ( rate / 100 / 12 ) , term * 12 ) - 1 )'],
                            ['label' => 'Estimated Max Payment', 'formula' => '( amount * ( ( rate + increase ) / 100 / 12 ) * pow( 1 + ( ( rate + increase ) / 100 / 12 ) , term * 12 ) ) / ( pow( 1 + ( ( rate + increase ) / 100 / 12 ) , term * 12 ) - 1 )'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Retirement',
                'calculators' => [
                    [
                        'name' => 'Are My Current Retirement Savings Sufficient?',
                        'inputs' => [
                            ['id' => 'current_savings', 'label' => 'Current Retirement Savings', 'type' => 'number', 'default_value' => '50000'],
                            ['id' => 'desired_income', 'label' => 'Desired Annual Income in Retirement', 'type' => 'number', 'default_value' => '80000'],
                            ['id' => 'pension', 'label' => 'Expected Annual Pension Income', 'type' => 'number', 'default_value' => '0'],
                            ['id' => 'social_security', 'label' => 'Expected Annual Social Security Income', 'type' => 'number', 'default_value' => '25000'],
                            ['id' => 'retirement_age', 'label' => 'Retirement Age', 'type' => 'number', 'default_value' => '65'],
                            ['id' => 'current_age', 'label' => 'Current Age', 'type' => 'number', 'default_value' => '35'],
                            ['id' => 'return_rate', 'label' => 'Expected Annual Return on Savings (%)', 'type' => 'percentage', 'default_value' => '6'],
                            ['id' => 'inflation', 'label' => 'Annual Inflation Rate (%)', 'type' => 'percentage', 'default_value' => '3'],
                        ],
                        'results' => [
                            ['label' => 'Income Shortfall to Cover', 'formula' => 'max( 0 , desired_income - pension - social_security )'],
                            ['label' => 'Estimated Fund Needed at Retirement', 'formula' => 'max( 0 , desired_income - pension - social_security ) * 20'],
                        ]
                    ],
                    [
                        'name' => 'When Should I Start Saving for Retirement?',
                        'inputs' => [
                            ['id' => 'current_age', 'label' => 'Current Age', 'type' => 'number', 'default_value' => '30'],
                            ['id' => 'retirement_age', 'label' => 'Desired Retirement Age', 'type' => 'number', 'default_value' => '65'],
                            ['id' => 'monthly_savings', 'label' => 'Monthly Savings ($)', 'type' => 'number', 'default_value' => '500'],
                            ['id' => 'rate', 'label' => 'Expected Annual Interest Rate (%)', 'type' => 'percentage', 'default_value' => '7'],
                        ],
                        'results' => [
                            ['label' => 'Future Value of Savings', 'formula' => 'monthly_savings * ( pow( 1 + ( rate / 100 / 12 ) , ( retirement_age - current_age ) * 12 ) - 1 ) / ( rate / 100 / 12 )'],
                            ['label' => 'Interest Earned', 'formula' => '( monthly_savings * ( pow( 1 + ( rate / 100 / 12 ) , ( retirement_age - current_age ) * 12 ) - 1 ) / ( rate / 100 / 12 ) ) - ( monthly_savings * ( retirement_age - current_age ) * 12 )'],
                        ]
                    ],
                    [
                        'name' => '401(k) Future Value Calculator',
                        'inputs' => [
                            ['id' => 'current_balance', 'label' => 'Current 401(k) Balance', 'type' => 'number', 'default_value' => '20000'],
                            ['id' => 'annual_contribution', 'label' => 'Your annual contribution', 'type' => 'number', 'default_value' => '10000'],
                            ['id' => 'return_rate', 'label' => 'Expected annual return rate', 'type' => 'percentage', 'default_value' => '7'],
                            ['id' => 'years', 'label' => 'Years until retirement', 'type' => 'number', 'default_value' => '30'],
                        ],
                        'results' => [
                            ['label' => 'Estimated Final 401k Balance', 'formula' => '( current_balance * pow( 1 + ( return_rate / 100 ) , years ) ) + ( annual_contribution * ( pow( 1 + ( return_rate / 100 ) , years ) - 1 ) / ( return_rate / 100 ) )'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Tax',
                'calculators' => [
                    [
                        'name' => 'Capital Gains/Loss Tax Estimator',
                        'inputs' => [
                            ['id' => 'long_term', 'label' => 'Long Term Capital Gains ($)', 'type' => 'number', 'default_value' => '5000'],
                            ['id' => 'short_term', 'label' => 'Short Term Capital Gains ($)', 'type' => 'number', 'default_value' => '1000'],
                            ['id' => 'income', 'label' => 'Other Taxable Income ($)', 'type' => 'number', 'default_value' => '60000'],
                        ],
                        'results' => [
                            ['label' => 'Estimated Tax on Gains', 'formula' => '( long_term * 0.15 ) + ( short_term * 0.22 )'],
                        ]
                    ],
                    [
                        'name' => 'Marginal vs. Effective Tax Rate',
                        'inputs' => [
                            ['id' => 'income', 'label' => 'Total Taxable Income', 'type' => 'number', 'default_value' => '85000'],
                        ],
                        'results' => [
                            ['label' => 'Estimated Federal Tax', 'formula' => 'income * 0.18'],
                            ['label' => 'Effective Tax Rate', 'formula' => '18'],
                            ['label' => 'Marginal Tax Rate', 'formula' => '22'],
                        ]
                    ],
                    [
                        'name' => 'Self-Employment Tax Owed',
                        'inputs' => [
                            ['id' => 'net_income', 'label' => 'Annual net self-employment income', 'type' => 'number', 'default_value' => '50000'],
                        ],
                        'results' => [
                            ['label' => 'Social Security Tax', 'formula' => 'net_income * 0.9235 * 0.124'],
                            ['label' => 'Medicare Tax', 'formula' => 'net_income * 0.9235 * 0.029'],
                            ['label' => 'Total Self Employment Tax', 'formula' => '( net_income * 0.9235 * 0.124 ) + ( net_income * 0.9235 * 0.029 )'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Investment',
                'calculators' => [
                    [
                        'name' => 'Certificate of Deposit (CD) Calculator',
                        'inputs' => [
                            ['id' => 'deposit', 'label' => 'Initial Deposit', 'type' => 'number', 'default_value' => '10000'],
                            ['id' => 'months', 'label' => 'Term Length (Months)', 'type' => 'number', 'default_value' => '24'],
                            ['id' => 'rate', 'label' => 'Interest Rate (%)', 'type' => 'percentage', 'default_value' => '4.5'],
                        ],
                        'results' => [
                            ['label' => 'Final Balance', 'formula' => 'deposit * pow( 1 + ( rate / 100 / 12 ) , months )'],
                            ['label' => 'Total Interest Earned', 'formula' => '( deposit * pow( 1 + ( rate / 100 / 12 ) , months ) ) - deposit'],
                        ]
                    ],
                    [
                        'name' => 'Stock Option Calculator',
                        'inputs' => [
                            ['id' => 'options', 'label' => 'Number of Options Granted', 'type' => 'number', 'default_value' => '1000'],
                            ['id' => 'exercise_price', 'label' => 'Exercise Price Per Share', 'type' => 'number', 'default_value' => '15'],
                            ['id' => 'current_price', 'label' => 'Current Share Price', 'type' => 'number', 'default_value' => '25'],
                            ['id' => 'growth', 'label' => 'Expected Annual Stock Growth (%)', 'type' => 'percentage', 'default_value' => '8'],
                            ['id' => 'years', 'label' => 'Years Until Sale', 'type' => 'number', 'default_value' => '5'],
                        ],
                        'results' => [
                            ['label' => 'Future Share Price', 'formula' => 'current_price * pow( 1 + ( growth / 100 ) , years )'],
                            ['label' => 'Total Gross Gain', 'formula' => '( current_price * pow( 1 + ( growth / 100 ) , years ) - exercise_price ) * options'],
                        ]
                    ],
                    [
                        'name' => 'Rule of 72 for Investing Calculator',
                        'inputs' => [
                            ['id' => 'interest_rate', 'label' => 'Annual Interest Rate', 'type' => 'percentage', 'default_value' => '7'],
                        ],
                        'results' => [
                            ['label' => 'Years to Double Your Money', 'formula' => '72 / interest_rate'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Savings',
                'calculators' => [
                    [
                        'name' => 'Simple Savings Calculator',
                        'inputs' => [
                            ['id' => 'initial_deposit', 'label' => 'Initial Deposit', 'type' => 'number', 'default_value' => '1000'],
                            ['id' => 'monthly_contribution', 'label' => 'Monthly Contribution', 'type' => 'number', 'default_value' => '100'],
                            ['id' => 'years', 'label' => 'Years to Save', 'type' => 'number', 'default_value' => '10'],
                            ['id' => 'apy', 'label' => 'Annual Percentage Yield (APY)', 'type' => 'percentage', 'default_value' => '4'],
                        ],
                        'results' => [
                            ['label' => 'Total Savings Balance', 'formula' => '( initial_deposit * pow( 1 + ( apy / 100 / 12 ) , years * 12 ) ) + ( monthly_contribution * ( pow( 1 + ( apy / 100 / 12 ) , years * 12 ) - 1 ) / ( apy / 100 / 12 ) )'],
                            ['label' => 'Total Interest Earned', 'formula' => '( ( initial_deposit * pow( 1 + ( apy / 100 / 12 ) , years * 12 ) ) + ( monthly_contribution * ( pow( 1 + ( apy / 100 / 12 ) , years * 12 ) - 1 ) / ( apy / 100 / 12 ) ) ) - ( initial_deposit + ( monthly_contribution * years * 12 ) )'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Insurance',
                'calculators' => [
                    [
                        'name' => 'How Much Life Insurance Do I Need?',
                        'inputs' => [
                            ['id' => 'income', 'label' => 'Annual Income Replacement Needed', 'type' => 'number', 'default_value' => '50000'],
                            ['id' => 'years', 'label' => 'Number of Years Needed', 'type' => 'number', 'default_value' => '20'],
                            ['id' => 'debt', 'label' => 'Total Debt (Mortgage, etc.)', 'type' => 'number', 'default_value' => '200000'],
                            ['id' => 'education', 'label' => 'Children Education Fund', 'type' => 'number', 'default_value' => '100000'],
                            ['id' => 'savings', 'label' => 'Current Savings & Life Insurance', 'type' => 'number', 'default_value' => '50000'],
                        ],
                        'results' => [
                            ['label' => 'Total Support Needs', 'formula' => '( income * years ) + debt + education'],
                            ['label' => 'Additional Insurance Needed', 'formula' => '( income * years ) + debt + education - savings'],
                        ]
                    ],
                    [
                        'name' => 'Future Value of Annuity',
                        'inputs' => [
                            ['id' => 'payment', 'label' => 'Periodic Payment Amount', 'type' => 'number', 'default_value' => '500'],
                            ['id' => 'years', 'label' => 'Number of Years', 'type' => 'number', 'default_value' => '20'],
                            ['id' => 'rate', 'label' => 'Annual Interest Rate (%)', 'type' => 'percentage', 'default_value' => '6'],
                        ],
                        'results' => [
                            ['label' => 'Future Value of Annuity', 'formula' => 'payment * 12 * ( pow( 1 + ( rate / 100 / 12 ) , years * 12 ) - 1 ) / ( rate / 100 / 12 )'],
                            ['label' => 'Total Contributions', 'formula' => 'payment * 12 * years'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Kids',
                'calculators' => [
                    [
                        'name' => 'Importance of Compound Interest',
                        'inputs' => [
                            ['id' => 'weekly', 'label' => 'Weekly Savings ($)', 'type' => 'number', 'default_value' => '10'],
                            ['id' => 'years', 'label' => 'Years to Invest', 'type' => 'number', 'default_value' => '30'],
                            ['id' => 'rate', 'label' => 'Annual Interest Rate (%)', 'type' => 'percentage', 'default_value' => '7'],
                        ],
                        'results' => [
                            ['label' => 'Future Value of Savings', 'formula' => 'weekly * 52 * ( pow( 1 + ( rate / 100 ) , years ) - 1 ) / ( rate / 100 )'],
                            ['label' => 'Total Interest Earned', 'formula' => '( weekly * 52 * ( pow( 1 + ( rate / 100 ) , years ) - 1 ) / ( rate / 100 ) ) - ( weekly * 52 * years )'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Loans',
                'calculators' => [
                    [
                        'name' => 'Should I Consolidate My Debt?',
                        'inputs' => [
                            ['id' => 'debt', 'label' => 'Total Amount of Current Debt', 'type' => 'number', 'default_value' => '25000'],
                            ['id' => 'old_rate', 'label' => 'Average Interest Rate (%)', 'type' => 'percentage', 'default_value' => '18'],
                            ['id' => 'new_rate', 'label' => 'Consolidation Loan Interest Rate (%)', 'type' => 'percentage', 'default_value' => '10'],
                            ['id' => 'term', 'label' => 'Loan Term (Months)', 'type' => 'number', 'default_value' => '48'],
                        ],
                        'results' => [
                            ['label' => 'Monthly Savings', 'formula' => '( ( debt * ( old_rate / 100 / 12 ) * pow( 1 + ( old_rate / 100 / 12 ) , term ) ) / ( pow( 1 + ( old_rate / 100 / 12 ) , term ) - 1 ) ) - ( ( debt * ( new_rate / 100 / 12 ) * pow( 1 + ( new_rate / 100 / 12 ) , term ) ) / ( pow( 1 + ( new_rate / 100 / 12 ) , term ) - 1 ) )'],
                            ['label' => 'Total Interest Savings', 'formula' => '( ( ( debt * ( old_rate / 100 / 12 ) * pow( 1 + ( old_rate / 100 / 12 ) , term ) ) / ( pow ( 1 + ( old_rate / 100 / 12 ) , term ) - 1 ) ) * term ) - ( ( ( debt * ( new_rate / 100 / 12 ) * pow ( 1 + ( new_rate / 100 / 12 ) , term ) ) / ( pow ( 1 + ( new_rate / 100 / 12 ) , term ) - 1 ) ) * term )'],
                        ]
                    ],
                    [
                        'name' => 'Student Loan Repayment',
                        'inputs' => [
                            ['id' => 'balance', 'label' => 'Loan Balance', 'type' => 'number', 'default_value' => '35000'],
                            ['id' => 'rate', 'label' => 'Interest Rate (%)', 'type' => 'percentage', 'default_value' => '5.5'],
                        ],
                        'results' => [
                            ['label' => 'Standard Monthly Payment (10yr)', 'formula' => '( balance * ( rate / 100 / 12 ) * pow( 1 + ( rate / 100 / 12 ) , 120 ) ) / ( pow ( 1 + ( rate / 100 / 12 ) , 120 ) - 1 )'],
                            ['label' => 'Extended Monthly Payment (25yr)', 'formula' => '( balance * ( rate / 100 / 12 ) * pow( 1 + ( rate / 100 / 12 ) , 300 ) ) / ( pow ( 1 + ( rate / 100 / 12 ) , 300 ) - 1 )'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Paycheck',
                'calculators' => [
                    [
                        'name' => 'Hourly to Salary Converter',
                        'inputs' => [
                            ['id' => 'hourly', 'label' => 'Hourly Wage ($)', 'type' => 'number', 'default_value' => '25'],
                            ['id' => 'hours', 'label' => 'Hours Per Week', 'type' => 'number', 'default_value' => '40'],
                            ['id' => 'weeks', 'label' => 'Weeks Per Year', 'type' => 'number', 'default_value' => '52'],
                        ],
                        'results' => [
                            ['label' => 'Annual Salary', 'formula' => 'hourly * hours * weeks'],
                            ['label' => 'Monthly Salary', 'formula' => 'hourly * hours * weeks / 12'],
                        ]
                    ],
                    [
                        'name' => '401(k) Contribution Impact Calculator',
                        'inputs' => [
                            ['id' => 'gross', 'label' => 'Gross Pay Per Paycheck', 'type' => 'number', 'default_value' => '3000'],
                            ['id' => 'contrib', 'label' => 'Your 401(k) Contribution Rate (%)', 'type' => 'percentage', 'default_value' => '6'],
                            ['id' => 'match', 'label' => 'Employer Match Rate (%)', 'type' => 'percentage', 'default_value' => '3'],
                            ['id' => 'limit', 'label' => 'Employer Match Limit (%)', 'type' => 'percentage', 'default_value' => '5'],
                        ],
                        'results' => [
                            ['label' => 'Your Contribution Per Paycheck', 'formula' => 'gross * ( contrib / 100 )'],
                            ['label' => 'Employer Match Per Paycheck', 'formula' => 'gross * ( min( contrib , limit ) * ( match / 100 ) / limit )'],
                            ['label' => 'Total Per Paycheck', 'formula' => 'gross * ( contrib / 100 ) + ( gross * ( min( contrib , limit ) * ( match / 100 ) / limit ) )'],
                        ]
                    ],
                ]
            ],
        ];

        foreach ($data as $index => $catData) {
            $category = FinancialCalculatorCategory::updateOrCreate(
                ['slug' => Str::slug($catData['name'])],
                [
                    'name' => $catData['name'],
                    'order_index' => $index,
                    'is_active' => true,
                ]
            );

            foreach ($catData['calculators'] as $calc) {
                FinancialCalculator::updateOrCreate(
                    ['slug' => Str::slug($calc['name'])],
                    [
                        'category_id' => $category->id,
                        'name' => $calc['name'],
                        'icon' => 'Calculator',
                        'inputs' => $calc['inputs'] ?? [
                            ['id' => 'sample_input', 'label' => 'Sample Input', 'type' => 'number', 'default_value' => '100']
                        ],
                        'results' => $calc['results'] ?? [
                            ['label' => 'Sample Result', 'formula' => 'sample_input * 1.1']
                        ],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
