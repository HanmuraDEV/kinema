from google import genai
import pandas as pd

class SemanticVectorizer:
    """
    Clase responsable de comunicarse con la API de Google Gemini para
    generar vectores de similitud (Embeddings).
    """
    def __init__(self, api_key: str):
        # Inicializamos el cliente limpiamente. El SDK moderno resolverá la versión API por defecto.
        self.client = genai.Client(api_key=api_key)
        
        # Actualizamos a la nomenclatura moderna del modelo de embeddings de Gemini
        self.model_name = "gemini-embedding-2"

    def _get_single_embedding(self, text: str) -> list:
        """
        Método privado. Envía un solo texto a la API con sistema de Fallback.
        """
        try:
            response = self.client.models.embed_content(
                model=self.model_name,
                contents=text
            )
            return response.embeddings[0].values
        except Exception as e:
            error_msg = str(e)
            if "404" in error_msg or "not found" in error_msg.lower():
                # Fallback al modelo de generación de texto previo en caso de que la región
                # no soporte todavía la familia gemini-embedding-2
                try:
                    fallback_model = "text-embedding-004"
                    response = self.client.models.embed_content(
                        model=fallback_model,
                        contents=text
                    )
                    return response.embeddings[0].values
                except Exception as e2:
                    print(f"Error definitivo (incluso con fallback): {e2}")
                    return None
            else:
                print(f"Error generando embedding: {e}")
                return None

    def vectorize_dataframe(self, df: pd.DataFrame, text_column: str = 'metadata_text') -> pd.DataFrame:
        """
        Aplica el modelo a todas las filas del DataFrame.
        En producción real, esto debería usar procesamiento por lotes (batching).
        """
        import streamlit as st # Agregado para usar barras de progreso en Streamlit
        import time # <-- NUEVO: Importamos time

        print(f"Vectorizando {len(df)} películas...")
        
        # --- NUEVO: Integración con Streamlit ---
        progress_bar = st.progress(0)
        status_text = st.empty()
        
        embeddings = []
        total = len(df)
        
        for index, row in df.iterrows():
            text = row[text_column]
            status_text.text(f"Vectorizando: {row['title']} ({index + 1}/{total})")
            
            vec = self._get_single_embedding(text)
            embeddings.append(vec)
            
            # Actualizar barra
            progress_bar.progress((index + 1) / total)

            # <-- NUEVO: Pausa para no saturar la API gratuita de Gemini (15 RPM)
            time.sleep(1.5) 
            
        status_text.empty()
        progress_bar.empty()
        # ----------------------------------------
        
        df['embedding'] = embeddings
        
        # Eliminamos aquellas películas que fallaron en la API
        return df.dropna(subset=['embedding']).reset_index(drop=True)