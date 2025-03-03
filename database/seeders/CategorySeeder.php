<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Income categories
        $incomeCategories = [
            ['name' => 'Gaji Fulltime', 'type' => 'income'],
            ['name' => 'Gaji Freelance', 'type' => 'income'],
            ['name' => 'Bonus / Hadiah', 'type' => 'income'],
            ['name' => 'Hutang', 'type' => 'income'],
        ];

        // Expense categories
        $expenseCategories = [
            ['name' => 'Makan & Minum', 'type' => 'expense'],
            ['name' => 'Kebutuhan Primer Bulanan', 'type' => 'expense'],
            ['name' => 'Kebutuhan Sekunder', 'type' => 'expense'],
            ['name' => 'Belanja Olshop', 'type' => 'expense'],
            ['name' => 'Piutang', 'type' => 'expense'],
        ];

        // Combine all categories
        $categories = array_merge($incomeCategories, $expenseCategories);

        // Insert categories
        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                $category
            );
        }
    }
}