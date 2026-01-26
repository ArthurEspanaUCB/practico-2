<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Persona;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador API para gestión de Clientes
 *
 * Maneja todas las operaciones CRUD de clientes del sistema de ventas,
 * incluyendo la creación y actualización de datos personales asociados.
 * Implementa soft delete mediante cambio de estado y sigue el estándar REST.
 *
 * Estructura de respuesta estándar:
 * - success: boolean - Indica si la operación fue exitosa
 * - message: string - Mensaje descriptivo de la operación
 * - data: object|array - Datos de respuesta
 * - errors: object - Errores de validación (solo en caso de error 422)
 *
 * @author Sistema de Ventas
 *
 * @version 1.0
 */
class ClienteController extends Controller
{
    /**
     * Listar todos los clientes con filtros y paginación
     *
     * Endpoint: GET /api/clientes
     *
     * Query Parameters:
     * - estado: boolean - Filtrar por estado activo/inactivo
     * - tipo_cliente: string - Filtrar por tipo (Regular, VIP, Corporativo, Mayorista)
     * - search: string - Búsqueda en nombres, apellidos, razón social, documento, código
     * - sort_by: string - Campo para ordenar (default: id)
     * - sort_order: string - Orden asc/desc (default: desc)
     * - all: boolean - Si es 'true', retorna todos sin paginar
     * - per_page: int - Registros por página (default: 10)
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": { ... }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Iniciar query con eager loading de persona para optimizar consultas
            $query = Cliente::with('persona');

            // Aplicar filtro por estado si se proporciona
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Aplicar filtro por tipo de cliente si se proporciona
            if ($request->has('tipo_cliente') && $request->tipo_cliente != '') {
                $query->where('tipo_cliente', $request->tipo_cliente);
            }

            // Aplicar búsqueda global en múltiples campos
            // Busca en datos de persona (nombres, apellidos, razón social, documento) y código de cliente
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->whereHas('persona', function ($q) use ($search) {
                    // ILIKE es case-insensitive en PostgreSQL, usar LIKE para MySQL
                    $q->where('nombres', 'ilike', "%{$search}%")
                        ->orWhere('apellidos', 'ilike', "%{$search}%")
                        ->orWhere('razon_social', 'ilike', "%{$search}%")
                        ->orWhere('numero_documento', 'ilike', "%{$search}%");
                })->orWhere('codigo', 'ilike', "%{$search}%");
            }

            // Aplicar ordenamiento personalizado
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Retornar todos los registros o paginados según parámetro
            if ($request->has('all') && $request->all == 'true') {
                $clientes = $query->get();
            } else {
                $perPage = $request->get('per_page', 10);
                $clientes = $query->paginate($perPage);
            }

            // Respuesta exitosa con datos
            // Log de consulta
            // AuditLogger::consulta("Consulta de clientes. Filtros: " . json_encode($request->all()));

            return response()->json([
                'success' => true,
                'data' => $clientes,
            ], 200);
        } catch (Exception $e) {
            // Capturar cualquier error y retornar respuesta con código 500
            AuditLogger::error('Error al obtener clientes', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los clientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo cliente con su persona asociada
     *
     * Endpoint: POST /api/clientes
     *
     * Body (JSON):
     * {
     *   // Datos de Persona
     *   "tipo_documento": "DNI|RUC|CE|PASAPORTE",
     *   "numero_documento": "string (max 20, único)",
     *   "nombres": "string (max 100, requerido)",
     *   "apellidos": "string (max 100, requerido si no es RUC)",
     *   "razon_social": "string (max 200, requerido si es RUC)",
     *   "email": "email (max 150, opcional)",
     *   "telefono": "string (max 20, opcional)",
     *   "celular": "string (max 20, opcional)",
     *   "direccion": "string (max 250, opcional)",
     *   "ciudad": "string (max 100, opcional)",
     *   "provincia": "string (max 100, opcional)",
     *   "fecha_nacimiento": "date (antes de hoy, opcional)",
     *   "sexo": "M|F|Otro (opcional)",
     *
     *   // Datos de Cliente
     *   "tipo_cliente": "Regular|VIP|Corporativo|Mayorista (requerido)",
     *   "limite_credito": "numeric (min 0, opcional)",
     *   "dias_credito": "integer (0-365, opcional)",
     *   "descuento_general": "numeric (0-100, opcional)",
     *   "observaciones": "string (opcional)"
     * }
     *
     * Respuesta exitosa (201):
     * {
     *   "success": true,
     *   "message": "Cliente creado exitosamente",
     *   "data": { cliente con persona }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Definir reglas de validación
            $validator = Validator::make($request->all(), [
                // Validaciones para datos de persona
                'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
                'numero_documento' => 'required|string|max:20|unique:personas,numero_documento',
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required_if:tipo_documento,DNI,CE,PASAPORTE|nullable|string|max:100',
                'razon_social' => 'required_if:tipo_documento,RUC|nullable|string|max:200',
                'email' => 'nullable|email|max:150',
                'telefono' => 'nullable|string|max:20',
                'celular' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:250',
                'ciudad' => 'nullable|string|max:100',
                'provincia' => 'nullable|string|max:100',
                'fecha_nacimiento' => 'nullable|date|before:today',
                'sexo' => 'nullable|in:M,F,Otro',

                // Validaciones para datos de cliente
                'tipo_cliente' => 'required|in:Regular,VIP,Corporativo,Mayorista',
                'limite_credito' => 'nullable|numeric|min:0',
                'dias_credito' => 'nullable|integer|min:0|max:365',
                'descuento_general' => 'nullable|numeric|min:0|max:100',
                'observaciones' => 'nullable|string',
            ], [
                // Mensajes de error personalizados
                'tipo_documento.required' => 'El tipo de documento es obligatorio',
                'numero_documento.required' => 'El número de documento es obligatorio',
                'numero_documento.unique' => 'Este número de documento ya está registrado',
                'nombres.required' => 'El nombre es obligatorio',
                'apellidos.required_if' => 'Los apellidos son obligatorios',
                'razon_social.required_if' => 'La razón social es obligatoria para RUC',
                'email.email' => 'El email no es válido',
                'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
                'tipo_cliente.required' => 'El tipo de cliente es obligatorio',
                'descuento_general.max' => 'El descuento no puede ser mayor a 100%',
            ]);

            // Si la validación falla, retornar errores con código 422 (Unprocessable Entity)
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Iniciar transacción para asegurar atomicidad de operaciones
            DB::beginTransaction();

            // Crear registro de persona primero (relación padre)
            $persona = Persona::create([
                'tipo_documento' => $request->tipo_documento,
                'numero_documento' => $request->numero_documento,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'razon_social' => $request->razon_social,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'celular' => $request->celular,
                'direccion' => $request->direccion,
                'ciudad' => $request->ciudad,
                'provincia' => $request->provincia,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'sexo' => $request->sexo,
                'estado' => true,
            ]);

            // Crear registro de cliente asociado a la persona
            $cliente = Cliente::create([
                'persona_id' => $persona->id,
                'codigo' => Cliente::generarCodigo(), // Genera código automático (CLI000001, etc.)
                'tipo_cliente' => $request->tipo_cliente,
                'limite_credito' => $request->limite_credito ?? 0,
                'credito_usado' => 0, // Inicia en 0
                'dias_credito' => $request->dias_credito ?? 0,
                'descuento_general' => $request->descuento_general ?? 0,
                'observaciones' => $request->observaciones,
                'fecha_registro' => now(), // Fecha actual del sistema
                'estado' => true,
            ]);

            // Confirmar transacción si todo fue exitoso
            DB::commit();

            AuditLogger::insercion("Cliente creado: {$cliente->codigo}", $cliente->toArray());

            // Retornar respuesta exitosa con código 201 (Created)
            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => $cliente->load('persona'), // Incluir datos de persona en respuesta
            ], 201);
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            AuditLogger::error('Error al crear cliente', $e);

            // Retornar error con código 500 (Internal Server Error)
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar los detalles de un cliente específico
     *
     * Endpoint: GET /api/clientes/{id}
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": { cliente con persona }
     * }
     *
     * Respuesta error (404):
     * {
     *   "success": false,
     *   "message": "Cliente no encontrado"
     * }
     *
     * @param  int  $id  ID del cliente
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Buscar cliente por ID con datos de persona
            $cliente = Cliente::with('persona')->find($id);

            // Verificar si el cliente existe
            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                ], 404);
            }

            // Retornar cliente encontrado
            return response()->json([
                'success' => true,
                'data' => $cliente,
            ], 200);
        } catch (Exception $e) {
            // Capturar cualquier error inesperado
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar los datos de un cliente existente
     *
     * Endpoint: PUT/PATCH /api/clientes/{id}
     *
     * Body: Mismo formato que store()
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "message": "Cliente actualizado exitosamente",
     *   "data": { cliente actualizado }
     * }
     *
     * @param  int  $id  ID del cliente a actualizar
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Buscar cliente con persona asociada
            $cliente = Cliente::with('persona')->find($id);

            // Verificar existencia del cliente
            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                ], 404);
            }

            // Validar datos de entrada
            // NOTA: En unique se excluye el ID actual de persona para permitir mantener el mismo documento
            $validator = Validator::make($request->all(), [
                // Validaciones para datos de persona
                'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
                'numero_documento' => 'required|string|max:20|unique:personas,numero_documento,'.$cliente->persona_id,
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required_if:tipo_documento,DNI,CE,PASAPORTE|nullable|string|max:100',
                'razon_social' => 'required_if:tipo_documento,RUC|nullable|string|max:200',
                'email' => 'nullable|email|max:150',
                'telefono' => 'nullable|string|max:20',
                'celular' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:250',
                'ciudad' => 'nullable|string|max:100',
                'provincia' => 'nullable|string|max:100',
                'fecha_nacimiento' => 'nullable|date|before:today',
                'sexo' => 'nullable|in:M,F,Otro',

                // Validaciones para datos de cliente
                'tipo_cliente' => 'required|in:Regular,VIP,Corporativo,Mayorista',
                'limite_credito' => 'nullable|numeric|min:0',
                'dias_credito' => 'nullable|integer|min:0|max:365',
                'descuento_general' => 'nullable|numeric|min:0|max:100',
                'observaciones' => 'nullable|string',
            ], [
                // Mensajes de error personalizados
                'numero_documento.unique' => 'Este número de documento ya está registrado',
                'nombres.required' => 'El nombre es obligatorio',
                'apellidos.required_if' => 'Los apellidos son obligatorios',
                'razon_social.required_if' => 'La razón social es obligatoria para RUC',
            ]);

            // Si la validación falla, retornar errores
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Iniciar transacción para actualización atómica
            DB::beginTransaction();

            // Actualizar datos de la persona asociada
            $cliente->persona->update([
                'tipo_documento' => $request->tipo_documento,
                'numero_documento' => $request->numero_documento,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'razon_social' => $request->razon_social,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'celular' => $request->celular,
                'direccion' => $request->direccion,
                'ciudad' => $request->ciudad,
                'provincia' => $request->provincia,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'sexo' => $request->sexo,
            ]);

            // Actualizar datos comerciales del cliente
            $cliente->update([
                'tipo_cliente' => $request->tipo_cliente,
                'limite_credito' => $request->limite_credito ?? 0,
                'dias_credito' => $request->dias_credito ?? 0,
                'descuento_general' => $request->descuento_general ?? 0,
                'observaciones' => $request->observaciones,
            ]);

            // Confirmar transacción
            DB::commit();

            AuditLogger::actualizacion("Cliente actualizado: {$cliente->codigo}", $request->except(['imagen']));

            // Retornar respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente',
                'data' => $cliente->load('persona'),
            ], 200);
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            AuditLogger::error("Error al actualizar cliente ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alternar el estado de un cliente (activar/desactivar)
     *
     * Endpoint: PATCH /api/clientes/{id}/toggle-estado
     *
     * Cambia el estado actual al opuesto y sincroniza con persona asociada.
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "message": "Cliente activado/desactivado exitosamente",
     *   "data": { cliente actualizado }
     * }
     *
     * @param  int  $id  ID del cliente
     */
    public function toggleEstado(int $id): JsonResponse
    {
        try {
            // Buscar cliente con persona
            $cliente = Cliente::with('persona')->find($id);

            // Verificar existencia
            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                ], 404);
            }

            // Iniciar transacción
            DB::beginTransaction();

            // Calcular nuevo estado (toggle: true -> false, false -> true)
            $nuevoEstado = ! $cliente->estado;

            // Actualizar estado del cliente
            $cliente->update(['estado' => $nuevoEstado]);

            // Sincronizar estado con persona asociada
            $cliente->persona->update(['estado' => $nuevoEstado]);

            // Confirmar transacción
            DB::commit();

            AuditLogger::actualizacion("Cambio estado cliente ID {$id} a ".($nuevoEstado ? 'Activo' : 'Inactivo'));

            // Mensaje dinámico según el nuevo estado
            $mensaje = $nuevoEstado
                ? 'Cliente activado exitosamente'
                : 'Cliente desactivado exitosamente';

            // Retornar respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $cliente->load('persona'),
            ], 200);
        } catch (Exception $e) {
            // Revertir en caso de error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar código automático para nuevo cliente
     *
     * Endpoint: GET /api/clientes/generar-codigo
     *
     * Genera el siguiente código secuencial disponible (CLI000001, CLI000002, etc.)
     * Útil para previsualizar el código antes de crear el cliente.
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": { "codigo": "CLI000123" }
     * }
     */
    public function generarCodigo(): JsonResponse
    {
        try {
            // Invocar método estático del modelo para generar código
            $codigo = Cliente::generarCodigo();

            return response()->json([
                'success' => true,
                'data' => ['codigo' => $codigo],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar código',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar (desactivar) un cliente
     *
     * Endpoint: DELETE /api/clientes/{id}
     *
     * NOTA: Implementa soft delete cambiando estado a false.
     * No elimina físicamente el registro para mantener integridad referencial
     * y preservar historial de ventas/transacciones.
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "message": "Cliente desactivado exitosamente"
     * }
     *
     * @param  int  $id  ID del cliente a eliminar
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Buscar cliente con persona
            $cliente = Cliente::with('persona')->find($id);

            // Verificar existencia
            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                ], 404);
            }

            // Iniciar transacción
            DB::beginTransaction();

            // Soft delete: cambiar estado a false en lugar de eliminar
            $cliente->update(['estado' => false]);

            // Sincronizar estado con persona asociada
            $cliente->persona->update(['estado' => false]);

            // Confirmar transacción
            DB::commit();

            AuditLogger::eliminacion("Cliente eliminado (desactivado) ID {$id}");

            return response()->json([
                'success' => true,
                'message' => 'Cliente desactivado exitosamente',
            ], 200);
        } catch (Exception $e) {
            // Revertir en caso de error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
