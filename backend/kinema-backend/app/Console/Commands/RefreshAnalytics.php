<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Uso:
 *   # Token por entorno (recomendado, no queda en historial):
 *   KINEMA_API_TOKEN=xxx php artisan analytics:refresh
 *
 *   # O explícito:
 *   php artisan analytics:refresh --token=xxx --python=/home/user/kinema-venv/bin/python
 *
 * Cron sugerido (usuario, 1 vez al día):
 *   0 4 * * * cd ~/kinema/backend/kinema-backend && ./vendor/bin/sail artisan analytics:refresh >> storage/logs/analytics-refresh.log 2>&1
 *
 * Requiere el venv Python de backend/python (ver requirements.txt):
 *   python3 -m venv ~/kinema-venv && ~/kinema-venv/bin/pip install torch \
 *     --index-url https://download.pytorch.org/whl/cpu && \
 *     ~/kinema-venv/bin/pip install -r backend/python/requirements.txt
 */
class RefreshAnalytics extends Command
{
    protected $signature = 'analytics:refresh
        {--token= : Token Sanctum con acceso a GET /api/reviews (o env KINEMA_API_TOKEN)}
        {--python=python3 : Binario python del venv}
        {--api-url= : Base del API (defecto APP_URL)}';

    protected $description = 'Ejecuta el pipeline de sentimiento (Python) y actualiza el JSON del frontend';

    public function handle()
    {
        $token = $this->option('token') ?: env('KINEMA_API_TOKEN');
        if (!$token) {
            $this->error('Falta el token: pasa --token=xxx o exporta KINEMA_API_TOKEN.');
            return 1;
        }

        $script = base_path('../python/src/sentiment.py');
        if (!file_exists($script)) {
            $this->error("No se encontró sentiment.py en: {$script}");
            return 1;
        }

        $out = storage_path('app/private/kinema_sentiment_data.json');
        $apiUrl = $this->option('api-url') ?: config('app.url');

        $process = new Process(
            [$this->option('python'), $script, '--out', $out, '--api-url', $apiUrl],
            timeout: 1800
        );
        $process->setEnv(array_merge(getenv(), [
            'KINEMA_API_TOKEN' => $token,
            'KINEMA_API_URL' => $apiUrl,
        ]));

        $this->info('Analizando reseñas (puede tardar varios minutos la primera vez)...');
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        if (!$process->isSuccessful()) {
            $this->error('Falló el análisis de sentimiento.');
            return 1;
        }

        $this->info("✅ JSON actualizado: {$out}");
        return 0;
    }
}
