<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'denumire'       => $this->faker->words(3, true),
            'categorie'      => 'PC Digital',
            'youtube_url'    => null,
            'ai_verified'    => false,
            'ai_accuracy'    => null,
            'ai_explanation' => null,
        ];
    }
}
