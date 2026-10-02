# Diagramas de flujo y secuencia — Kinema

## F1. Login con Bearer token

```mermaid
sequenceDiagram
    autonumber
    participant B as Navegador (:4321)
    participant F as Astro SSR / Isla LoginForm
    participant A as API :8000
    participant D as Postgres
    B->>F: GET /login (SSR estático)
    B->>F: submit (isla hidratada) → POST /api/login {email, password}
    F->>A: POST /api/login
    A->>D: SELECT user + Hash::check
    A-->>F: 200 {access_token, user+profile}
    F->>F: localStorage.kinema_token = token
    F->>B: location.href = /
    B->>F: GET / (Navbar hidrata → GET /api/user con Bearer)
```

## F2. Calificar con estrellas sin reseña (merge por campo)

```mermaid
sequenceDiagram
    autonumber
    participant U as MovieRating (isla)
    participant A as POST /api/reviews {movie_id, rating}
    participant D as reviews
    U->>A: rating=4 (sin content)
    A->>D: updateOrCreate([user,movie], {rating}) — content intacto
    Note over A,D: store() solo toca campos presentes (filled);
    Note over A,D: el texto nunca borra la nota ni viceversa
    U->>U: pinta 4★ + “Guardada: 4/5”
```

## F3. Importación Letterboxd (ZIP → convergencia)

```mermaid
flowchart TB
    Z[ZIP tal cual] --> UNZIP[ZipArchive: ignora deleted/ + orphaned/]
    UNZIP --> DET{detectar por encabezados}
    DET -->|Rating| R[ratings.csv → reviews.rating]
    DET -->|Watched Date| D[diary.csv → watched_at]
    DET -->|Review| T[reviews.csv → content multilínea]
    DET -->|solo Name/Year| W[watched.csv → watched_at]
    DET -->|watchlist| WL[lista Watchlist]
    DET -->|lists/*.csv + preámbulo| L[listas propias, slug = archivo]
    DET -->|likes/films| LK[lista Me gusta]
    R & D & T & W --> RES[resolveMovie: título+año → sin fechas → stub]
    RES --> ENR[enrich TMDB inmediato]
    ENR -->|sin match| STUB[stub con poster]
    ENR -->|match existente| MERGE[mueve reseñas/items, borra stub]
    WL & L & LK --> ITEMS[firstOrCreate items ordenados]
```

## F4. Recomendaciones personalizadas

```mermaid
flowchart TB
    ME([GET /api/recommendations + Bearer]) --> HIS[reviews ≥4★ con vibe + embedding]
    HIS --> SIM[similar() pgvector × top-3 → “Porque te gustó X”]
    HIS --> VIB[top-2 vibras → no vistas con poster]
    SIM & VIB --> DEDUP[deduplica, máx 8]
    DEDUP --> FILL{< 6?}
    FILL -->|sí| TREND[relleno con trending]
    FILL -->|no| OUT([items + reasons])
    ANON([sin token]) --> TREND
```

## F5. Refresh de sentimiento (offline)

```mermaid
sequenceDiagram
    autonumber
    participant C as cron WSL 04:00
    participant S as sentiment.py (venv)
    participant A as GET /api/reviews?per_page=100 + Bearer
    participant B as BETO español (CPU)
    participant J as kinema_sentiment_data.json
    participant F as Ficha (Pulso)
    C->>S: ejecuta con KINEMA_API_TOKEN
    S->>A: pagina hasta last_page
    A-->>S: reseñas con texto
    S->>B: predict() por reseña → POS/NEG/NEU
    S->>J: agrega por movie_id
    F->>A: GET /movies/{id}/sentiment
    A->>J: lee JSON (tolera claves extra)
```

## F6. Añadir película a lista (modal)

```mermaid
sequenceDiagram
    autonumber
    participant U as MovieActions (isla)
    participant A as API
    U->>A: GET /api/user → GET /users/{id}/lists (modal)
    U->>A: POST /lists/{id}/items {movie_id}
    A-->>U: 201 (firstOrCreate: idempotente)
    U->>U: cierra modal + “Añadida a …”
```

## Matriz de permisos (resumen)

| Recurso | Anónimo | Dueño | Otro miembro |
|---|---|---|---|
| Ver películas, búsqueda, listas públicas, feed público | ✅ | ✅ | ✅ |
| Crear reseña/comentario/lista, follow, importar | ❌ 401 | ✅ | ✅ |
| Editar/borrar reseña, comentario, lista | — | ✅ | ❌ 404 |
| Ver lista privada | ❌ 403 | ✅ | ❌ 403 |
| `GET /api/reviews` (corpus NLP) | ❌ 401 | token servicio | token servicio |
