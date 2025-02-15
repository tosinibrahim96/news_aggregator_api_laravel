<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SourceFactory extends Factory
{
    protected $model = Source::class;

     /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'base_url' => $this->faker->url(),
            'api_key' => $this->faker->uuid(),
            'is_active' => true,
            'last_synced_at' => $this->faker->optional()->dateTime(),
        ];
    }

    /**
     * Indicate that the source is inactive.
     * 
     * @return Factory
     */
    public function inactive(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
