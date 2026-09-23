from google import genai
import pandas as pd
import time

try:
    import streamlit as st
except ImportError:  # CLI sin streamlit: shim mínimo
    class _NoStreamlit:
        def __getattr__(self, _):
            def _noop(*a, **k):
                return None
            return _noop
    st = _NoStreamlit()

from typing import Callable, Optional

class SemanticVectorizer:
    def __init__(self, api_key: str):
        self.client = genai.Client(api_key=api_key)
        # Restaurado al modelo exacto y a la ruta que funcionó originalmente en tu entorno
        self.model_name = "gemini-embedding-2"

    def _get_single_embedding(self, text: str) -> list:
        try:
            response = self.client.models.embed_content(
                model=self.model_name,
                contents=text
            )
            return response.embeddings[0].values
        except Exception as e:
            print(f"⚠️ Error generando embedding: {e}")
            return None

    def vectorize_dataframe(self, df: pd.DataFrame, text_column: str = 'metadata_text',
                            progress: Optional[Callable[[float, str], None]] = None) -> pd.DataFrame:
        embeddings = []
        total = len(df)

        for index, row in df.iterrows():
            text = row[text_column]

            if progress:
                progress((index + 1) / total, f"Vectorizando: {row['title']} ({index + 1}/{total})")

            vec = self._get_single_embedding(text)
            embeddings.append(vec)

            # Pausa mínima para evitar cortes de conexión sin ralentizar de más
            time.sleep(1.5)

        df['embedding'] = embeddings
        return df.dropna(subset=['embedding']).reset_index(drop=True)