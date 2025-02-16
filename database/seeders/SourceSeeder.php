<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    /**
     * Array of news sources with their configurations
     *
     * @var array<int, array<string, mixed>>
     */
    private array $sources = [
        [
            'name' => 'The Guardian',
            'base_url' => 'https://content.guardianapis.com',
            'category_mapping' => [
                'technology' => 'technology',
                'science' => 'science',
                'health' => 'healthcare',
                'sports' => 'sport',
                'entertainment' => 'culture',
                'world-news' => 'world',
            ],
        ],
        [
            'name' => 'NewsAPI',
            'base_url' => 'https://newsapi.org/v2',
            'category_mapping' => [
                'technology' => 'technology',
                'science' => 'science',
                'health' => 'health',
                'sports' => 'sports',
                'entertainment' => 'entertainment',
                'world-news' => 'world',
            ],
        ],
        [
            'name' => 'New York Times',
            'base_url' => 'https://api.nytimes.com/svc',
            'category_mapping' => [
                'technology' => 'technology',
                'science' => 'science',
                'health' => 'health',
                'sports' => 'sports',
                'entertainment' => 'arts',
                'world-news' => 'world',
            ],
        ],
    ];

    /**
     * Seed the sources table with predefined sources.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->sources as $source) {
            Source::firstOrCreate(
                ['name' => $source['name']],
                [
                    'slug' => str($source['name'])->slug(),
                    'base_url' => $source['base_url'],
                    'category_mapping' => $source['category_mapping'],
                    'is_active' => true,
                ]
            );
        }
    }
}