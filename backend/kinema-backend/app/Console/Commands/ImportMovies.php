<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use App\Models\Vibe;
use Illuminate\Support\Facades\DB;

class ImportMovies extends Command
{
    // Definimos cómo se llamará el comando en la terminal y qué argumentos recibe
    protected $signature = 'app:import-movies {file : Ruta absoluta o relativa al archivo CSV}';
    protected $description = 'Importa películas, vibras y vectores desde el CSV generado por el pipeline de ML';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ El archivo no existe en la ruta: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file); // Leemos la primera fila (los encabezados)

        $this->info('🚀 Iniciando la inyección de datos de ML en PostgreSQL...');

        // Usamos una transacción para que, si algo falla, no guarde datos a medias
        DB::beginTransaction();

        try {
            $count = 0;
            while (($row = fgetcsv($file)) !== false) {
                $data = array_combine($headers, $row);

                // 1. Encontrar o crear la "Vibra" (Cluster Name)
                $vibe = Vibe::firstOrCreate(
                    ['name' => $data['cluster_name']]
                );

                // 2. Limpiar el formato del vector
                // Python guarda el vector como un string "[0.123, -0.456...]". Lo convertimos a un array real de PHP.
                $vectorString = str_replace(['[', ']', ' '], '', $data['embedding']);
                $vectorArray = explode(',', $vectorString);
                $vectorArray = array_map('floatval', $vectorArray);

                // 3. Insertar la película (o actualizarla si el tmdb_id ya existe)
                Movie::updateOrCreate(
                    ['tmdb_id' => $data['tmdb_id']],
                    [
                        'title' => $data['title'],
                        'overview' => $data['metadata_text'] ?? null,
                        'genres' => $data['genres'] ?? null,
                        'vibe_id' => $vibe->id,
                        'x_coordinate' => $data['x'],
                        'y_coordinate' => $data['y'],
                        'embedding' => $vectorArray,
                    ]
                );

                $count++;
            }

            DB::commit();
            $this->info("✅ ¡Éxito! Se inyectaron {$count} películas con sus respectivos vectores espaciales.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error crítico durante la importación: ' . $e->getMessage());
        }

        fclose($file);
    }
}
