# Reglas de Agente: Gestión de GitHub, Versionado y Despliegue

Este documento define las reglas **OBLIGATORIAS** que el Agente debe seguir al realizar cambios en el proyecto **Wiwa Tour Checkout Pro**. Estas reglas tienen prioridad y deben ejecutarse proactivamente.

## 1. Versionado Semántico (SemVer)

**CADA VEZ** que realices un cambio en el código (PHP, JS, CSS) que afecte la funcionalidad o corrija un error, DEBES actualizar la versión del plugin.

1.  **Archivos a Actualizar**:
    - `wiwa-tour-checkout-pro.php`: Actualizar en dos lugares:
      - Encabezado del plugin: `* Version: X.Y.Z`
      - Constante: `define('WIWA_CHECKOUT_VERSION', 'X.Y.Z');`
    - Asegúrate de que ambos coincidan **exactamente**.

2.  **Criterio de Incremento**:
    - **PATCH (x.x.Z)**: Bug fixes, correcciones menores, ajustes de estilo.
    - **MINOR (x.Y.0)**: Nuevas funcionalidades compatibles con lo anterior.
    - **MAJOR (X.0.0)**: Cambios que rompen compatibilidad.

## 2. Changelog (CHANGELOG.md)

**CADA VEZ** que actualices la versión, DEBES agregar una entrada en `CHANGELOG.md`.

1.  **Formato**:
    ```markdown
    ## [X.Y.Z] - YYYY-MM-DD

    ### Added

    - Descripción de nueva funcionalidad.

    ### Changed

    - Descripción de cambios en funcionalidad existente.

    ### Fixed

    - Descripción de correcciones de errores.
    ```
2.  Inserta la nueva versión arriba de la anterior (orden cronológico inverso).

## 3. Estrategia de Ramas (Git Flow)

1.  **Rama Base**: Trabaja siempre sobre **`dev`** por defecto, a menos que se indique lo contrario.
2.  **Features Grandes**:
    - Si la tarea es compleja o una nueva funcionalidad grande, crea una rama:
      `git checkout -b feature/nombre-descriptivo`
    - Trabaja en esa rama.
    - Al finalizar, haz merge a `dev`.
3.  **Prohibido**: NUNCA hagas commit directo a `main` sin autorización explícita de "release" o "despliegue a producción".

## 4. Commits y Mensajes

Usa **Conventional Commits** para todos los mensajes de commit.

- `feat(scope): descripción` (Nuevas características)
- `fix(scope): descripción` (Correcciones de errores)
- `docs(scope): descripción` (Cambios solo en documentación)
- `style(scope): descripción` (Formato, espacios, CSS que no cambia lógica)
- `refactor(scope): descripción` (Mejoras de código sin cambio de comportamiento)
- `chore(scope): descripción` (Tareas de build, dependencias, versionado)

Ejemplo: `fix(checkout): validar campo de teléfono en checkout`

## 5. Despliegue Automático (Auto-Deploy)

**Regla de Oro**: Si has realizado cambios, probado (o verificado que es seguro) y actualizado la versión/changelog, **DEBES** subir los cambios al repositorio remoto automáticamente al final de tu turno.

**Comandos a ejecutar proactivamente:**

1.  `git add .`
2.  `git commit -m "tipo(alcance): mensaje descriptivo"`
3.  `git push origin <rama_actual>` (Generalmente `dev`)

> **Nota**: El push a `dev` dispara automáticamente el GitHub Action de despliegue al entorno de desarrollo (`dev.wiwatour.com`). No necesitas hacer nada más para desplegar a dev.

## 6. Manejo de Errores en Despliegue

Si el comando `git push` falla (por ejemplo, por conflictos):

1.  Intenta hacer `git pull origin <rama> --rebase`.
2.  Resuelve los conflictos si el agente tiene contexto suficiente.
3.  Vuelve a intentar el push.
4.  Si persiste el error, notifica al usuario con detalles.

## Resumen del Flujo de Trabajo del Agente

En cada interacción donde se modifique código:

1.  Realizar cambios en el código.
2.  Incrementar versión en `wiwa-tour-checkout-pro.php`.
3.  Actualizar `CHANGELOG.md`.
4.  `git add .`
5.  `git commit -m "..."`
6.  `git push origin dev`

---

**Instrucción Final**: Sigue estas reglas rigurosamente. No esperes a que el usuario te pida "subir cambios" o "desplegar". Asume que si el trabajo está terminado, debe estar en el repositorio y en el servidor de desarrollo.
