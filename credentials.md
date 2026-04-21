
# Wiwatour Infrastructure Credentials

> [!WARNING]
> DO NOT commit this file publicly if the repository is public. Ignore it via `.gitignore` or keep it secure.

The following credentials have been generated and configured inside the `.env` files located manually on the Wiwatour VPS.

## n8n Credentials
- **Access URL:** https://n8n.wiwatour.com
- **UI Login User:** `webmaster@wiwatour.com`
- **UI Login Password:** `N8n:Wiwa26#`
- **Database User:** `n8n`
- **Database Password:** `WiwatourN8N_!DbP@ss_2026`
- **SMTP Account:** `info@wiwatour.com` (using Brevo via 760726001@smtp-brevo.com)
- **SMTP Key:** `bskM2Yjoo2kjyrG`
- **API Key (Wiwatour Update Key, Never Expires):** `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI4NWQ4NjU3My1lOWQ0LTRjNWYtODgxMC03NTA0ZTFiYzk1MjciLCJpc3MiOiJuOG4iLCJhdWQiOiJwdWJsaWMtYXBpIiwianRpIjoiYzE0ZTI3MzgtYzMwYi00MDI2LTkyMzYtOTNkZjc4OTYyZjcyIiwiaWF0IjoxNzc0NTgxMTA2fQ.w4BYii9Mi5B3-M-Mq-Gf_9i5JeDuJ4OgggPG96o7r6M`
- **Workflow Lucía V18 ID:** `3EnYmWTf4kjNK0PY`

## ~~Typebot Credentials~~ (DESINSTALADO 2026-03-25)
> [!NOTE]
> Typebot fue desinstalado completamente del servidor el 25 de marzo de 2026. Contenedores, base de datos, volúmenes e imágenes Docker eliminados.

## CloudPanel Reverse Proxy Sites
All reverse proxies use strong passwords internally for the CloudPanel vHost setups (e.g. `n8n.wiwatour.com`, `qdrant.wiwatour.com`):
- Password used internally for the isolated sites: `W1w@t0ur2026_Secure!`

## MinIO Object Storage
- **Access URL (S3 API):** https://s3.wiwatour.com
- **Console URL:** https://minio-console.wiwatour.com (Internal/Proxy)
- **Root User:** `webmaster@wiwatour.com`
- **Root Password:** `WiwatourMinIO_Secure_2026!`

## N8N Memory Database (PostgreSQL para Lucía)
- **Dockge Stack Name:** `lucia-memory-db`
- **Host (IP/Docker internal):** `lucia-memory-db` o `la IP de tu servidor`
- **Database Name:** `chat_history`
- **Database User:** `lucia_admin`
- **Database Password:** `WiwatourMemory_!DbP@ss_2026`
- **Port:** `5432`
- **Tablas:** `bot_config`, `chat_history_lucia`, `kb_file_registry`

## WiwaTour Automation Database (PostgreSQL para Operaciones)
- **Dockge Stack Name:** `wiwatour-automation-db`
- **Host (desde n8n/Docker):** `wiwatour-automation-db`
- **Database Name:** `wiwatour_ops`
- **Database User:** `wiwatour_admin`
- **Database Password:** `WiwatourOps_!DbP@ss_2026`
- **Port externo:** `5434` (interno Docker: `5432`)
- **n8n Credential ID:** `WAu43XXUCuF6DxuL`
- **n8n Credential Name:** `PostgreSQL WiwaTour Ops`
- **Red Docker:** `n8n_n8n`
- **Tablas:**
  - `payment_alerts_log` — Alertas de pagos fallidos/cancelados
  - `ecommerce_test_log` — Logs de pruebas E2E de la tienda
  - `daily_sales_report` — Reportes diarios de ventas (futuro)

## Qdrant Vector Database
- **Dockge Stack Name:** `qdrant`
- **Dashboard URL:** https://qdrant.wiwatour.com/dashboard/
- **Internal URL (from n8n/Docker):** `http://qdrant:6333`
- **REST API Port:** `6333`
- **gRPC Port:** `6334`
- **API Key:** `WiwatourQdrant_SecureKey_2026!`
- **Collection Name:** `wiwatour-lucia`
- **n8n Credential ID:** `DUNxtpKyujy15MTV`
- **n8n Credential Name:** `Qdrant Wiwatour`

