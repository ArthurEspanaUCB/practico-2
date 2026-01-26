<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\StoreProductoRequest;
use App\Http\Requests\Producto\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    /**
     * Listar todos los productos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Producto::with('categoria')
                ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
                ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->categoria_id))
                ->when($request->boolean('stock_bajo'), fn ($q) => $q->stockBajo())
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function ($subQ) use ($search) {
                        $subQ->where('codigo', 'ilike', "%{$search}%")
                            ->orWhere('nombre', 'ilike', "%{$search}%")
                            ->orWhere('descripcion', 'ilike', "%{$search}%");
                    });
                })
                ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

            $productos = $request->boolean('all')
                ? $query->get()
                : $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => ProductoResource::collection($productos),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error('Error al obtener productos', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los productos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear nuevo producto
     */
    public function store(StoreProductoRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['estado'] = true;

            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $nombreImagen = time().'_'.$imagen->getClientOriginalName();
                $data['imagen'] = $imagen->storeAs('productos', $nombreImagen, 'public');
            }

            $producto = Producto::create($data);

            DB::commit();

            AuditLogger::insercion("Producto creado: {$producto->codigo} - {$producto->nombre}", $producto->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => new ProductoResource($producto->load('categoria')),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error('Error al crear producto', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un producto específico
     */
    public function show($id): JsonResponse
    {
        try {
            $producto = Producto::with('categoria')->find($id);

            if (! $producto) {
                return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new ProductoResource($producto),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar producto
     */
    public function update(UpdateProductoRequest $request, $id): JsonResponse
    {
        try {
            $producto = Producto::find($id);

            if (! $producto) {
                return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior si existe
                if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                    Storage::disk('public')->delete($producto->imagen);
                }

                $imagen = $request->file('imagen');
                $nombreImagen = time().'_'.$imagen->getClientOriginalName();
                $data['imagen'] = $imagen->storeAs('productos', $nombreImagen, 'public');
            }

            $producto->update($data);

            DB::commit();

            AuditLogger::actualizacion("Producto actualizado: {$producto->codigo} - {$producto->nombre}", $request->except('imagen'));

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => new ProductoResource($producto->load('categoria')),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error("Error al actualizar producto ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado de producto
     */
    public function toggleEstado($id): JsonResponse
    {
        try {
            $producto = Producto::find($id);

            if (! $producto) {
                return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }

            DB::beginTransaction();
            $nuevoEstado = ! $producto->estado;
            $producto->update(['estado' => $nuevoEstado]);
            DB::commit();

            $mensaje = $nuevoEstado ? 'Producto activado exitosamente' : 'Producto desactivado exitosamente';
            AuditLogger::actualizacion("Cambio estado producto: {$producto->codigo} a ".($nuevoEstado ? 'Activo' : 'Inactivo'));

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => new ProductoResource($producto->load('categoria')),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error al cambiar el estado del producto', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generar código automático
     */
    public function generarCodigo(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => ['codigo' => Producto::generarCodigo()],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al generar código', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar producto (desactivar)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $producto = Producto::find($id);

            if (! $producto) {
                return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }

            DB::beginTransaction();
            $producto->update(['estado' => false]);
            DB::commit();

            AuditLogger::eliminacion("Producto desactivado ID {$id}");

            return response()->json(['success' => true, 'message' => 'Producto desactivado exitosamente'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
