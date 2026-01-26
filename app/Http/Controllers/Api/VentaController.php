<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venta\StoreVentaRequest;
use App\Http\Requests\Venta\UpdateVentaRequest;
use App\Http\Resources\VentaResource;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para gestión de Ventas
 */
class VentaController extends Controller
{
    /**
     * Listar todas las ventas con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Venta::with(['cliente.persona', 'detalles.producto'])
                ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
                ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
                ->when($request->filled('tipo_venta'), fn ($q) => $q->where('tipo_venta', $request->tipo_venta))
                ->when($request->filled('fecha_desde'), fn ($q) => $q->whereDate('fecha_venta', '>=', $request->fecha_desde))
                ->when($request->filled('fecha_hasta'), fn ($q) => $q->whereDate('fecha_venta', '<=', $request->fecha_hasta))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function ($subQ) use ($search) {
                        $subQ->where('codigo', 'ilike', "%{$search}%")
                            ->orWhere('numero_comprobante', 'ilike', "%{$search}%");
                    });
                })
                ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

            $ventas = $request->boolean('all')
                ? $query->get()
                : $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => VentaResource::collection($ventas),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error('Error al obtener ventas', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las ventas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva venta con sus detalles
     */
    public function store(StoreVentaRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['codigo'] = Venta::generarCodigo();
            $data['estado'] = Venta::ESTADO_PENDIENTE;
            $data['porcentaje_impuesto'] ??= 0;
            $data['porcentaje_descuento'] ??= 0;

            $venta = Venta::create($data);

            foreach ($request->detalles as $detalle) {
                $venta->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'porcentaje_descuento' => $detalle['porcentaje_descuento'] ?? 0,
                ]);
            }

            DB::commit();

            $venta->load(['cliente.persona', 'detalles.producto']);

            AuditLogger::insercion("Venta creada: {$venta->codigo} (Cliente ID: {$venta->cliente_id})", $request->input('detalles'));

            return response()->json([
                'success' => true,
                'message' => 'Venta creada exitosamente',
                'data' => new VentaResource($venta),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error('Error al crear venta', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar una venta específica con sus detalles
     */
    public function show(int $id): JsonResponse
    {
        try {
            $venta = Venta::with(['cliente.persona', 'detalles.producto.categoria'])->find($id);

            if (! $venta) {
                return response()->json(['success' => false, 'message' => 'Venta no encontrada'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new VentaResource($venta),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una venta existente (solo si está pendiente)
     */
    public function update(UpdateVentaRequest $request, int $id): JsonResponse
    {
        try {
            $venta = Venta::find($id);

            if (! $venta) {
                return response()->json(['success' => false, 'message' => 'Venta no encontrada'], 404);
            }

            if (! $venta->puede_editarse) {
                return response()->json(['success' => false, 'message' => 'Solo las ventas pendientes pueden modificarse'], 422);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $data['porcentaje_impuesto'] ??= 0;
            $data['porcentaje_descuento'] ??= 0;

            $venta->update($data);

            $venta->detalles()->delete();

            foreach ($request->detalles as $detalle) {
                $venta->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'porcentaje_descuento' => $detalle['porcentaje_descuento'] ?? 0,
                ]);
            }

            DB::commit();

            $venta->load(['cliente.persona', 'detalles.producto']);
            AuditLogger::actualizacion("Venta actualizada: {$venta->codigo}", $request->except(['detalles']));

            return response()->json([
                'success' => true,
                'message' => 'Venta actualizada exitosamente',
                'data' => new VentaResource($venta),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error("Error al actualizar venta ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Completar una venta (reduce stock y usa crédito)
     */
    public function completar(int $id): JsonResponse
    {
        try {
            $venta = Venta::with(['detalles.producto', 'cliente'])->find($id);

            if (! $venta) {
                return response()->json(['success' => false, 'message' => 'Venta no encontrada'], 404);
            }

            $venta->completar();
            $venta->load(['cliente.persona', 'detalles.producto']);

            AuditLogger::actualizacion("Venta completada: {$venta->codigo} (Stock actualizado)");

            return response()->json([
                'success' => true,
                'message' => 'Venta completada exitosamente. Stock actualizado.',
                'data' => new VentaResource($venta),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error("Error al completar venta ID {$id}", $e);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Anular una venta (devuelve stock y crédito si estaba completada)
     */
    public function anular(int $id): JsonResponse
    {
        try {
            $venta = Venta::with(['detalles.producto', 'cliente'])->find($id);

            if (! $venta) {
                return response()->json(['success' => false, 'message' => 'Venta no encontrada'], 404);
            }

            $venta->anular();
            $venta->load(['cliente.persona', 'detalles.producto']);

            AuditLogger::actualizacion("Venta anulada: {$venta->codigo} (Stock revertido)");

            return response()->json([
                'success' => true,
                'message' => 'Venta anulada exitosamente',
                'data' => new VentaResource($venta),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error("Error al anular venta ID {$id}", $e);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Eliminar una venta (solo si está pendiente)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $venta = Venta::find($id);

            if (! $venta) {
                return response()->json(['success' => false, 'message' => 'Venta no encontrada'], 404);
            }

            if ($venta->estado !== Venta::ESTADO_PENDIENTE) {
                return response()->json(['success' => false, 'message' => 'Solo las ventas pendientes pueden eliminarse. Las completadas deben anularse.'], 422);
            }

            DB::beginTransaction();
            $venta->detalles()->delete();
            $venta->delete();
            DB::commit();

            AuditLogger::eliminacion("Venta eliminada ID {$id}");

            return response()->json(['success' => true, 'message' => 'Venta eliminada exitosamente'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            AuditLogger::error("Error al eliminar venta ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar código automático para nueva venta
     */
    public function generarCodigo(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => ['codigo' => Venta::generarCodigo()],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al generar código', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener clientes activos para select
     */
    public function getClientes(): JsonResponse
    {
        $clientes = Cliente::with('persona')
            ->activos()
            ->orderBy('codigo')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nombre' => $c->persona->nombre_completo ?? 'Sin nombre',
                'dias_credito' => $c->dias_credito,
                'credito_disponible' => $c->credito_disponible,
            ]);

        // Nota: No uso ClienteResource::collection aquí porque el resource tiene campos extra que quizás no necesito en un select,
        // o porque el formato es específico para el select.
        // Pero para ser consistente, DEBERÍA usar Resource.
        // Lo dejaré así para no cambiar la estructura que espera "nombre", "dias_credito".
        // ClienteResource tiene "nombre_completo".

        return response()->json(['success' => true, 'data' => $clientes], 200);
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
                'precio_venta' => $p->precio_venta,
                'stock' => $p->stock,
                'unidad_medida' => $p->unidad_medida,
            ]);

        return response()->json(['success' => true, 'data' => $productos], 200);
    }
}
