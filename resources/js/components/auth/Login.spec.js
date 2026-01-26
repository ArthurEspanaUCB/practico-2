import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import Login from "./Login.vue";

// Mock axios
vi.mock("axios", () => ({
    default: {
        post: vi.fn(),
        defaults: {
            headers: {
                common: {},
            },
        },
    },
}));

import axios from "axios";

describe("Login Component", () => {
    let wrapper;

    beforeEach(() => {
        // Limpiar mocks antes de cada test
        vi.clearAllMocks();

        // Mock localStorage
        const localStorageMock = {
            getItem: vi.fn(),
            setItem: vi.fn(),
            removeItem: vi.fn(),
            clear: vi.fn(),
        };
        Object.defineProperty(window, "localStorage", {
            value: localStorageMock,
        });

        // Montar componente
        wrapper = mount(Login);
    });

    /**
     * Test: El formulario de login se renderiza correctamente
     */
    it("renders login form correctly", () => {
        // Verificar que existe el formulario
        expect(wrapper.find("form").exists()).toBe(true);

        // Verificar campos de email y password
        expect(wrapper.find('input[type="email"]').exists()).toBe(true);
        expect(wrapper.find('input[type="password"]').exists()).toBe(true);

        // Verificar botón de submit
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    /**
     * Test: El título del sistema está visible
     */
    it("displays system title", () => {
        expect(wrapper.text()).toContain("Sistema de Ventas");
    });

    /**
     * Test: El botón muestra "Iniciar Sesión" cuando no está cargando
     */
    it('shows "Iniciar Sesión" button text when not loading', () => {
        const button = wrapper.find('button[type="submit"]');
        expect(button.text()).toContain("Iniciar Sesión");
    });

    /**
     * Test: Se puede escribir en los campos de email y password
     */
    it("allows typing in email and password fields", async () => {
        const emailInput = wrapper.find('input[type="email"]');
        const passwordInput = wrapper.find('input[type="password"]');

        await emailInput.setValue("test@example.com");
        await passwordInput.setValue("password123");

        expect(emailInput.element.value).toBe("test@example.com");
        expect(passwordInput.element.value).toBe("password123");
    });

    /**
     * Test: Login exitoso emite evento 'login-success'
     */
    it("emits login-success event on successful login", async () => {
        // Mock respuesta exitosa
        axios.post.mockResolvedValueOnce({
            data: {
                success: true,
                data: {
                    token: "test-token",
                    user: {
                        id: 1,
                        name: "Test User",
                        email: "test@example.com",
                    },
                },
            },
        });

        // Llenar formulario
        await wrapper.find('input[type="email"]').setValue("test@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");

        // Enviar formulario
        await wrapper.find("form").trigger("submit.prevent");
        await flushPromises();

        // Verificar que se emitió el evento
        expect(wrapper.emitted("login-success")).toBeTruthy();
        expect(wrapper.emitted("login-success")[0][0]).toEqual({
            id: 1,
            name: "Test User",
            email: "test@example.com",
        });
    });

    /**
     * Test: Login fallido muestra mensaje de error
     */
    it("shows error message on failed login", async () => {
        // Mock respuesta de error 401
        axios.post.mockRejectedValueOnce({
            response: {
                status: 401,
                data: { message: "Credenciales incorrectas" },
            },
        });

        // Llenar formulario
        await wrapper.find('input[type="email"]').setValue("test@example.com");
        await wrapper.find('input[type="password"]').setValue("wrongpassword");

        // Enviar formulario
        await wrapper.find("form").trigger("submit.prevent");
        await flushPromises();

        // Verificar que se muestra el error
        expect(wrapper.text()).toContain("Credenciales incorrectas");
    });

    /**
     * Test: El botón se deshabilita durante la carga
     */
    it("disables submit button while loading", async () => {
        // Mock que nunca resuelve (simula carga lenta)
        axios.post.mockImplementationOnce(() => new Promise(() => {}));

        // Llenar formulario
        await wrapper.find('input[type="email"]').setValue("test@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");

        // Enviar formulario
        await wrapper.find("form").trigger("submit.prevent");

        // Verificar que el botón está deshabilitado
        const button = wrapper.find('button[type="submit"]');
        expect(button.attributes("disabled")).toBeDefined();
        expect(button.text()).toContain("Ingresando...");
    });

    /**
     * Test: Mensaje de error para usuario inactivo (403)
     */
    it("shows specific error for inactive user", async () => {
        // Mock respuesta 403
        axios.post.mockRejectedValueOnce({
            response: {
                status: 403,
                data: {
                    message:
                        "Su cuenta está inactiva. Contacte al administrador.",
                },
            },
        });

        // Llenar y enviar formulario
        await wrapper
            .find('input[type="email"]')
            .setValue("inactive@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");
        await wrapper.find("form").trigger("submit.prevent");
        await flushPromises();

        // Verificar mensaje
        expect(wrapper.text()).toContain("Su cuenta está inactiva");
    });
});
