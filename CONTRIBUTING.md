# Cómo Contribuir al Sistema de Ventas

## ¡Gracias por tu interés en contribuir!

Este documento establece las pautas para contribuir al proyecto y asegurar la calidad del código.

## Proceso de Pull Request (PR)

1.  **Fork** del repositorio y clónalo localmente.
2.  Crea una nueva rama para tu funcionalidad o corrección:
    ```bash
    git checkout -b feature/nombre-funcionalidad
    # o
    git checkout -b fix/descripcion-bug
    ```
3.  **Desarrollo**:
    -   Sigue el estilo de código definido en `.editorconfig`.
    -   Asegúrate de no incluir credenciales ni archivos `.env`.
    -   Si añades una nueva funcionalidad, por favor incluye pruebas.
4.  **Testing**:
    -   Ejecuta `php artisan test` para asegurar que no has roto nada.
5.  **Commit**:
    -   Usa mensajes de commit descriptivos y en imperativo (ej. "Añadir validación a producto", no "añadí validación").
6.  **Push** a tu fork y abre un Pull Request hacia la rama `main`.
7.  Completa la plantilla del Pull Request con toda la información solicitada.

## Estándares de Código

-   **PHP**: PSR-12.
-   **JavaScript/Vue**: Estilo estándar, indentación de 4 espacios.
-   **Commits**: Claros y concisos.

## Reporte de Bugs

Si encuentras un bug, por favor abre un Issue incluyendo:

-   Pasos para reproducir.
-   Comportamiento esperado vs real.
-   Screenshots si aplica.
