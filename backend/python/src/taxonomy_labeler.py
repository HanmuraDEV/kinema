import pandas as pd
import streamlit as st
import json
from google import genai

class TaxonomyLabeler:
    def __init__(self, api_key: str):
        self.client = genai.Client(api_key=api_key)
        self.fallback_models = [
            "models/gemini-3.5-flash",
            "models/gemini-flash-latest"
        ]
        
    def label_clusters(self, df: pd.DataFrame, sample_size: int = 4) -> pd.DataFrame:
        unique_clusters = sorted(df['cluster_id'].unique())
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

        with st.spinner("Conectando con la IA para generar etiquetas estéticas (1 sola petición)..."):
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
                    st.success("¡Etiquetado de vibras completado!")
                    return df
                except Exception as e:
                    st.toast(f"⚠️ {model} falló al generar JSON. Intentando alternativa...")
                    continue
            
            st.error("❌ Los modelos fallaron al procesar el JSON. Intenta de nuevo.")
            st.stop()