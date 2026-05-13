<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class ImportProducts extends Command
{
    protected $signature = 'app:import-products {file=products.csv}';
    protected $description = 'Importa lista de produse dintr-un fisier CSV in baza de date';

    public function handle()
    {
        $fileName = $this->argument('file');
        $filePath = base_path($fileName);

        if (!file_exists($filePath)) {
            $this->error("Fisierul nu a fost gasit: {$filePath}");
            return Command::FAILURE;
        }

        $file = fopen($filePath, 'r');
        fgetcsv($file); // Ignoram header-ul

        $count = 0;
        $this->info('Incepem importul produselor...');

        while (($row = fgetcsv($file)) !== false) {

            // PROGRAMARE DEFENSIVA:
            // Daca randul nu are macar 3 coloane (id, denumire, categorie) sau id-ul e gol, il ignoram.
            if (count($row) < 3 || empty(trim($row[0]))) {
                continue;
            }

            Product::updateOrCreate(
                ['id' => trim($row[0])],
                [
                    'denumire' => trim($row[1]),
                    'categorie' => trim($row[2]),
                    // Verificam in mod sigur daca exista indexul 3/4 si daca nu sunt goale
                    'youtube_url' => isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : null,
                    'youtube_found_at' => isset($row[4]) && trim($row[4]) !== '' ? trim($row[4]) : null,
                ]
            );
            $count++;
        }

        fclose($file);

        $this->info("Import finalizat cu succes! Am adaugat/actualizat {$count} produse valide.");

        return Command::SUCCESS;
    }
}
