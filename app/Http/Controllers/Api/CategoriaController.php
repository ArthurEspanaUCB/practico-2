<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Categoria::query();

            // Filtro por estado
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Búsqueda
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")
                        ->orWhere('descripcion', 'ilike', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación o todos los registros
            if ($request->has('all') && $request->all == 'true') {
                $categorias = $query->get();
            } else {
                $perPage = $request->get('per_page', 10);
                $categorias = $query->paginate($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $categorias,
            ], 200);

            // Log de consulta
            // AuditLogger::consulta("Consulta de categorías. Filtros: " . json_encode($request->all()));
        } catch (Exception $e) {
            AuditLogger::error('Error al obtener categorías', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validación
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:categorias,nombre',
                'descripcion' => 'nullable|string|max:500',
            ], [
                'nombre.required' => 'El nombre es obligatorio',
                'nombre.max' => 'El nombre no puede exceder 100 caracteres',
                'nombre.unique' => 'Ya existe una categoría con este nombre',
                'descripcion.max' => 'La descripción no puede exceder 500 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Crear categoría
            DB::beginTransaction();

            $categoria = Categoria::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => true,
            ]);

            DB::commit();

            AuditLogger::insercion("Categoría creada: ID {$categoria->id}", $categoria->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error('Error al crear categoría', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $categoria = Categoria::find($id);

            if (! $categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $categoria,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la categoría',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $categoria = Categoria::find($id);

            if (! $categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                ], 404);
            }

            // Validación
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:categorias,nombre,'.$id,
                'descripcion' => 'nullable|string|max:500',
            ], [
                'nombre.required' => 'El nombre es obligatorio',
                'nombre.max' => 'El nombre no puede exceder 100 caracteres',
                'nombre.unique' => 'Ya existe una categoría con este nombre',
                'descripcion.max' => 'La descripción no puede exceder 500 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Actualizar categoría
            DB::beginTransaction();

            $categoria->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
            ]);

            DB::commit();

            AuditLogger::actualizacion("Categoría actualizada: ID {$id}", $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => $categoria,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error("Error al actualizar categoría ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleEstado($id)
    {
        try {
            $categoria = Categoria::find($id);

            if (! $categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                ], 404);
            }

            DB::beginTransaction();

            $nuevoEstado = ! $categoria->estado;
            $categoria->update(['estado' => $nuevoEstado]);

            DB::commit();

            $mensaje = $nuevoEstado ? 'Categoría activada exitosamente' : 'Categoría desactivada exitosamente';

            AuditLogger::actualizacion("Cambio de estado categoría ID {$id} a ".($nuevoEstado ? 'Activo' : 'Inactivo'));

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $categoria,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado de la categoría',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $categoria = Categoria::find($id);

            if (! $categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                ], 404);
            }

            // En lugar de eliminar, desactivamos
            DB::beginTransaction();
            $categoria->update(['estado' => false]);
            DB::commit();

            AuditLogger::eliminacion("Categoría eliminada (desactivada) ID {$id}");

            return response()->json([
                'success' => true,
                'message' => 'Categoría desactivada exitosamente',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
