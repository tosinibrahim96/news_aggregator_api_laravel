<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    private array $sources = [

        [
            'name' => 'NewsAPI',
            'base_url' => 'https://newsapi.org/v2',
        ],
        [
            'name' => 'The Guardian',
            'base_url' => 'https://content.guardianapis.com',
        ],
        [
            'name' => 'New York Times',
            'base_url' => 'https://api.nytimes.com/svc',
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
                    'is_active' => true,
                ]
            );
        }
    }
}