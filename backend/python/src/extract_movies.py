import tmdbsimple as tmdb
from os import environ
import pandas as pd
import time
import streamlit as st
from dotenv import load_dotenv
import plotly.express as px

# Cargar automáticamente las variables desde el archivo .env local
load_dotenv()

# 1. Configuración de la Interfaz de Streamlit
st.set_page_config(page_title="TMDB Extractor & Visualizer", layout="wide")
st.title("🎬 Pipeline Completo: Extracción, Vectorización y Clustering")

class DataExtractor:
    def __init__(self, api_key: str):
        tmdb.API_KEY = api_key
        tmdb.REQUESTS_TIMEOUT = 5
        self.genre_map = self._build_genre_map()
    
    def _build_genre_map(self) -> dict:
        genres_api = tmdb.Genres()
        try:
            response = genres_api.movie_list()
            return {g['id']: g['name'] for g in response['genres']}
        except Exception as e:
            st.warning(f'⚠️ Error obteniendo mapa de géneros: {e}')
            return {}

    def fetch_popular_movies(self, pages: int = 5) -> pd.DataFrame:
        movies_data = []
        movies_api = tmdb.Movies()
        
        progress_bar = st.progress(0)
        status_text = st.empty()

        for page in range(1, pages + 1):
            status_text.text(f"Extrayendo página {page} de {pages} (TMDB)...")
            try:
                popular = movies_api.popular(page=page)
                for movie in popular['results']:
                    genre_names = [self.genre_map.get(gid, "Unknown") for gid in movie.get('genre_ids', [])]
                    metadata = f"Title: {movie['title']}. Genres: {', '.join(genre_names)}. Overview: {movie.get('overview', '')}"
                    
                    movies_data.append({
                        "tmdb_id": movie['id'],
                        "title": movie['title'],
                        "genres": ", ".join(genre_names),
                        "metadata_text": metadata
                    })
                time.sleep(0.2)
            except Exception as e:
                st.error(f"Error en página {page}: {e}")
            
            progress_bar.progress(page / pages)

        status_text.empty()
        progress_bar.empty()

        df = pd.DataFrame(movies_data)
        return df[df['metadata_text'].str.strip() != ""]

# Decorador CRÍTICO: Evita ejecutar la descarga masiva cada vez que se interactúa con la app
@st.cache_data(show_spinner=False)
def load_movie_data(api_key: str, num_pages: int):
    extractor = DataExtractor(api_key=api_key)
    return extractor.fetch_popular_movies(pages=num_pages)

# --- PANEL LATERAL (SIDEBAR) ---
st.sidebar.header("Configuración del Pipeline")

# Manejo de Keys
api_key_input = environ.get('API_KEY', '')
if api_key_input:
    st.sidebar.success("✅ TMDB API Key cargada")
    api_key = api_key_input
else:
    api_key = st.sidebar.text_input("Ingresa tu TMDB API Key", type="password")

gemini_key_input = environ.get('GENAI_API_KEY', '')
if gemini_key_input:
    st.sidebar.success("✅ Gemini API Key cargada")
    gemini_key = gemini_key_input
else:
    gemini_key = st.sidebar.text_input("Ingresa tu GENAI API Key", type="password")

# Controles de Parámetros
pages_to_fetch = st.sidebar.slider("Páginas a extraer (20 pelis c/u)", min_value=1, max_value=20, value=2)
n_clusters = st.sidebar.slider("Número de Agrupaciones (Clusters)", min_value=2, max_value=15, value=5)

# --- BOTÓN MAESTRO: Ejecuta todo el flujo continuo ---
if st.sidebar.button("▶️ Iniciar Pipeline Completo"):
    if not api_key or not gemini_key:
        st.error("⚠️ Faltan las claves de API (TMDB o Gemini) para continuar.")
    else:
        # --- FASE 1: EXTRACCIÓN ---
        st.subheader("1️⃣ Extracción de Datos (TMDB)")
        with st.spinner('Conectando con TMDB...'):
            df = load_movie_data(api_key, pages_to_fetch)
            st.success(f"¡Extracción exitosa! {len(df)} películas obtenidas.")
            st.dataframe(df[['title', 'genres']].head(3), use_container_width=True)

        # --- FASE 2: VECTORIZACIÓN ---
        st.divider()
        st.subheader("2️⃣ Vectorización Semántica (Gemini)")
        with st.spinner("Conectando con Google GenAI y procesando vectores..."):
            from semantic_vectorizer import SemanticVectorizer
            vectorizer = SemanticVectorizer(api_key=gemini_key)
            
            # Ejecutamos la vectorización (esto llama a tu otra clase)
            df_vectorized = vectorizer.vectorize_dataframe(df)
            
            if df_vectorized.empty:
                st.error("🚨 Falló la vectorización. El DataFrame quedó vacío.")
                st.stop()
                
            st.success(f"¡Vectorización completa! {len(df_vectorized)} películas procesadas.")

        # --- FASE 3: CLUSTERING Y GRÁFICA ---
        st.divider()
        st.subheader("3️⃣ Análisis Matemático y Clustering")
        with st.spinner("Ejecutando K-Means y t-SNE..."):
            from movie_analyzer import MovieAnalyzer
            
            analyzer = MovieAnalyzer(n_clusters=n_clusters)
            df_analyzed = analyzer.analyze(df_vectorized)
            
            st.success("¡Análisis completado! Generando mapa visual...")
            
            # Gráfica Interactiva con Plotly
            fig = px.scatter(
                df_analyzed,
                x='x', 
                y='y',
                color=df_analyzed['cluster_id'].astype(str),
                hover_name='title',
                hover_data={'genres': True, 'cluster_id': True, 'x': False, 'y': False},
                title=f"Mapa Semántico ({n_clusters} Clusters)",
                color_discrete_sequence=px.colors.qualitative.Pastel
            )
            fig.update_layout(template="plotly_dark")
            st.plotly_chart(fig, use_container_width=True)
            
            # Opción para guardar los resultados listos para Laravel
            csv = df_analyzed.to_csv(index=False).encode('utf-8')
            st.download_button(
                label="💾 Descargar Dataset Procesado (CSV)",
                data=csv,
                file_name='movies_clustered.csv',
                mime='text/csv',
            )

elif 'df' not in st.session_state:
    st.info("👈 Configura los parámetros en la barra lateral y presiona 'Iniciar Pipeline Completo'.")