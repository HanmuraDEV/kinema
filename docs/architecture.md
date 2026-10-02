# Arquitectura — Kinema

## 1. Diagrama de contexto (C4 L1)

```mermaid
flowchart LR
    U[Miembro / Visitante] -->|HTTPS :4321| FE[Frontend Astro SSR + islas Vue]
    FE -->|SSR fetch + Axios Bearer :8000| API[API Laravel Sanctum]
    API -->|SQL + pgvector| PG[(PostgreSQL 16)]
    API -->|REST| TMDB[TMDB API]
    PY[Pipeline Python] -->|GET /api/reviews con token| API
    PY -->|JSON sentimiento| ST[(storage JSON)]
    API -->|lee JSON| ST
    TMDB -.->|fichas, cast, taquilla| API
```

## 2. Contenedores (C4 L2, dev)

```mermaid
flowchart TB
    subgraph HOST[Dev: WSL2 + Windows]
        subgraph DOCKER[Docker: red kinema-backend_sail]
            APP[laravel.test :80 → host :8000<br/>php artisan serve + OPcache CLI]
            DB[(pgsql :5432 → host :5433<br/>ankane/pgvector)]
        end
        FE2[Astro dev :4321<br/>Windows, HMR]
        VENV[venv Python ~/kinema-venv<br/>torch CPU + pysentimiento]
    end
    APP <-->|red sail, DB_HOST=pgsql| DB
    FE2 -->|PUBLIC_API_URL| APP
    VENV -->|sentiment.py| APP
    VOL[(sail-pgdata<br/>PGDATA real)] --- DB
```

> Nota de ingeniería inversa: la imagen pgvector usa `PGDATA=/var/lib/postgresql/data`,
> distinto del volumen Sail por defecto. Sin el volumen nombrado `sail-pgdata`,
> cada recreate abandona los datos en un volumen anónimo (incidente real recuperado).

## 3. Componentes backend (C4 L3)

```mermaid
flowchart TB
    R[routes/api.php<br/>~40 endpoints] --> AUTH[AuthController<br/>register/login/logout]
    R --> MOV[MovieController<br/>index/search/show/similar/credits<br/>+ ratingStats + vibeNeighborhood]
    R --> REV[ReviewController<br/>CRUD + byUser + indexAll + myReview]
    R --> LST[MovieListController<br/>CRUD + items + showBySlug]
    R --> SOC[FollowController<br/>FeedController]
    R --> AN[AnalyticsController<br/>sentiment/activity/taste/map/genres]
    R --> PER[PersonController]
    R --> SEA[SearchController<br/>unificada + vibes]
    R --> REC[RecommendationController<br/>pgvector + vibras + trending]
    R --> IMP[ImportController<br/>ZIP Letterboxd]
    IMP --> LBSVC[LetterboxdImportService<br/>detecta CSV + merge]
    LBSVC --> TMDB[TmdbService<br/>search scoring/details/credits/<br/>personDetail/enrich+merge]
    MOV --> TMDB
    PER --> TMDB
    CMD[Artisan: import-movies<br/>enrich-posters/credits<br/>analytics:refresh] --> TMDB
    CMD --> LBSVC
    AUTH --> U[User + Sanctum tokens]
    U --- P[Profile]
```

## 4. Frontend: páginas e islas

| Ruta | SSR | Islas (`client:load`) |
|---|---|---|
| `/`, `/search`, `/movie?id=` | fetch API en servidor | FeedActivity, Recommendations, LiveSearch(estático), MovieActions, MovieRating, ReviewForm, CommentsSection |
| `/login`, `/register` | estática | LoginForm, RegisterForm, UploadHistory |
| `/diary`, `/lists` | AuthGuard | MyDiary, MyLists |
| `/[u]/profile`, `/[u]/lists/[s]`, `/[u]/review`, `/artists/[id]/profile` | fetch API en servidor | FollowButton, ListDetail, CommentsSection, ActivityHeatmap, TasteRadar, GenreRadar, RatingsLine |

Patrón: SSR pinta primero (con token solo si hay sesión — sin token usa
públicos/fallbacks); las islas hidratan y personalizan con `kinema_token`.

## 5. Pipeline de datos

```mermaid
flowchart LR
    T[TMDB popular + keywords] --> V[Gemini embeddings 3072d]
    V --> K[K-Means + t-SNE x/y]
    K --> L[Gemini: nombres de vibras]
    L --> CSV[kinema_data.csv]
    CSV --> ART[artisan app:import-movies]
    ART --> PG[(movies + vibes)]
    REV2[(reviews)] --> SEN[sentiment.py BETO-es]
    SEN --> JSON[kinema_sentiment_data.json]
    JSON --> UI[Pulso en ficha]
    PG --> ENR[enrich-posters/credits<br/>posters, cast, taquilla]
    ENR --> PG
```

## 6. Decisiones de arquitectura (ADRs resumidos)

1. **Bearer tokens, no cookies**: se eliminó `EnsureFrontendRequestsAreStateful`
   (provocaba 419 CSRF en navegador). Sin estado servidor salvo token revocable.
2. **SSR con adapter Node**: el prerender estático devolvía 404 en rutas
   dinámicas; el layout es `.astro` (las directivas `client:*` no existen en SFC Vue).
3. **Merge por `tmdb_id`**: los stubs Letterboxd convergen al catálogo moviendo
   reseñas/items y borrando duplicados (transacción atómica).
4. **Scoring TMDB**: primer resultado crudo asignaba basura; se exige match fuerte.
5. **Analytics híbrida**: agregados SQL en vivo (rápidos) + NLP offline (pesado).
