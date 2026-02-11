# Guía de Despliegue, Versionado y Flujos de Trabajo

Este documento define las reglas de despliegue, convenciones de versionado y estrategias de branches establecidas para el proyecto **Wiwa Tour Checkout Pro**.

## 1. Estrategia de Ramas (Branching Strategy)

El proyecto sigue una estructura de branches definida para separar el desarrollo activo, las versiones estables y los despliegues automáticos.

### Ramas Principales

| Rama   | Propósito                                                                 | Despliegue Automático |
| ------ | ------------------------------------------------------------------------- | --------------------- |
| `dev`  | Rama de desarrollo principal. Contiene las últimas características (WIP). | **Sí** → Entorno DEV  |
| `main` | Rama de producción estable. Código listo para clientes.                   | **Sí** → Entorno PROD |

### Flujo de Trabajo Recomendado

1.  **Desarrollo de Funcionalidades**:
    - Crear una rama temporal desde `dev` (ej: `feature/nueva-funcionalidad` o `fix/bug-checkout`).
    - Probar localmente.
    - Hacer Pull Request (PR) o Merge hacia `dev`.
    - **Automático**: Al hacer push a `dev`, el código se despliega en el servidor de desarrollo (`dev.wiwatour.com`).

2.  **Paso a Producción**:
    - Una vez validado en `dev`, crear un PR de `dev` hacia `main`.
    - **Importante**: Antes de mergear, asegúrate de actualizar la versión en `wiwa-tour-checkout-pro.php`.
    - **Automático**: Al hacer merge/push a `main`, se genera una Release en GitHub y se despliega en producción (`wiwatour.com`).

---

## 2. Convenciones de Commits

Utilizamos [Conventional Commits](https://www.conventionalcommits.org/) para mantener un historial limpio y facilitar la generación de changelogs.

**Formato:**
`tipo(alcance): descripción breve`

**Tipos Comunes:**

- `feat`: Una nueva funcionalidad (Feature).
- `fix`: Corrección de un error (Bug fix).
- `docs`: Cambios en documentación.
- `style`: Cambios de formato (espacios, puntos y comas, etc; no afecta lógica).
- `refactor`: Refactorización de código sin añadir features ni corregir bugs.
- `chore`: Tareas de mantenimiento (actualizar dependencias, scripts de build).

**Ejemplos:**

- `feat(checkout): agregar validación de teléfono en tiempo real`
- `fix(pasajeros): corregir error al guardar datos de niños`
- `style: formatear código según PSR-12`

---

## 3. Versionado y Releases

El proyecto maneja dos tipos de versiones:

### 1. Versión del Plugin (Manual)

En el archivo principal `wiwa-tour-checkout-pro.php`, existen dos indicadores de versión que deben mantenerse sincronizados manualmente antes de un release:

```php
// En el encabezado del archivo
* Version: 2.8.8

// En la constante definida
define('WIWA_CHECKOUT_VERSION', '2.8.8');
```

> **Nota**: Actualmente existe una discrepancia en el archivo (header dice 2.6.0, constante dice 2.8.8). Se recomienda unificar esto en el próximo commit.

### 2. Releases de GitHub (Automático)

- **Producción (`main`)**: El workflow `deploy-prod` genera automáticamente un tag y release usando el número de ejecución del workflow (ej: `v15`, `v16`).
  - _Recomendación_: Considerar cambiar esto en el futuro para usar tags semánticos manuales (v2.8.8) si se desea mayor control.

### 3. Pre-Releases (Etiquetas)

Si necesitas generar una versión de prueba empaquetada (ZIP) sin desplegar a producción:

- Crea un tag con formato `v*alpha*`, `v*beta*`, o `v*rc*` (ej: `v2.9.0-beta1`).
- Haz push del tag: `git push origin v2.9.0-beta1`.
- Esto disparará el workflow `pre-release.yml` que generará un ZIP descargable en GitHub.

---

## 4. Workflows de GitHub Actions

Archivos de configuración ubicados en `.github/workflows/`:

1.  **`deploy-dev.yml`**
    - **Trigger**: Push a la rama `dev`.
    - **Acción**: Despliega los archivos vía RSYNC al servidor de Hostinger (carpeta `dev.wiwatour.com`).
    - **Exclusiones**: `.git`, `.github`, `node_modules`, archivos de documentación, etc.

2.  **`deploy-prod.yml`**
    - **Trigger**: Push a la rama `main`.
    - **Acción 1**: Crea una Release en GitHub (v{run_number}).
    - **Acción 2**: Despliega los archivos vía RSYNC al servidor de Hostinger (carpeta `wiwatour.com`).

3.  **`pre-release.yml`**
    - **Trigger**: Push de tags (`v*alpha*`, `v*beta*`, `v*rc*`).
    - **Acción**: Crea una Pre-Release en GitHub con el código empaquetado en ZIP. Opcionalmente despliega a Dev.

---

## 5. Comandos Útiles

**Desplegar cambios a Dev rápidamente:**

```bash
git add .
git commit -m "fix(modulo): descripción del cambio"
git push origin dev
```

**Crear una versión pre-release para probar:**

```bash
git tag v2.9.0-beta1
git push origin v2.9.0-beta1
```
