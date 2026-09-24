# Kinema

Red social de cine con motor de descubrimiento: catálogo con vibras (clusters ML),
reseñas, listas por usuario con slugs, follows + feed, recomendaciones por
pgvector y analítica (sentimiento NLP, heatmaps, radares, taquilla TMDB).

## Stack

| Capa | Tecnología |
|---|---|
| Frontend | Astro 7 (SSR, adapter Node) + Vue 3 + React 19 + Tailwind 4 + axios |
| Backend | Laravel 12 + Sanctum (Bearer tokens) + PostgreSQL 16 + pgvector |
| ML/NLP | Python (pandas, sklearn, Gemini, pysentimiento/BETO español) |
| Infra dev | Laravel Sail (Docker), gateway opcional OmniRoute (aparcado) |

## Estructura

```
kinema/
├── frontend/                 # Astro SPA/SSR (:4321)
│   └── src/{pages,components,composables,services,lib,layouts}
├── backend/
│   ├── kinema-backend/       # Laravel API (:8000)
│   │   ├── app/{Http/Controllers,Models,Services,Console/Commands}
│   │   ├── database/migrations
│   │   ├── routes/api.php
│   │   └── docker/99-kinema.ini  # OPcache CLI para Sail
│   └── python/src/           # Pipeline ML + NLP
│       ├── main.py           # Orquestador catálogo: extract→vectorize→analyze→label→CSV
│       ├── sentiment.py      # Sentimiento ES de reseñas → JSON para el frontend
│       └── extract_movies.py # (también app Streamlit original)
├── opencode.json             # Provider OmniRoute (usa {env:OMNIROUTE_API_KEY}, sin secretos)
└── temp/                     # Archivos personales (exports Letterboxd). Ignorado por git.
```

## Requisitos

- Docker Desktop + WSL2 (Ubuntu) para el backend.
- Node.js ≥ 22 + pnpm para el frontend.
- Python 3.14 + venv solo para el pipeline NLP (`backend/python/requirements.txt`).
- Claves: `TMDB_API_KEY` (posters/fichas/cast), `GENAI_API_KEY` (embeddings/vibras),
  `KINEMA_API_TOKEN` (token Sanctum para el pipeline). Nunca en git (`.env` ignorados).

## Puesta en marcha

```bash
# Backend (desde WSL, donde el filesystem es rápido)
cd ~/kinema/backend/kinema-backend
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --force

# Frontend (Windows)
cd frontend
pnpm install
pnpm dev            # http://localhost:4321 (API en http://localhost:8000)
```

> **Rendimiento:** el código bajo `/mnt/c` (OneDrive/red) vuelve a PHP 7s por
> request. El setup probado es código en `~/kinema` (ext4) + frontend en
> Windows. Tras editar backend, resincronizar:
> `rsync -a --exclude 'venv/' --exclude 'dist/' --exclude '.astro/' \
>   --exclude '__pycache__/' /mnt/c/.../kinema/ ~/kinema/`

## Comandos útiles

```bash
./vendor/bin/sail artisan app:import-movies kinema_data.csv  # catálogo ML
./vendor/bin/sail artisan app:enrich-posters --limit=200 --only-missing
./vendor/bin/sail artisan app:enrich-posters --ids=1,2,3 --force
./vendor/bin/sail artisan app:enrich-credits --limit=500
./vendor/bin/sail artisan analytics:refresh --token=xxx       # sentimiento (requiere venv con Python+deps)
# Vía directa recomendada (venv en WSL):
KINEMA_API_TOKEN=xxx ~/kinema-venv/bin/python backend/python/src/sentiment.py \
  --out backend/kinema-backend/storage/app/private/kinema_sentiment_data.json \
  --api-url http://localhost:8000
```

## API (resumen)

Público: `POST /api/{register,login}`, `GET /api/movies`, `/movies/search`,
`/movies/{id}[/similar|/credits]`, `/api/search` (pelis+listas públicas+personas),
`/api/recommendations`, `/api/reviews/{id}`, `/api/profiles/...`,
`/api/users/{id}/{lists,reviews,followers,following}`,
`/api/users/{u}/lists/{slug}`, `/api/people[/search|/{id}]`,
`/api/analytics/{activity,taste,map,genres}`, `/api/movies/{id}/sentiment`.

Protegido (`auth:sanctum`): `GET /api/user`, `/api/logout`, CRUD reseñas
(+`GET /movies/{id}/my-review`, `GET /api/reviews`), perfiles update, CRUD
listas (+items), follow toggle/status, `/api/feed`, `POST /api/import/letterboxd`.

Auth: Bearer token en `localStorage` (`kinema_token`), header `Authorization`.
Sin cookies/CSRF (el middleware stateful de Sanctum se quitó a propósito).

## Decisiones registradas

- SSR con `@astrojs/node` (el prerender estático daba 404 a rutas dinámicas).
- Layout Astro (no Vue): las directivas `client:*` solo hidran en `.astro`.
- `sail-pgdata` nombra el PGDATA real (la imagen pgvector usa
  `/var/lib/postgresql/data`; sin esto cada recreate “perdía” la BD).
- `activity_logs` eliminada (heatmap sale de `reviews`).
- TMDB con scoring de match (el primer resultado crudo asignaba basura).
- Stubs Letterboxd convergen al catálogo por `tmdb_id` con fusión.

## Git

Ramas: `dev` (trabajo) → `master` (estable). Commits en español, cortos.
