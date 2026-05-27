<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class TypeApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'types' => collect(config('trackpad.types'))
                ->map(fn ($label, $tipo) => [
                    'tipo' => (int) $tipo,
                    'label' => $label,
                    'nome_tipo' => config('trackpad.database_type_names')[$tipo] ?? null,
                ])
                ->values(),
        ]);
    }
}
