from google import genai
import pandas as pd
import streamlit as st
import time

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
            st.toast(f"⚠️ Error generando embedding: {e}")
            return None

    def vectorize_dataframe(self, df: pd.DataFrame, text_column: str = 'metadata_text') -> pd.DataFrame:
        progress_bar = st.progress(0)
        status_text = st.empty()
        
        embeddings = []
        total = len(df)
        
        for index, row in df.iterrows():
            text = row[text_column]
            status_text.text(f"Vectorizando: {row['title']} ({index + 1}/{total})")
            
            vec = self._get_single_embedding(text)
            embeddings.append(vec)
            
            progress_bar.progress((index + 1) / total)
            
            # Mantenemos la pausa mínima estrictamente necesaria para evitar el corte 
            # de conexión de red (WinError 10054) sin ralentizar de más el proceso.
            time.sleep(1.5)
            
        status_text.empty()
        progress_bar.empty()
        
        df['embedding'] = embeddings
        return df.dropna(subset=['embedding']).reset_index(drop=True)