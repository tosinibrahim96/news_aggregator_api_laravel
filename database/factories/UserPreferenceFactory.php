<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_id' => null,
            'category_id' => null,
            'author_name' => null,
        ];
    }

    /**
     * Indicate that the preference is for a source
     */
    public function forSource(): self
    {
        return $this->state(fn (array $attributes) => [
            'source_id' => Source::factory(),
        ]);
    }

    /**
     * Indicate that the preference is for a category
     */
    public function forCategory(): self
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => Category::factory(),
        ]);
    }

    /**
     * Indicate that the preference is for an author
     */
    public function forAuthor(): self
    {
        return $this->state(fn (array $attributes) => [
            'author_name' => fake()->name(),
        ]);
    }
}
