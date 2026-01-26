<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Proveedor;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador API para gestión de Proveedores
 *
 * Maneja todas las operaciones CRUD de proveedores del sistema de compras,
 * incluyendo la creación y actualización de datos personales asociados,
 * información de contacto comercial, datos bancarios y calificación de desempeño.
 * Implementa soft delete mediante cambio de estado y sigue el estándar REST.
 *
 * DIFERENCIA CLAVE CON CLIENTES:
 * Los proveedores nos otorgan crédito a nosotros, mientras que los clientes
 * reciben crédito de nosotros. Esta diferencia semántica es importante al
 * gestionar límites de crédito y días de pago.
 *
 * Estructura de respuesta estándar:
 * - success: boolean - Indica si la operación fue exitosa
 * - message: string - Mensaje descriptivo de la operación
 * - data: object|array - Datos de respuesta (proveedor con persona relacionada)
 * - errors: object - Errores de validación (solo en caso de error 422)
 *
 * @author Sistema de Ventas
 *
 * @version 1.0
 */
class ProveedorController extends Controller
{
    /**
     * Listar todos los proveedores con filtros y paginación
     *
     * Endpoint: GET /api/v1/proveedores
     *
     * Query Parameters:
     * - estado: boolean - Filtrar por estado activo/inactivo
     * - tipo_proveedor: string - Filtrar por tipo (Producto, Servicio, Ambos)
     * - calificacion: int - Filtrar por calificación (1-5)
     * - rubro: string - Filtrar por rubro/industria
     * - search: string - Búsqueda en nombres, apellidos, razón social, documento, código, rubro
     * - sort_by: string - Campo para ordenar (default: id)
     * - sort_order: string - Orden asc/desc (default: desc)
     * - all: boolean - Si es 'true', retorna todos sin paginar
     * - per_page: int - Registros por página (default: 10)
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [ ... ],
     *     "total": 50
     *   }
     * }
     *
     * Respuesta error (500):
     * {
     *   "success": false,
     *   "message": "Error al obtener los proveedores",
     *   "error": "mensaje de error técnico"
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Iniciar query con eager loading de persona para optimizar consultas
            // Evita el problema N+1 al cargar relaciones anticipadamente
            $query = Proveedor::with('persona');

            // Aplicar filtro por estado si se proporciona
            // Permite filtrar proveedores activos (true) o inactivos (false)
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Aplicar filtro por tipo de proveedor si se proporciona
            // Filtra por: Producto, Servicio o Ambos
            if ($request->has('tipo_proveedor') && $request->tipo_proveedor != '') {
                $query->where('tipo_proveedor', $request->tipo_proveedor);
            }

            // Aplicar filtro por calificación si se proporciona
            // Calificación numérica del 1 al 5 (1=Malo, 5=Sobresaliente)
            if ($request->has('calificacion') && $request->calificacion != '') {
                $query->where('calificacion', $request->calificacion);
            }

            // Aplicar búsqueda global en múltiples campos
            // Busca en:
            // - Datos de persona: nombres, apellidos, razón social, número de documento
            // - Datos de proveedor: código, rubro
            // Usa ILIKE para búsqueda case-insensitive (PostgreSQL)
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->whereHas('persona', function ($q) use ($search) {
                    // Búsqueda en datos personales con OR para ampliar resultados
                    $q->where('nombres', 'ilike', "%{$search}%")
                        ->orWhere('apellidos', 'ilike', "%{$search}%")
                        ->orWhere('razon_social', 'ilike', "%{$search}%")
                        ->orWhere('numero_documento', 'ilike', "%{$search}%");
                })
                    // Búsqueda adicional en campos propios del proveedor
                    ->orWhere('codigo', 'ilike', "%{$search}%")
                    ->orWhere('rubro', 'ilike', "%{$search}%");
            }

            // Aplicar ordenamiento personalizado
            // Permite ordenar por cualquier campo de la tabla
            // Útil para ordenar por fecha_registro, total_compras, calificacion, etc.
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Retornar todos los registros o paginados según parámetro
            // all=true: retorna colección completa (útil para selectores/dropdowns)
            // Sin all: retorna paginado para listados de tabla
            if ($request->has('all') && $request->all == 'true') {
                $proveedores = $query->get();
            } else {
                $perPage = $request->get('per_page', 10);
                $proveedores = $query->paginate($perPage);
            }

            // Respuesta exitosa con código HTTP 200 (OK)
            // Log de consulta
            // AuditLogger::consulta("Consulta de proveedores. Filtros: " . json_encode($request->all()));

            return response()->json([
                'success' => true,
                'data' => $proveedores,
            ], 200);
        } catch (Exception $e) {
            // Capturar cualquier error inesperado y retornar código 500 (Internal Server Error)
            // En producción, considerar log del error: Log::error($e->getMessage())
            AuditLogger::error('Error al obtener proveedores', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los proveedores',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo proveedor con su persona asociada
     *
     * Endpoint: POST /api/v1/proveedores
     *
     * Body (JSON):
     * {
     *   // Datos de Persona (obligatorios marcados con *)
     *   "tipo_documento": "DNI|RUC|CE|PASAPORTE", *
     *   "numero_documento": "string (max 20, único)", *
     *   "nombres": "string (max 100)", *
     *   "apellidos": "string (max 100, requerido si no es RUC)",
     *   "razon_social": "string (max 200, requerido si es RUC)",
     *   "email": "email (max 150, opcional)",
     *   "telefono": "string (max 20, opcional)",
     *   "celular": "string (max 20, opcional)",
     *   "direccion": "string (max 250, opcional)",
     *   "ciudad": "string (max 100, opcional)",
     *   "provincia": "string (max 100, opcional)",
     *
     *   // Datos de Proveedor
     *   "tipo_proveedor": "Producto|Servicio|Ambos", *
     *   "rubro": "string (max 150, opcional)",
     *   "limite_credito": "numeric (min 0, crédito que nos otorgan)",
     *   "dias_credito": "integer (0-365, días que nos dan para pagar)",
     *   "descuento_general": "numeric (0-100, descuento que nos dan)",
     *   "cuenta_bancaria": "string (max 50, opcional)",
     *   "banco": "string (max 100, opcional)",
     *   "nombre_contacto": "string (max 150, opcional)",
     *   "cargo_contacto": "string (max 100, opcional)",
     *   "telefono_contacto": "string (max 20, opcional)",
     *   "email_contacto": "email (max 150, opcional)",
     *   "calificacion": "integer (1-5, default: 3)",
     *   "observaciones": "string (opcional)"
     * }
     *
     * Respuesta exitosa (201):
     * {
     *   "success": true,
     *   "message": "Proveedor creado exitosamente",
     *   "data": { proveedor con persona }
     * }
     *
     * Respuesta error validación (422):
     * {
     *   "success": false,
     *   "message": "Error de validación",
     *   "errors": { ... }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Definir reglas de validación
            // Las validaciones se ejecutan antes de tocar la base de datos
            // para prevenir datos inconsistentes
            $validator = Validator::make($request->all(), [
                // Validaciones para datos de persona
                'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
                'numero_documento' => 'required|string|max:20|unique:personas,numero_documento',
                'nombres' => 'required_if:tipo_documento,DNI,CE,PASAPORTE|nullable|string|max:100',
                'apellidos' => 'required_if:tipo_documento,DNI,CE,PASAPORTE|nullable|string|max:100',
                'razon_social' => 'required_if:tipo_documento,RUC|nullable|string|max:200',
                'email' => 'nullable|email|max:150',
                'telefono' => 'nullable|string|max:20',
                'celular' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:250',
                'ciudad' => 'nullable|string|max:100',
                'provincia' => 'nullable|string|max:100',

                // Validaciones para datos de proveedor
                'tipo_proveedor' => 'required|in:Producto,Servicio,Ambos',
                'rubro' => 'nullable|string|max:150',
                'limite_credito' => 'nullable|numeric|min:0',
                'dias_credito' => 'nullable|integer|min:0|max:365',
                'descuento_general' => 'nullable|numeric|min:0|max:100',
                'cuenta_bancaria' => 'nullable|string|max:50',
                'banco' => 'nullable|string|max:100',
                'nombre_contacto' => 'nullable|string|max:150',
                'cargo_contacto' => 'nullable|string|max:100',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:150',
                // IMPORTANTE: Calificación ahora es numérica (1-5), no enum
                'calificacion' => 'nullable|integer|min:1|max:5',
                'observaciones' => 'nullable|string',
            ], [
                // Mensajes de error personalizados en español
                // Mejoran la experiencia de usuario y claridad
                'tipo_documento.required' => 'El tipo de documento es obligatorio',
                'numero_documento.required' => 'El número de documento es obligatorio',
                'numero_documento.unique' => 'Este número de documento ya está registrado',
                'nombres.required_if' => 'El nombre es obligatorio para personas naturales',
                'apellidos.required_if' => 'Los apellidos son obligatorios para personas naturales',
                'razon_social.required_if' => 'La razón social es obligatoria para RUC',
                'tipo_proveedor.required' => 'El tipo de proveedor es obligatorio',
                'descuento_general.max' => 'El descuento no puede ser mayor a 100%',
                'dias_credito.max' => 'Los días de crédito no pueden exceder 365 días',
                'calificacion.min' => 'La calificación mínima es 1',
                'calificacion.max' => 'La calificación máxima es 5',
            ]);

            // Si la validación falla, retornar errores con código 422 (Unprocessable Entity)
            // No se ejecuta ninguna operación en la base de datos
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Iniciar transacción para asegurar atomicidad de operaciones
            // Si alguna operación falla, todas se revierten (rollback)
            // Esto previene estados inconsistentes (persona sin proveedor o viceversa)
            DB::beginTransaction();

            // Crear registro de persona primero (relación padre)
            // La persona contiene los datos básicos compartidos
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
                'estado' => true, // Persona activa por defecto
            ]);

            // Crear registro de proveedor asociado a la persona
            // El proveedor contiene datos comerciales específicos
            $proveedor = Proveedor::create([
                'persona_id' => $persona->id,
                'codigo' => Proveedor::generarCodigo(), // Genera código automático (PROV000001, etc.)
                'tipo_proveedor' => $request->tipo_proveedor,
                'rubro' => $request->rubro,
                'limite_credito' => $request->limite_credito ?? 0,
                'credito_usado' => 0, // Inicia en 0, se actualiza con compras a crédito
                'dias_credito' => $request->dias_credito ?? 0,
                'descuento_general' => $request->descuento_general ?? 0,
                'cuenta_bancaria' => $request->cuenta_bancaria,
                'banco' => $request->banco,
                'nombre_contacto' => $request->nombre_contacto,
                'cargo_contacto' => $request->cargo_contacto,
                'telefono_contacto' => $request->telefono_contacto,
                'email_contacto' => $request->email_contacto,
                'observaciones' => $request->observaciones,
                'fecha_registro' => now(), // Fecha actual del sistema
                'calificacion' => $request->calificacion ?? Proveedor::CALIFICACION_BUENO, // Default: 3 (Bueno)
                'estado' => true, // Proveedor activo por defecto
            ]);

            // Confirmar transacción si todo fue exitoso
            // Ambos registros (persona y proveedor) se guardan permanentemente
            DB::commit();

            AuditLogger::insercion("Proveedor creado: {$proveedor->codigo} - {$proveedor->tipo_proveedor}", $proveedor->toArray());

            // Retornar respuesta exitosa con código 201 (Created)
            // Incluir datos de persona en respuesta mediante eager loading
            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => $proveedor->load('persona'),
            ], 201);
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            // Ningún cambio se guarda en la base de datos
            DB::rollBack();
            AuditLogger::error('Error al crear proveedor', $e);

            // Retornar error con código 500 (Internal Server Error)
            // En producción, considerar: Log::error('Error creando proveedor', ['error' => $e->getMessage()])
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proveedor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar los detalles de un proveedor específico
     *
     * Endpoint: GET /api/v1/proveedores/{id}
     *
     * Parámetros de URL:
     * - id: int - ID del proveedor a consultar
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "codigo": "PROV000001",
     *     "tipo_proveedor": "Producto",
     *     "rubro": "Tecnología",
     *     "persona": { ... },
     *     "credito_disponible": 50000.00,
     *     "calificacion_texto": "Excelente",
     *     ...
     *   }
     * }
     *
     * Respuesta error (404):
     * {
     *   "success": false,
     *   "message": "Proveedor no encontrado"
     * }
     *
     * @param  int  $id  ID del proveedor
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Buscar proveedor por ID con datos de persona mediante eager loading
            // Evita consultas adicionales al acceder a $proveedor->persona
            $proveedor = Proveedor::with('persona')->find($id);

            // Verificar si el proveedor existe
            // find() retorna null si no encuentra el registro
            if (! $proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado',
                ], 404); // HTTP 404 Not Found
            }

            // Retornar proveedor encontrado con código 200 (OK)
            // Los accessors del modelo se incluyen automáticamente (credito_disponible, etc.)
            return response()->json([
                'success' => true,
                'data' => $proveedor,
            ], 200);
        } catch (Exception $e) {
            // Capturar cualquier error inesperado
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el proveedor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar los datos de un proveedor existente
     *
     * Endpoint: PUT/PATCH /api/v1/proveedores/{id}
     *
     * Parámetros de URL:
     * - id: int - ID del proveedor a actualizar
     *
     * Body: Mismo formato que store()
     *
     * IMPORTANTE:
     * - No se puede modificar el código del proveedor (se genera automáticamente)
     * - No se modifica credito_usado (se gestiona mediante métodos del modelo)
     * - La actualización afecta tanto a persona como a proveedor
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "message": "Proveedor actualizado exitosamente",
     *   "data": { proveedor actualizado }
     * }
     *
     * @param  int  $id  ID del proveedor a actualizar
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Buscar proveedor con persona asociada
            $proveedor = Proveedor::with('persona')->find($id);

            // Verificar existencia del proveedor
            if (! $proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado',
                ], 404);
            }

            // Validar datos de entrada
            // NOTA: En unique se excluye el ID actual de persona para permitir mantener el mismo documento
            // Ejemplo: unique:personas,numero_documento,5 (excluye el registro con id=5)
            $validator = Validator::make($request->all(), [
                // Validaciones para datos de persona
                'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
                'numero_documento' => 'required|string|max:20|unique:personas,numero_documento,'.$proveedor->persona_id,
                'nombres' => 'required_if:tipo_documento,DNI,CE,PASAPORTE|nullable|string|max:100',
                'apellidos' => 'required_if:tipo_documento,DNI,CE,PASAPORTE|nullable|string|max:100',
                'razon_social' => 'required_if:tipo_documento,RUC|nullable|string|max:200',
                'email' => 'nullable|email|max:150',
                'telefono' => 'nullable|string|max:20',
                'celular' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:250',
                'ciudad' => 'nullable|string|max:100',
                'provincia' => 'nullable|string|max:100',

                // Validaciones para datos de proveedor
                'tipo_proveedor' => 'required|in:Producto,Servicio,Ambos',
                'rubro' => 'nullable|string|max:150',
                'limite_credito' => 'nullable|numeric|min:0',
                'dias_credito' => 'nullable|integer|min:0|max:365',
                'descuento_general' => 'nullable|numeric|min:0|max:100',
                'cuenta_bancaria' => 'nullable|string|max:50',
                'banco' => 'nullable|string|max:100',
                'nombre_contacto' => 'nullable|string|max:150',
                'cargo_contacto' => 'nullable|string|max:100',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:150',
                'calificacion' => 'nullable|integer|min:1|max:5',
                'observaciones' => 'nullable|string',
            ], [
                // Mensajes de error personalizados
                'numero_documento.unique' => 'Este número de documento ya está registrado',
                'nombres.required_if' => 'El nombre es obligatorio para personas naturales',
                'apellidos.required_if' => 'Los apellidos son obligatorios para personas naturales',
                'razon_social.required_if' => 'La razón social es obligatoria para RUC',
                'calificacion.min' => 'La calificación mínima es 1',
                'calificacion.max' => 'La calificación máxima es 5',
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
            // Ambos modelos (persona y proveedor) se actualizan juntos
            DB::beginTransaction();

            // Actualizar datos de la persona asociada
            // Mantiene sincronizados los datos base con el proveedor
            $proveedor->persona->update([
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
            ]);

            // Actualizar datos comerciales del proveedor
            // NOTA: No se actualiza codigo, credito_usado, fecha_registro, ultima_compra, total_compras
            // Estos campos se gestionan por el sistema
            $proveedor->update([
                'tipo_proveedor' => $request->tipo_proveedor,
                'rubro' => $request->rubro,
                'limite_credito' => $request->limite_credito ?? 0,
                'dias_credito' => $request->dias_credito ?? 0,
                'descuento_general' => $request->descuento_general ?? 0,
                'cuenta_bancaria' => $request->cuenta_bancaria,
                'banco' => $request->banco,
                'nombre_contacto' => $request->nombre_contacto,
                'cargo_contacto' => $request->cargo_contacto,
                'telefono_contacto' => $request->telefono_contacto,
                'email_contacto' => $request->email_contacto,
                'calificacion' => $request->calificacion ?? Proveedor::CALIFICACION_BUENO,
                'observaciones' => $request->observaciones,
            ]);

            // Confirmar transacción
            DB::commit();

            AuditLogger::actualizacion("Proveedor actualizado: {$proveedor->codigo}", $request->except(['imagen']));

            // Retornar respuesta exitosa con código 200 (OK)
            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $proveedor->load('persona'),
            ], 200);
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            AuditLogger::error("Error al actualizar proveedor ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el proveedor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alternar el estado de un proveedor (activar/desactivar)
     *
     * Endpoint: PATCH /api/v1/proveedores/{id}/toggle-estado
     *
     * Cambia el estado actual al opuesto:
     * - Si está activo (true), lo desactiva (false)
     * - Si está inactivo (false), lo activa (true)
     *
     * IMPORTANTE:
     * - El cambio de estado se sincroniza con la persona asociada
     * - Desactivar un proveedor no elimina su historial de compras
     * - Los proveedores inactivos no aparecen en selectores (filtro por estado)
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "message": "Proveedor activado/desactivado exitosamente",
     *   "data": { proveedor actualizado }
     * }
     *
     * @param  int  $id  ID del proveedor
     */
    public function toggleEstado(int $id): JsonResponse
    {
        try {
            // Buscar proveedor con persona
            $proveedor = Proveedor::with('persona')->find($id);

            // Verificar existencia
            if (! $proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado',
                ], 404);
            }

