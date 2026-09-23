import os
import requests
import pandas as pd
import json
from happytransformer import HappyTextClassification

# 1. Configuración de la API
# TODO(Fase D): reescritura pendiente. El token nunca va en código:
# usar variable de entorno KINEMA_API_TOKEN (token anterior revocado el 2026-09-22).
API_URL = "http://localhost:8000/api/reviews" # requiere endpoint GET (no existe aún)
TOKEN = os.environ.get("KINEMA_API_TOKEN", "")

headers = {
    "Accept": "application/json",
    "Authorization": f"Bearer {TOKEN}"
}

def fetch_reviews():
    """Extrae las reseñas desde el backend de Kinema"""
    print("Descargando reseñas desde la API...")
    # Para el ejemplo, simulamos la extracción (en producción harías un request.get real)
    # response = requests.get(API_URL, headers=headers)
    # return response.json()
    
    # Datos simulados basados en nuestra estructura de base de datos
    return [
        {"movie_id": 1, "content": "El arco de redención es cine puro. Efectos excelentes.", "rating": 4.5},
        {"movie_id": 1, "content": "Me aburrió bastante, el guion es muy predecible.", "rating": 2.0},
        {"movie_id": 1, "content": "Visualmente increíble, pero los diálogos son flojos.", "rating": 3.0}
    ]

def analyze_sentiments(data):
    """Aplica DistilBERT para clasificar el texto"""
    print("Cargando modelo DistilBERT (esto puede tardar la primera vez)...")
    # Usamos el modelo por defecto de clasificación de sentimiento
    happy_tc = HappyTextClassification(model_type="DISTILBERT", model_name="distilbert-base-uncased-finetuned-sst-2-english", num_labels=2)
    
    df = pd.DataFrame(data)
    
    sentiments = []
    scores = []
    
    print("Analizando sentimientos...")
    for text in df['content']:
        # HappyTransformer devuelve un objeto con 'label' (POSITIVE/NEGATIVE) y 'score'
        result = happy_tc.classify_text(text)
        sentiments.append(result.label)
        scores.append(result.score)
        
    df['nlp_sentiment'] = sentiments
    df['nlp_confidence'] = scores
    
    return df

def generate_chart_data(df):
    """Agrupa los datos para que el frontend los pueda graficar fácilmente"""
    print("Generando JSON para el frontend...")
    
    # Agrupamos por película y sentimiento para contar cuántos positivos/negativos hay
    chart_data = df.groupby(['movie_id', 'nlp_sentiment']).size().unstack(fill_value=0).reset_index()
    
    # Convertimos el DataFrame a un formato de diccionario amigable para JSON
    final_json = chart_data.to_dict(orient='records')
    
    # Guardamos el resultado (Laravel leerá este archivo después)
    with open('kinema_sentiment_data.json', 'w') as f:
        json.dump(final_json, f, indent=4)
        
    print("¡Análisis completado! Archivo kinema_sentiment_data.json generado.")

# Ejecución del pipeline
if __name__ == "__main__":
    raw_data = fetch_reviews()
    analyzed_df = analyze_sentiments(raw_data)
    generate_chart_data(analyzed_df)