## Browserless Chrome (Visual Testing)
- **Dashboard URL:** https://browser.wiwatour.com/?token=WiwaBrowserTest2026!
- **Token de acceso:** `WiwaBrowserTest2026!`
- **Puerto interno:** `3100` (mapeado a `3000` dentro del contenedor)
- **Docker Container:** `browserless` (Stack: `/opt/stacks/browserless/docker-compose.yml`)
- **Red Docker:** `n8n_n8n` (compartida con n8n)
- **Imagen:** `browserless/chrome:latest`
- **Endpoints API útiles:**
  - Pressure/Métricas: `https://browser.wiwatour.com/pressure?token=WiwaBrowserTest2026!`
  - Config: `https://browser.wiwatour.com/config?token=WiwaBrowserTest2026!`
  - Screenshot: `POST https://browser.wiwatour.com/screenshot?token=WiwaBrowserTest2026!`
  - PDF: `POST https://browser.wiwatour.com/pdf?token=WiwaBrowserTest2026!`
- **Límites configurados:**
  - Sesiones concurrentes: `5`
  - Timeout: `60s`
  - Cola máxima: `10`
  - Memoria: `2 GB` / CPU: `1.5 cores`
- **Funcionalidades del Dashboard:**
  - Editor de código Puppeteer/Playwright en vivo
  - Visor de sesiones activas (Session Viewer)
  - Ejecución de scripts de test en tiempo real
  - Métricas de presión y uso de recursos

## WiwaTour E-Commerce Test Monitor (n8n)
- **Workflow n8n ID:** `ZQanm9zcXq2WIFpJ`
- **Nombre:** `🛒 WiwaTour E-Commerce Test Monitor`
- **Nodos:** 28
- **Frecuencia:** 5 veces/día (pruebas generales), 3 veces/semana con pruebas de pago
- **Timezone:** `America/Bogota`
- **WooCommerce API:**
  - Consumer Key: `ck_916363af8a7470d013078215b072acab947148c4`
  - Consumer Secret: `cs_83f9d446d30c7e7428934ec1e2e284c0d1777859`
  - Store API Base: `https://wiwatour.com/wc/store/v1/`
- **WhatsApp Business API:**
  - Number ID: `861407612950603`
  - Número: `+57 320 5109287`
  - Token (Meta/Facebook): `EAATafbjtB0UBQ...` (ver token completo en n8n)
- **Base de Datos de Logs:**
  - Tabla: `ecommerce_test_log` (en PostgreSQL dedicado: `wiwatour-automation-db` / DB: `wiwatour_ops`)
  - Credencial n8n: `PostgreSQL WiwaTour Ops` (`WAu43XXUCuF6DxuL`)
  - Esquema: `test_date, test_type, schedule_time, phases (JSONB), overall_status, error_summary, total_duration_ms, notification_sent`
- **Pruebas que ejecuta:**
  1. Health Check del sitio
  2. Navegación a página de tienda
  3. Vista de producto
  4. Agregar al carrito (API)
  5. Proceso de checkout (API)
  6. Simulación de pago ePayco (3x/semana)
  7. Test visual con Browserless (detección de cache, errores CSS, elementos rotos)
- **Alertas:** WhatsApp (3 números) + Email vía Brevo

## Lucía Monitor Dashboard
- **URL:** https://monitor.wiwatour.com
- **Contraseña de acceso:** `WiwaMonitor2026!`
- **Archivos en servidor:**
  - Dashboard: `/home/monitor/htdocs/monitor.wiwatour.com/index.html`
  - API Proxy: `/home/monitor/htdocs/monitor.wiwatour.com/api.php`
- **PHP-FPM Pool:** `php8.3-fpm` en puerto `18001`
- **Funcionalidades:**
  - Toggle global On/Off de Lucía (consulta PostgreSQL `bot_config` en tiempo real)
  - Polling automático cada 10 segundos del estado del bot
  - Métricas de ejecuciones n8n, conversaciones Chatwoot, costos estimados
  - Estado de servicios (n8n, Chatwoot, Qdrant, Website)
  - Logout seguro con limpieza de sesión