            // Iniciar transacción
            DB::beginTransaction();

            // Calcular nuevo estado (toggle: true -> false, false -> true)
            // Operador de negación (!) invierte el booleano
            $nuevoEstado = ! $proveedor->estado;

            // Actualizar estado del proveedor
            $proveedor->update(['estado' => $nuevoEstado]);

            // Sincronizar estado con persona asociada
            // Mantiene consistencia entre ambas tablas
            $proveedor->persona->update(['estado' => $nuevoEstado]);

            // Confirmar transacción
            DB::commit();

            AuditLogger::actualizacion("Cambio estado proveedor ID {$id} a ".($nuevoEstado ? 'Activo' : 'Inactivo'));

            // Mensaje dinámico según el nuevo estado
            // Operador ternario: condición ? si_verdadero : si_falso
            $mensaje = $nuevoEstado
                ? 'Proveedor activado exitosamente'
                : 'Proveedor desactivado exitosamente';

            // Retornar respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $proveedor->load('persona'),
            ], 200);
        } catch (Exception $e) {
            // Revertir en caso de error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del proveedor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar código automático para nuevo proveedor
     *
     * Endpoint: GET /api/v1/proveedores/generar-codigo
     *
     * Genera el siguiente código secuencial disponible.
     * Formato: PROV000001, PROV000002, etc.
     *
     * Útil para:
     * - Previsualizar el código antes de crear el proveedor
     * - Mostrar el código en formularios de creación
     * - Validar secuencia de códigos
     *
     * IMPORTANTE:
     * - El código real se genera al momento de crear el proveedor
     * - Esta es solo una vista previa
     * - Usa transacciones y locks para evitar duplicados en creación concurrente
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": {
     *     "codigo": "PROV000123"
     *   }
     * }
     */
    public function generarCodigo(): JsonResponse
    {
        try {
            // Invocar método estático del modelo para generar código
            // El método usa transacciones y locks para garantizar unicidad
            $codigo = Proveedor::generarCodigo();

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
     * Eliminar (desactivar) un proveedor
     *
     * Endpoint: DELETE /api/v1/proveedores/{id}
     *
     * IMPORTANTE - SOFT DELETE:
     * - NO elimina físicamente el registro de la base de datos
     * - Solo cambia el estado a false (inactivo)
     * - Preserva el historial de compras y relaciones
     * - Mantiene integridad referencial con órdenes de compra
     *
     * Razones para soft delete:
     * 1. Auditoría: Mantener registro histórico completo
     * 2. Relaciones: Órdenes de compra históricas siguen referenciando al proveedor
     * 3. Reportes: Análisis histórico requiere datos completos
     * 4. Reversibilidad: Posibilidad de reactivar el proveedor
     *
     * Para eliminación física (no recomendado):
     * - Usar método delete() o forceDelete() directamente
     * - Verificar que no existan relaciones dependientes
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "message": "Proveedor desactivado exitosamente"
     * }
     *
     * @param  int  $id  ID del proveedor a eliminar
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Buscar proveedor con persona
            $proveedor = Proveedor::with('persona')->find($id);

            // Verificar existencia
            if (! $proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado',
                ], 404);
            }

            // Iniciar transacción
            DB::beginTransaction();

            // Soft delete: cambiar estado a false en lugar de eliminar
            // Alternativa con SoftDeletes trait: $proveedor->delete()
            // Esto agregaría timestamp en deleted_at en lugar de cambiar estado
            $proveedor->update(['estado' => false]);

            // Sincronizar estado con persona asociada
            $proveedor->persona->update(['estado' => false]);

            // Confirmar transacción
            DB::commit();

            AuditLogger::eliminacion("Proveedor eliminado (desactivado) ID {$id}");

            return response()->json([
                'success' => true,
                'message' => 'Proveedor desactivado exitosamente',
            ], 200);
        } catch (Exception $e) {
            // Revertir en caso de error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proveedor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
