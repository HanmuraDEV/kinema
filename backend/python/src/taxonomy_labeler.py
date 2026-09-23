import pandas as pd
import json
from google import genai

try:
    import streamlit as st
    _HAS_STREAMLIT = True
except ImportError:  # CLI sin streamlit
    _HAS_STREAMLIT = False
    st = None

from typing import Callable, Optional


class Reporter:
    """Spinner de streamlit en la app, print simple en CLI."""
    def __init__(self, message: str, progress: Optional[Callable[[str], None]] = None):
        self.message = message
        self.progress = progress
        self._spinner = None

    def __enter__(self):
        if self.progress:
            self.progress(self.message)
        elif _HAS_STREAMLIT:
            self._spinner = st.spinner(self.message)
            self._spinner.__enter__()
        else:
            print(self.message)
        return self

    def __exit__(self, *args):
        if self._spinner is not None:
            self._spinner.__exit__(*args)
        return False

class TaxonomyLabeler:
    def __init__(self, api_key: str):
        self.client = genai.Client(api_key=api_key)
        self.fallback_models = [
            "models/gemini-3.5-flash",
            "models/gemini-flash-latest"
        ]
        
    def label_clusters(self, df: pd.DataFrame, sample_size: int = 4,
                         progress: Optional[Callable[[str], None]] = None) -> pd.DataFrame:
        unique_clusters = sorted(df['cluster_id'].unique())
        if progress:
            progress("Sintetizando 'vibras' a través de las sinopsis...")
        elif _HAS_STREAMLIT:
            st.info("🧠 Sintetizando 'vibras' puras a través de las sinopsis...")
        
        # 1. Preparamos el contexto enviando las historias, no los géneros
        context_text = ""
        for cid in unique_clusters:
            cluster_movies = df[df['cluster_id'] == cid]
            sample = cluster_movies.sample(n=min(len(cluster_movies), sample_size), random_state=42)
            
            context_text += f"\n--- CLUSTER {cid} ---\n"
            for _, row in sample.iterrows():
                context_text += f"Título: {row['title']}\nSinopsis: {row['metadata_text'].split('Overview: ')[-1][:200]}...\n"

        # 2. El Prompt Definitivo de Vibras
        prompt = f"""
        Actúa como el Director de Experiencia (UX) de una plataforma de cine underground.
        A continuación, te presento grupos de películas (Clusters) con sus respectivas sinopsis.
        Estos grupos se formaron por sus similitudes emocionales, estéticas y narrativas (vibras).

        {context_text}

        Tu tarea: Asigna un nombre atractivo, evocador y no-tradicional a cada Cluster basado puramente en la "vibra" de sus tramas.

        REGLAS DE DISEÑO:
        1. MÁXIMO 3 PALABRAS por nombre. (Ejemplos de estilo: "Melancolía Neón", "Tensión Claustrofóbica", "Caos Absurdo", "Evasión Cálida", "Adrenalina Cruda").
        2. PROHIBIDO usar el formato "Categoría: Subcategoría" o paréntesis.
        3. Usa emociones y atmósferas.

        Devuelve ÚNICAMENTE un objeto JSON válido donde la llave sea el ID del cluster (string) y el valor sea la vibra.
        Ejemplo estricto: {{"0": "Caos Urbano", "1": "Odisea Espacial"}}
        """

        with Reporter("Conectando con la IA para generar etiquetas (1 sola petición)...", progress):
            for model in self.fallback_models:
                try:
                    response = self.client.models.generate_content(
                        model=model,
                        contents=prompt,
                    )

                    response_text = response.text.replace('```json', '').replace('```', '').strip()
                    labels_dict = json.loads(response_text)
                    labels_dict = {int(k): v for k, v in labels_dict.items()}

                    df['cluster_name'] = df['cluster_id'].map(labels_dict)
                    return df
                except Exception as e:
                    msg = f"⚠️ {model} falló al generar JSON. Intentando alternativa... ({e})"
                    if progress:
                        progress(msg)
                    elif _HAS_STREAMLIT:
                        st.toast(msg)
                    continue

            raise RuntimeError("❌ Los modelos fallaron al procesar el JSON.")