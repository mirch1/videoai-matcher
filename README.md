# VideoAI Matcher

Aplica?ie Laravel care cauta automat trailerele produselor pe YouTube ?i le verifica cu AI (Groq/LLaMA).

## Cerin?e sistem

- PHP 8.2+
- MySQL / MariaDB
- Composer
- Cont YouTube Data API v3
- Cont Groq (groq.com) pentru AI

## Instalare

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
# terminal separat:
php artisan queue:work
```

## Configurare .env

```env
APP_NAME="VideoAI Matcher"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=videoai
DB_USERNAME=root
DB_PASSWORD=

# https://console.cloud.google.com ? YouTube Data API v3 ? Credentials ? API Key
YOUTUBE_API_KEY=AIza_cheia_ta_aici

# https://console.groq.com ? API Keys ? Create API Key
XAI_API_KEY=gsk_cheia_ta_aici
XAI_BASE_URL=https://api.groq.com/openai/v1
XAI_MODEL=llama-3.3-70b-versatile

QUEUE_CONNECTION=database
CACHE_STORE=database
```

## Rulare teste

```bash
php artisan test --filter=VideoAiMatcherTest
```

## Structura proiect

# ProductSeeder.php
New-Item -Path "database\seeders\ProductSeeder.php" -ItemType File -Force
Set-Content -Path "database\seeders\ProductSeeder.php" -Value @'
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
