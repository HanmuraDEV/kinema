#!/usr/bin/env python3
"""Análisis de sentimiento de reseñas Kinema (español) -> JSON para el frontend.

Lee TODAS las reseñas con texto vía GET /api/reviews (token por entorno),
las clasifica con pysentimiento (BETO español) y escribe el agregado por
película que consume AnalyticsController@movieSentiment:

    [{"movie_id": 1, "POSITIVE": 3, "NEGATIVE": 1, "NEUTRAL": 0}, ...]

Uso (desde backend/python, con el venv activo):
    KINEMA_API_TOKEN=xxx python src/sentiment.py --out ../kinema-backend/storage/app/private/kinema_sentiment_data.json

Variables:
    KINEMA_API_URL    (defecto http://localhost:8000)
    KINEMA_API_TOKEN  (token Sanctum con acceso a /api/reviews)
"""
from __future__ import annotations

import argparse
import json
import os
import sys
from collections import Counter, defaultdict

import requests


def fetch_all_reviews(api_url: str, token: str, per_page: int = 100) -> list:
    reviews: list = []
    page = 1
    while True:
        r = requests.get(
            f"{api_url}/api/reviews",
            params={"per_page": per_page, "page": page},
            headers={"Accept": "application/json", "Authorization": f"Bearer {token}"},
            timeout=30,
        )
        r.raise_for_status()
        payload = r.json()
        batch = payload.get("data", [])
        if not batch:
            break
        reviews.extend(batch)
        print(f"   página {page}: {len(batch)} reseñas (total {len(reviews)})")
        if page >= payload.get("last_page", page):
            break
        page += 1
    return reviews


def classify(texts: list[str]):
    from pysentimiento import create_analyzer

    print("Cargando analizador de sentimiento (español, primera vez descarga modelo)...")
    analyzer = create_analyzer(task="sentiment", lang="es")
    labels, scores = [], []
    for i, text in enumerate(texts):
        out = analyzer.predict(text[:2000])  # BETO admite hasta ~512 tokens
        labels.append(out.output)  # POS | NEG | NEU
        scores.append(float(out.probas[out.output]))
        if (i + 1) % 20 == 0:
            print(f"   {i + 1}/{len(texts)}...")
    return labels, scores


def main() -> int:
    ap = argparse.ArgumentParser(description="Sentimiento de reseñas Kinema")
    ap.add_argument("--out", required=True, help="Ruta del JSON destino")
    ap.add_argument("--api-url", default=os.environ.get("KINEMA_API_URL", "http://localhost:8000"))
    ap.add_argument("--token", default=os.environ.get("KINEMA_API_TOKEN", ""))
    ap.add_argument("--per-page", type=int, default=100)
    args = ap.parse_args()

    if not args.token:
        print("Falta el token: exporta KINEMA_API_TOKEN o pasa --token.")
        return 1

    print("Descargando reseñas...")
    reviews = fetch_all_reviews(args.api_url, args.token, args.per_page)
    reviews = [r for r in reviews if (r.get("content") or "").strip()]
    print(f"Total con texto: {len(reviews)}")
    if not reviews:
        print("Sin reseñas que analizar; no se escribe nada.")
        return 0

    labels, scores = classify([r["content"] for r in reviews])

    agg: dict[int, Counter] = defaultdict(Counter)
    for review, label in zip(reviews, labels):
        key = {"POS": "POSITIVE", "NEG": "NEGATIVE", "NEU": "NEUTRAL"}.get(label, "NEUTRAL")
        agg[int(review["movie_id"])][key] += 1

    final = [
        {"movie_id": mid, "POSITIVE": c.get("POSITIVE", 0),
         "NEGATIVE": c.get("NEGATIVE", 0), "NEUTRAL": c.get("NEUTRAL", 0)}
        for mid, c in sorted(agg.items())
    ]

    os.makedirs(os.path.dirname(os.path.abspath(args.out)) or ".", exist_ok=True)
    with open(args.out, "w", encoding="utf-8") as f:
        json.dump(final, f, indent=4, ensure_ascii=False)

    n_pos = sum(1 for l in labels if l == "POS")
    print(f"✅ {len(reviews)} reseñas: {n_pos} POS, {len(labels) - n_pos - sum(1 for l in labels if l == 'NEU')} NEG. JSON: {args.out}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
