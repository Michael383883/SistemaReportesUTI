<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Docente;

class DocenteController extends Controller
{
    public function index()
    {
        return response()->json(Docente::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|numeric|unique:docentes,codigo',
            'ci' => 'required|string|max:13',
            'nombres' => 'required|string|max:40',
            'apellidos' => 'nullable|string|max:40',
            'fecha_nac' => 'nullable|date',
            'sexo' => 'required|string|max:1',
            'titulo' => 'nullable|string|max:4',
            'fecha_nombramiento' => 'nullable|date'
        ]);

        $docente = Docente::create($data);

        return response()->json($docente, 201);
    }

    public function show($codigo)
    {
        return response()->json(
            Docente::findOrFail($codigo)
        );
    }

    public function update(Request $request, $codigo)
    {
        $docente = Docente::findOrFail($codigo);

        $data = $request->validate([
            'ci' => 'sometimes|string|max:13',
            'nombres' => 'sometimes|string|max:40',
            'apellidos' => 'nullable|string|max:40',
            'fecha_nac' => 'nullable|date',
            'sexo' => 'sometimes|string|max:1',
            'titulo' => 'nullable|string|max:4',
            'fecha_nombramiento' => 'nullable|date'
        ]);

        $docente->update($data);

        return response()->json($docente);
    }

    public function destroy($codigo)
    {
        Docente::destroy($codigo);

        return response()->json([
            'message' => 'Eliminado correctamente'
        ]);
    }
}