# VideoAI Matcher

Aplicație Laravel care caută automat trailerele produselor pe YouTube și le verifică cu AI (Groq/LLaMA).

## Cerințe sistem

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
```

Într-un terminal separat (pentru procesare async):

```bash
php artisan queue:work
```

## Configurare `.env`

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

# https://console.cloud.google.com → YouTube Data API v3 → Credentials → API Key
YOUTUBE_API_KEY=AIza_cheia_ta_aici

# https://console.groq.com → API Keys → Create API Key
XAI_API_KEY=gsk_cheia_ta_aici
XAI_BASE_URL=https://api.groq.com/openai/v1
XAI_MODEL=llama-3.3-70b-versatile

QUEUE_CONNECTION=database
CACHE_STORE=database
```

## Import date inițiale

```bash
php artisan db:seed --class=ProductSeeder
```

Seederul populează automat câteva produse demo (Elden Ring, Cyberpunk 2077 etc.).
Pentru import din fișier Excel, folosește pagina **Import** din UI.

## Rulare teste

```bash
php artisan test --filter=VideoAiMatcherTest
```

## Structura proiect

| Clasă | Responsabilitate |
|---|---|
| `YouTubeClient` | Apeluri YouTube Data API v3, cache, rate limiting, retry |
| `AiVerifier` | Integrare Groq/LLaMA — scor acuratețe + decizie finală |
| `ProductService` | Orchestrare căutare + verificare AI |
| `SearchYoutubeAndVerifyJob` | Job async queue — procesare în fundal |
| `ProductRepository` | Acces date, query-uri produse |

## Flux aplicație

1. Lista produse cu filtre (fără video / search după denumire)
2. Click **Caută video** → pornește job async în queue
3. YouTube API returnează top 5 candidați
4. AI (Groq/LLaMA) alege cel mai bun match cu scor 0–100
5. Dacă scorul ≥ 75 → video salvat automat în DB
6. UI afișează candidații, verdictul AI și permite override manual
