import tmdbsimple as tmdb
from os import environ
import pandas as pd
import time
import streamlit as st
from dotenv import load_dotenv
import plotly.express as px

load_dotenv()

st.set_page_config(page_title="TMDB Extractor & Visualizer", layout="wide")
st.title("🎬 Pipeline Inteligente (Con Auto-Reanudación)")

# --- INICIALIZACIÓN DE LA MEMORIA (SESSION STATE) ---
if 'df_extracted' not in st.session_state:
    st.session_state.df_extracted = None
if 'df_vectorized' not in st.session_state:
    st.session_state.df_vectorized = None
if 'df_analyzed' not in st.session_state:
    st.session_state.df_analyzed = None
if 'run_pipeline' not in st.session_state:
    st.session_state.run_pipeline = False

class DataExtractor:
    def __init__(self, api_key: str):
        tmdb.API_KEY = api_key
        tmdb.REQUESTS_TIMEOUT = 5
        self.genre_map = self._build_genre_map()
    
    def _build_genre_map(self) -> dict:
        try:
            return {g['id']: g['name'] for g in tmdb.Genres().movie_list()['genres']}
        except:
            return {}

    def fetch_popular_movies(self, pages: int = 5) -> pd.DataFrame:
        movies_data = []
        movies_api = tmdb.Movies()
        progress_bar = st.progress(0)
        
        for page in range(1, pages + 1):
            try:
                popular = movies_api.popular(page=page)
                for movie in popular['results']:
                    movie_id = movie['id']
                    
                    # 1. Extraemos géneros
                    genres = [self.genre_map.get(gid, "Unknown") for gid in movie.get('genre_ids', [])]
                    genres_str = ", ".join(genres)
                    
                    # 2. Nueva consulta a la API para extraer las Keywords oficiales
                    try:
                        kw_response = tmdb.Movies(movie_id).keywords()
                        keywords_list = [k['name'] for k in kw_response.get('keywords', [])]
                        keywords_str = ", ".join(keywords_list)
                    except:
                        keywords_str = ""
                    
                    # 3. Guardamos todo, incluyendo una columna dedicada a keywords
                    movies_data.append({
                        "tmdb_id": movie_id,
                        "title": movie['title'],
                        "genres": genres_str,
                        "keywords": keywords_str, # Nueva columna aislada para el Paso 4
                        # Enriquecemos la metadata para que la vectorización sea más precisa
                       "metadata_text": f"Title: {movie['title']}. Overview: {movie.get('overview', '')}"
                    })
                time.sleep(0.2)
            except Exception as e:
                pass
            progress_bar.progress(page / pages)
        
        progress_bar.empty()
        df = pd.DataFrame(movies_data)
        return df[df['metadata_text'].str.strip() != ""]

# --- PANEL LATERAL ---
st.sidebar.header("Configuración")
api_key = environ.get('API_KEY', '')
gemini_key = environ.get('GENAI_API_KEY', '')

pages_to_fetch = st.sidebar.slider("Páginas a extraer", 1, 10, 2)
n_clusters = st.sidebar.slider("Número de Clusters", 2, 15, 5)

# --- BOTONES DE CONTROL ---
if st.sidebar.button("▶️ Iniciar / Continuar Pipeline"):
    st.session_state.run_pipeline = True

if st.sidebar.button("🗑️ Limpiar Memoria y Reiniciar"):
    for key in ['df_extracted', 'df_vectorized', 'df_analyzed']:
        st.session_state[key] = None
    st.session_state.run_pipeline = False
    st.rerun()

# --- EJECUCIÓN SECUENCIAL AUTOMÁTICA ---
if st.session_state.run_pipeline:
    
    # FASE 1: Extracción
    st.subheader("1️⃣ Extracción de TMDB")
    if st.session_state.df_extracted is None:
        with st.spinner("Extrayendo de TMDB..."):
            extractor = DataExtractor(api_key=api_key)
            st.session_state.df_extracted = extractor.fetch_popular_movies(pages_to_fetch)
        st.success(f"¡Extraídas {len(st.session_state.df_extracted)} películas!")
    else:
        st.info(f"✅ Omitido: Ya hay {len(st.session_state.df_extracted)} películas en memoria.")

    # FASE 2: Vectorización
    st.subheader("2️⃣ Vectorización Semántica")
    if st.session_state.df_extracted is not None:
        if st.session_state.df_vectorized is None:
            with st.spinner("Vectorizando con Gemini..."):
                from semantic_vectorizer import SemanticVectorizer
                vec = SemanticVectorizer(api_key=gemini_key)
                st.session_state.df_vectorized = vec.vectorize_dataframe(st.session_state.df_extracted)
            st.success("¡Vectorización completada!")
        else:
            st.info("✅ Omitido: Vectores ya procesados en memoria.")

    # FASE 3: Clustering
    st.subheader("3️⃣ Análisis Matemático (K-Means)")
    if st.session_state.df_vectorized is not None:
        if st.session_state.df_analyzed is None:
            with st.spinner("Agrupando películas..."):
                from movie_analyzer import MovieAnalyzer
                analyzer = MovieAnalyzer(n_clusters=n_clusters)
                st.session_state.df_analyzed = analyzer.analyze(st.session_state.df_vectorized)
            st.success("¡Agrupación completada!")
        else:
            st.info("✅ Omitido: Clusters matemáticos ya calculados en memoria.")

    # FASE 4: Etiquetado Inteligente y Renderizado
    st.subheader("4️⃣ Etiquetado Inteligente y Visualización")
    if st.session_state.df_analyzed is not None:
        with st.spinner("Consultando nombres de clusters con Gemini..."):
            from taxonomy_labeler import TaxonomyLabeler
            labeler = TaxonomyLabeler(api_key=gemini_key)
            
            # Si Gemini falla, label_clusters detendrá la ejecución gracias a st.stop()
            # y los pasos 1, 2 y 3 seguirán guardados.
            df_final = labeler.label_clusters(st.session_state.df_analyzed)
            
            st.success("¡Etiquetado exitoso! Generando mapa visual...")
            
            fig = px.scatter(
                df_final, x='x', y='y', color='cluster_name', hover_name='title',
                hover_data={'genres': True, 'cluster_name': True},
                title=f"Mapa Semántico ({n_clusters} Categorías)",
                color_discrete_sequence=px.colors.qualitative.Pastel
            )
            fig.update_layout(template="plotly_dark")
            st.plotly_chart(fig, use_container_width=True)
            
            csv = df_final.to_csv(index=False).encode('utf-8')
            st.download_button("💾 Descargar CSV Final", data=csv, file_name='kinema_data.csv', mime='text/csv')
            
        # Desactivamos el trigger automático una vez que terminó todo con éxito
        st.session_state.run_pipeline = False

elif not st.session_state.run_pipeline and st.session_state.df_extracted is None:
    st.info("👈 Configura los parámetros y presiona 'Iniciar / Continuar Pipeline'.")