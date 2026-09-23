#!/usr/bin/env python3
"""Orquestador CLI del pipeline de catálogo Kinema (sin Streamlit).

Fases: extract (TMDB) -> vectorize (Gemini) -> analyze (KMeans + t-SNE)
-> label (vibras Gemini) -> CSV listo para `php artisan app:import-movies`.

Uso (desde backend/python, con el venv activo):
    python src/main.py catalog --pages 5 --clusters 12 --out kinema_data.csv

Variables de entorno (o backend/.env equivalente):
    TMDB_API_KEY   (usa API_KEY como alias heredado)
    GENAI_API_KEY
"""
from __future__ import annotations

import argparse
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from dotenv import load_dotenv  # noqa: E402


def _progress(prefix: str):
    def _show(frac: float, msg: str = "") -> None:
        pct = int(frac * 100)
        print(f"\r[{prefix}] {pct:3d}% {msg}"[:120], end="", flush=True)
    return _show


def cmd_catalog(args: argparse.Namespace) -> int:
    load_dotenv()
    api_key = os.environ.get("TMDB_API_KEY") or os.environ.get("API_KEY", "")
    genai_key = os.environ.get("GENAI_API_KEY", "")
    if not api_key:
        print("Falta TMDB_API_KEY (o API_KEY heredado) en el entorno.")
        return 1
    if not genai_key:
        print("Falta GENAI_API_KEY en el entorno.")
        return 1

    from extract_movies import DataExtractor
    from semantic_vectorizer import SemanticVectorizer
    from movie_analyzer import MovieAnalyzer
    from taxonomy_labeler import TaxonomyLabeler

    print(f"1/4 Extrayendo {args.pages} páginas de TMDB...")
    extractor = DataExtractor(api_key=api_key)
    df = extractor.fetch_popular_movies(
        pages=args.pages, progress=lambda f: _progress("extract")(f))

    print(f"\n2/4 Vectorizando {len(df)} películas con Gemini...")
    vec = SemanticVectorizer(api_key=genai_key)
    df = vec.vectorize_dataframe(
        df, progress=lambda f, m="": _progress("vectorize")(f, m))
    print(f"\n   Quedan {len(df)} con embedding válido.")

    print(f"3/4 Clustering K-Means (k={args.clusters}) + t-SNE...")
    analyzer = MovieAnalyzer(n_clusters=args.clusters)
    df = analyzer.analyze(df)

    print("4/4 Etiquetando vibras con Gemini...")
    labeler = TaxonomyLabeler(api_key=genai_key)
    df = labeler.label_clusters(
        df, progress=lambda m: print(f"   {m}"))

    df.to_csv(args.out, index=False)
    print(f"✅ CSV listo: {args.out} ({len(df)} filas).")
    print("   Siguiente paso: php artisan app:import-movies " + args.out)
    return 0


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(description="Pipeline de catálogo Kinema")
    sub = p.add_subparsers(dest="command", required=True)

    c = sub.add_parser("catalog", help="Genera el CSV del catálogo")
    c.add_argument("--pages", type=int, default=5)
    c.add_argument("--clusters", type=int, default=12)
    c.add_argument("--out", default="kinema_data.csv")
    c.set_defaults(func=cmd_catalog)
    return p


def main() -> int:
    args = build_parser().parse_args()
    return args.func(args)


if __name__ == "__main__":
    raise SystemExit(main())
