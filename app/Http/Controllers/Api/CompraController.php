<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compra\StoreCompraRequest;
use App\Http\Requests\Compra\UpdateCompraRequest;
use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para gestión de Compras
 */
class CompraController extends Controller
{
    /**
     * Listar todas las compras con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Compra::with(['proveedor.persona', 'detalles.producto'])
                ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
                ->when($request->filled('proveedor_id'), fn ($q) => $q->where('proveedor_id', $request->proveedor_id))
                ->when($request->filled('tipo_compra'), fn ($q) => $q->where('tipo_compra', $request->tipo_compra))
                ->when($request->filled('fecha_desde'), fn ($q) => $q->whereDate('fecha_compra', '>=', $request->fecha_desde))
                ->when($request->filled('fecha_hasta'), fn ($q) => $q->whereDate('fecha_compra', '<=', $request->fecha_hasta))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function ($subQ) use ($search) {
                        $subQ->where('codigo', 'ilike', "%{$search}%")
                            ->orWhere('numero_comprobante', 'ilike', "%{$search}%");
                    });
                })
                ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

            $compras = $request->boolean('all')
                ? $query->get()
                : $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => CompraResource::collection($compras),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error('Error al obtener compras', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las compras',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva compra con sus detalles
     */
    public function store(StoreCompraRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['codigo'] = Compra::generarCodigo();
            $data['estado'] = Compra::ESTADO_PENDIENTE;
            $data['porcentaje_impuesto'] ??= 0;
            $data['porcentaje_descuento'] ??= 0;

            $compra = Compra::create($data);

            foreach ($request->detalles as $detalle) {
                $compra->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'porcentaje_descuento' => $detalle['porcentaje_descuento'] ?? 0,
                ]);
            }

            DB::commit();

            $compra->load(['proveedor.persona', 'detalles.producto']);

            AuditLogger::insercion("Compra creada: {$compra->codigo} (Proveedor ID: {$compra->proveedor_id})", $request->input('detalles'));

            return response()->json([
                'success' => true,
                'message' => 'Compra creada exitosamente',
                'data' => new CompraResource($compra),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error('Error al crear compra', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la compra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar una compra específica con sus detalles
     */
    public function show(int $id): JsonResponse
    {
        try {
            $compra = Compra::with(['proveedor.persona', 'detalles.producto.categoria'])->find($id);

            if (! $compra) {
                return response()->json(['success' => false, 'message' => 'Compra no encontrada'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new CompraResource($compra),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la compra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una compra existente (solo si está pendiente)
     */
    public function update(UpdateCompraRequest $request, int $id): JsonResponse
    {
        try {
            $compra = Compra::find($id);

            if (! $compra) {
                return response()->json(['success' => false, 'message' => 'Compra no encontrada'], 404);
            }

            if (! $compra->puede_editarse) {
                return response()->json(['success' => false, 'message' => 'Solo las compras pendientes pueden modificarse'], 422);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $data['porcentaje_impuesto'] ??= 0;
            $data['porcentaje_descuento'] ??= 0;

            $compra->update($data);

            $compra->detalles()->delete();

            foreach ($request->detalles as $detalle) {
                $compra->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'porcentaje_descuento' => $detalle['porcentaje_descuento'] ?? 0,
                ]);
            }

            DB::commit();

            $compra->load(['proveedor.persona', 'detalles.producto']);
            AuditLogger::actualizacion("Compra actualizada: {$compra->codigo}", $request->except(['detalles']));

            return response()->json([
                'success' => true,
                'message' => 'Compra actualizada exitosamente',
                'data' => new CompraResource($compra),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error("Error al actualizar compra ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la compra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Completar una compra (actualiza stock y crédito)
     */
    public function completar(int $id): JsonResponse
    {
        try {
            $compra = Compra::with(['detalles.producto', 'proveedor'])->find($id);

            if (! $compra) {
                return response()->json(['success' => false, 'message' => 'Compra no encontrada'], 404);
            }

            $compra->completar();
            $compra->load(['proveedor.persona', 'detalles.producto']);

            AuditLogger::actualizacion("Compra completada: {$compra->codigo} (Stock actualizado)");

            return response()->json([
                'success' => true,
                'message' => 'Compra completada exitosamente. Stock actualizado.',
                'data' => new CompraResource($compra),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error("Error al completar compra ID {$id}", $e);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Anular una compra (revierte stock y crédito si estaba completada)
     */
    public function anular(int $id): JsonResponse
    {
        try {
            $compra = Compra::with(['detalles.producto', 'proveedor'])->find($id);

            if (! $compra) {
                return response()->json(['success' => false, 'message' => 'Compra no encontrada'], 404);
            }

            $compra->anular();
            $compra->load(['proveedor.persona', 'detalles.producto']);

            AuditLogger::actualizacion("Compra anulada: {$compra->codigo} (Stock revertido)");

            return response()->json([
                'success' => true,
                'message' => 'Compra anulada exitosamente',
                'data' => new CompraResource($compra),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error("Error al anular compra ID {$id}", $e);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Eliminar una compra (solo si está pendiente)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $compra = Compra::find($id);

            if (! $compra) {
                return response()->json(['success' => false, 'message' => 'Compra no encontrada'], 404);
            }

            if ($compra->estado !== Compra::ESTADO_PENDIENTE) {
                return response()->json(['success' => false, 'message' => 'Solo las compras pendientes pueden eliminarse. Las completadas deben anularse.'], 422);
            }

            DB::beginTransaction();
            $compra->detalles()->delete();
            $compra->delete();
            DB::commit();

            AuditLogger::eliminacion("Compra eliminada ID {$id}");

            return response()->json(['success' => true, 'message' => 'Compra eliminada exitosamente'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error("Error al eliminar compra ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la compra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar código automático para nueva compra
     */
    public function generarCodigo(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => ['codigo' => Compra::generarCodigo()],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al generar el código', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener proveedores activos para select
     */
    public function getProveedores(): JsonResponse
    {
        $proveedores = Proveedor::with('persona')
            ->activos()
            ->orderBy('codigo')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->getNombreCompleto(),
                'dias_credito' => $p->dias_credito,
                'credito_disponible' => $p->credito_disponible,
            ]);

        return response()->json(['success' => true, 'data' => $proveedores], 200);
    }

    /**
     * Obtener productos activos para select
     */
    public function getProductos(): JsonResponse
    {
        $productos = Producto::with('categoria')
            ->activos()
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria?->nombre,
                'precio_compra' => $p->precio_compra,
                'stock' => $p->stock,
                'unidad_medida' => $p->unidad_medida,
            ]);

        return response()->json(['success' => true, 'data' => $productos], 200);
    }
}
