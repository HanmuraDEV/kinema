# Modelo E-R y diccionario — Kinema

> Esquema extraído en vivo de PostgreSQL 16 (`information_schema`).
> Volúmenes reales: 5 usuarios · 463 películas · 198 reseñas · 13 listas ·
> 5715 personas · 7925 créditos · 15 vibras · 2 follows · 1 comentario.

## Diagrama E-R

```mermaid
erDiagram
    users ||--o{ profiles : "1:1"
    users ||--o{ reviews : escribe
    users ||--o{ comments : escribe
    users ||--o{ movie_lists : crea
    users ||--o{ follows_follower : sigue
    users ||--o{ follows_followed : es seguido
    movies ||--o{ reviews : recibe
    movies ||--o{ comments : indirecto
    movies ||--o{ credits : acredita
    movies ||--o{ movie_list_items : contiene
    movies }o--|| vibes : pertenece
    movies ||--o{ profiles_top : favorita
    people ||--o{ credits : trabaja
    movie_lists ||--o{ movie_list_items : agrupa
    reviews ||--o{ comments : recibe

    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
    }
    profiles {
        bigint id PK
        bigint user_id FK
        text bio
        bigint top_movie_1 FK "SET NULL"
        bigint top_movie_2 FK "SET NULL"
        bigint top_movie_3 FK "SET NULL"
        bigint top_movie_4 FK "SET NULL"
    }
    movies {
        bigint id PK
        bigint tmdb_id UK_NULL "converge stubs"
        varchar title
        text overview
        varchar genres "nombres o ids TMDB"
        varchar poster_path
        date release_date
        smallint release_year
        bigint vibe_id FK "SET NULL"
        float8 x_coordinate
        float8 y_coordinate
        vector embedding "3072d pgvector"
        bigint budget
        bigint revenue
        int runtime
    }
    vibes {
        bigint id PK
        varchar name "ej. Sacrificio Colosal"
    }
    people {
        bigint id PK
        bigint tmdb_id UK
        varchar name
        text biography
        varchar profile_path
        date birthday
        date deathday
        varchar known_for_department
    }
    credits {
        bigint id PK
        bigint movie_id FK "CASCADE"
        bigint person_id FK "CASCADE"
        varchar department
        varchar job
        varchar character
        int order
    }
    reviews {
        bigint id PK
        bigint user_id FK "CASCADE"
        bigint movie_id FK "CASCADE"
        text content
        numeric rating "0.5–5.0"
        bool has_spoilers
        date watched_at
        varchar letterboxd_uri
    }
    comments {
        bigint id PK
        bigint review_id FK "CASCADE"
        bigint user_id FK "CASCADE"
        text content
    }
    movie_lists {
        bigint id PK
        bigint user_id FK "CASCADE"
        varchar name
        text description
        bool is_public
        varchar slug "UK(user_id,slug)"
    }
    movie_list_items {
        bigint id PK
        bigint movie_list_id FK "CASCADE"
        bigint movie_id FK "CASCADE"
        int sort_order
    }
    follows {
        bigint id PK
        bigint follower_id FK "users CASCADE"
        bigint followed_id FK "users CASCADE"
    }
```

## Diccionario (dominio; se omiten tablas framework de Laravel)

| Tabla | Clave | Borrado | Notas |
|---|---|---|---|
| `users` | PK id, UK email | — | Auth Sanctum en `personal_access_tokens` |
| `profiles` | PK, FK user CASCADE | cascada | Top 4 → movies SET NULL (no pierde perfil) |
| `movies` | PK, UK tmdb_id (nullable) | — | Stubs sin tmdb convergen por merge; embedding nullable (similar los excluye) |
| `vibes` | PK | SET NULL en movies | 15 clusters nombrados por Gemini |
| `people` | PK, UK tmdb_id | — | Bio bajo demanda vía TMDB |
| `credits` | PK, UK(movie,person,job) | cascada ambos | Top-15 cast + dirección/escritura |
| `reviews` | PK, UK lógica (user,movie) en app | cascada | Rating decimal 2,1; `watched_at` del diario |
| `comments` | PK | cascada | Hilo simple sobre reseñas |
| `movie_lists` | PK, UK(user,slug) | cascada | Slug auto con sufijo `-2` en colisión |
| `movie_list_items` | PK | cascada | `sort_order` apendiza al final |
| `follows` | PK | cascada | Sin UK par (toggle idempotente en app) |

## Reglas de integridad observadas

1. **Convergencia de duplicados**: el merge TMDB mueve reseñas e items antes de
   borrar el stub, en transacción (import Letterboxd atómico).
2. **Privacidad**: `is_public=false` excluye de búsqueda y exige dueño en lectura.
3. **Idempotencia social**: `updateOrCreate` (reseña), `firstOrCreate` (items),
   `toggle` (follows) — reintentos seguros.
4. **Nulos honestos**: poster/sinopsis/taquilla ausentes → fallback UI, nunca basura
   (scoring TMDB con umbral mínimo).
