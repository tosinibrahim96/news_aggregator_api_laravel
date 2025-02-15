<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    private array $categories = [
        'Technology',
        'Science',
        'Health',
        'Sports',
        'Entertainment',
        'World News'
    ];

    /**
     * Seed the categories table with predefined categories.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->categories as $category) {
            Category::firstOrCreate([
                'name' => $category,
            ], [
                'slug' => str($category)->slug(),
            ]);
        }
    }
}