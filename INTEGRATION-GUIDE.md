# Guía de Integración

## 1. GeoIP (MaxMind)

El plugin soporta dos métodos para detectar la ubicación del usuario:

### A. Integración Nativa WooCommerce (Recomendado)
Si ya tienes configurada la clave de licencia de MaxMind en WooCommerce:
1. Ve a **WooCommerce > Ajustes > Integración > MaxMind Geolocation**.
2. Asegúrate de que la licencia esté activa y la base de datos descargada.
3. En **Wiwa Checkout > Integraciones**, selecciona "Usar configuración de WooCommerce".

### B. API Directa MaxMind
Si prefieres usar una conexión directa:
1. Ve a **Wiwa Checkout > Integraciones**.
2. Selecciona "Usar API propia de MaxMind".
3. Ingresa tu `Account ID` y `License Key`.
4. Haz clic en "Probar Conexión" para verificar.

## 2. FOX Currency Switcher (WOOCS)

Para activar la funcionalidad multi-moneda:

1. Asegúrate de tener instalado y activo el plugin **FOX - Currency Switcher Professional**.
2. Configura tus monedas en **WooCommerce > Ajustes > Moneda (FOX)**.
   - Define la moneda base (ej. COP).
   - Agrega monedas adicionales (USD, EUR, etc.).
   - Configura las tasas de cambio.
3. En **Wiwa Checkout > Integraciones**:
   - Activa "Mostrar selector de moneda".
   - Elige el estilo (Botones o Dropdown).
   
**Nota:** El plugin detectará automáticamente las banderas si usas los códigos ISO estándar (COP, USD, EUR, etc.).
