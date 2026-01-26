<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test de Autenticación
 *
 * Estos tests verifican que el sistema de login/logout funciona correctamente.
 * Son críticos para el pipeline de CI/CD.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Usuario puede hacer login con credenciales válidas.
     *
     * Verifica que:
     * - La respuesta sea 200 OK
     * - Se retorne un token de acceso
     * - Se retorne información del usuario
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange: Crear un usuario de prueba
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'estado' => true,
        ]);

        // Act: Intentar login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: Verificar respuesta exitosa
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Usuario NO puede hacer login con credenciales inválidas.
     *
     * Verifica que:
     * - La respuesta sea 401 Unauthorized
     * - No se retorne un token
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        // Arrange: Crear un usuario
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'estado' => true,
        ]);

        // Act: Intentar login con contraseña incorrecta
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert: Verificar error de autenticación
        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /**
     * Test: Usuario inactivo NO puede hacer login.
     *
     * Verifica que usuarios con estado=false sean bloqueados.
     */
    public function test_inactive_user_cannot_login(): void
    {
        // Arrange: Crear un usuario inactivo
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
            'estado' => false,
        ]);

        // Act: Intentar login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        // Assert: Verificar que fue bloqueado (403 Forbidden)
        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /**
     * Test: Validación de campos requeridos en login.
     *
     * Verifica que email y password son obligatorios.
     */
    public function test_login_requires_email_and_password(): void
    {
        // Act: Intentar login sin datos
        $response = $this->postJson('/api/v1/auth/login', []);

        // Assert: Verificar error de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test: Usuario autenticado puede hacer logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        // Arrange: Crear usuario y obtener token
        $user = User::factory()->create([
            'estado' => true,
        ]);
        $token = $user->createToken('test_token')->plainTextToken;

        // Act: Hacer logout
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        // Assert: Verificar logout exitoso
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Usuario autenticado puede obtener su información.
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        // Arrange: Crear usuario y obtener token
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'profile@example.com',
            'estado' => true,
        ]);
        $token = $user->createToken('test_token')->plainTextToken;

        // Act: Obtener información del usuario
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/user');

        // Assert: Verificar respuesta
        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'profile@example.com')
            ->assertJsonPath('data.name', 'Test User');
    }

    /**
     * Test: Usuario no autenticado NO puede acceder a rutas protegidas.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        // Act: Intentar acceder sin token
        $response = $this->getJson('/api/v1/auth/user');

        // Assert: Verificar que es rechazado
        $response->assertStatus(401);
    }
}
