<?php

namespace App\Http\Controllers;

use App\Services\LetterboxdImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    // Importa el ZIP exportado por Letterboxd al historial del usuario.
    // Acepta el ZIP tal cual se descarga (con diary.csv, ratings.csv,
    // watchlist.csv dentro); cada CSV se detecta por encabezados.
    public function letterboxd(Request $request, LetterboxdImportService $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:20480', // 20MB
        ]);

        $path = $request->file('file')->storeAs(
            'imports',
            'letterboxd_' . $request->user()->id . '_' . time() . '.zip'
        );

        try {
            $summary = $importer->import(
                $request->user(),
                storage_path('app/private/' . $path)
            );
        } finally {
            @unlink(storage_path('app/private/' . $path));
        }

        return response()->json([
            'message' => 'Historial importado con éxito',
            'summary' => $summary,
        ]);
    }
}
