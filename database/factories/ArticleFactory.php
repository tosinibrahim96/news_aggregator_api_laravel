<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

     /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();
        
        return [
            'source_id' => Source::factory(),
            'category_id' => Category::factory(),
            'external_id' => $this->faker->uuid(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(3, true),
            'author' => $this->faker->name(),
            'url' => $this->faker->url(),
            'image_url' => $this->faker->imageUrl(),
            'published_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
