<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string',
            'curso_id' => 'required',
            'tipo_profesor' => 'required|string'
        ]);

        try {
            // 1. Obtener contexto del curso
            $curso = Curso::with('estudiantes')->find($request->curso_id);
            $contextoAcademico = "Curso: {$curso->nivel}° Medio {$curso->letra}. Cantidad de alumnos: " . $curso->estudiantes->count() . ".";
            
            // 2. Prompt de Sistema (Reglas)
            $promptSistema = "Eres un asistente pedagógico experto y empático. Estás hablando con un {$request->tipo_profesor} de Chile. El profesor te consulta sobre este curso: {$contextoAcademico}. Da sugerencias pedagógicas prácticas, pautas de evaluación y dinámicas aplicables al aula. Usa formato Markdown (negritas, viñetas). Sé directo y profesional.";

            // 3. Limpiar la API Key
            $apiKey = trim(env('GEMINI_API_KEY'));
            
            if (empty($apiKey)) {
                throw new \Exception("La API Key de Gemini está vacía en el archivo .env.");
            }

            // 4. URL oficial usando el modelo 'gemini-flash-latest' de la lista que obtuvimos
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" . $apiKey;

            // 5. Enviamos la petición forzando el formato JSON
            $response = Http::withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => $promptSistema]]
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $request->mensaje]]
                        ]
                    ]
                ]);

            // 6. Procesar respuesta
            if ($response->successful()) {
                $textoIA = $response->json('candidates.0.content.parts.0.text');
                return response()->json(['respuesta' => $textoIA]);
            }

            return response()->json(['respuesta' => 'Error de Gemini: ' . $response->body()], 500);

        } catch (\Exception $e) {
            return response()->json(['respuesta' => 'Error de Laravel: ' . $e->getMessage()], 500);
        }
    }
}