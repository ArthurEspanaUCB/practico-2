<?php

namespace Tests\Feature\Api;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test de API de Categorías
 *
 * Verifica operaciones CRUD del módulo de categorías.
 */
class CategoriaApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario autenticado para todos los tests
        $this->user = User::factory()->create(['estado' => true]);
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    /**
     * Helper: Headers de autenticación
     */
    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    /**
     * Test: Listar categorías (GET /api/v1/categorias)
     */
    public function test_can_list_categories(): void
    {
        // Arrange: Crear categorías de prueba
        Categoria::factory()->count(5)->create();

        // Act: Obtener lista
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/categorias');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test: Crear categoría (POST /api/v1/categorias)
     */
    public function test_can_create_category(): void
    {
        // Act: Crear categoría
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/categorias', [
                'nombre' => 'Categoría de Prueba',
                'descripcion' => 'Descripción de prueba',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Categoría de Prueba',
        ]);
    }

    /**
     * Test: Ver categoría individual (GET /api/v1/categorias/{id})
     */
    public function test_can_show_category(): void
    {
        // Arrange
        $categoria = Categoria::factory()->create([
            'nombre' => 'Categoría Específica',
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/categorias/{$categoria->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.nombre', 'Categoría Específica');
    }

    /**
     * Test: Actualizar categoría (PUT /api/v1/categorias/{id})
     */
    public function test_can_update_category(): void
    {
        // Arrange
        $categoria = Categoria::factory()->create([
            'nombre' => 'Nombre Original',
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/categorias/{$categoria->id}", [
                'nombre' => 'Nombre Actualizado',
                'descripcion' => 'Nueva descripción',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nombre' => 'Nombre Actualizado',
        ]);
    }

    /**
     * Test: Validación al crear categoría sin nombre
     */
    public function test_cannot_create_category_without_name(): void
    {
        // Act: Intentar crear sin nombre
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/categorias', [
                'descripcion' => 'Solo descripción',
            ]);

        // Assert: Error de validación
        $response->assertStatus(422);
    }

    /**
     * Test: Usuario no autenticado no puede acceder a categorías
     */
    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        // Act: Sin headers de auth
        $response = $this->getJson('/api/v1/categorias');

        // Assert
        $response->assertStatus(401);
    }
}
