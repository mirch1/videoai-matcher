<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['denumire' => 'Elden Ring',           'categorie' => 'PC Digital'],
            ['denumire' => 'Cyberpunk 2077',        'categorie' => 'PC Digital'],
            ['denumire' => 'Red Dead Redemption 2', 'categorie' => 'PC Digital'],
            ['denumire' => 'The Witcher 3',         'categorie' => 'PC Digital'],
            ['denumire' => 'God of War',            'categorie' => 'PC Digital'],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(['denumire' => $p['denumire']], $p);
        }
    }
}
