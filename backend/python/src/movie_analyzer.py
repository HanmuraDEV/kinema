import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.manifold import TSNE

class MovieAnalyzer:
    """
    Clase encargada de aplicar algoritmos de Machine Learning 
    sobre los vectores generados para encontrar patrones.
    """
    def __init__(self, n_clusters: int = 5):
        self.n_clusters = n_clusters
        # Configuración de K-Means (Agrupación)
        self.kmeans = KMeans(n_clusters=self.n_clusters, random_state=42, n_init=10)
        
        # Configuración de t-SNE (Reducción de dimensionalidad)
        # perplexity define el balance entre atención local y global. 
        # (Para datasets muy pequeños < 50, se debe bajar a 5 o 10)
        self.tsne = TSNE(n_components=2, perplexity=15, random_state=42)

    def analyze(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Toma el DataFrame con la columna 'embedding', aplica los algoritmos
        y devuelve el DataFrame con las columnas 'cluster_id', 'x' e 'y'.
        """
        # 1. Extraer la columna de listas en una matriz numérica bidimensional limpia
        matrix = np.vstack(df['embedding'].values)

        # 2. Asignar cada película a un grupo (Cluster)
        df['cluster_id'] = self.kmeans.fit_predict(matrix)

        # 3. Calcular las coordenadas X e Y para la gráfica visual
        embedding_2d = self.tsne.fit_transform(matrix)
        df['x'] = embedding_2d[:, 0]
        df['y'] = embedding_2d[:, 1]

        return df