# Manual del Backend

## Panel de Control

El plugin agrega un menú principal llamado **Wiwa Checkout**.

### Pestaña: General
- **Activar Checkout**: Interruptor maestro para habilitar/deshabilitar el checkout personalizado. Si se desactiva, se usará el checkout estándar de WooCommerce.
- **Logo**: URL de la imagen que aparecerá en la cabecera del checkout.

### Pestaña: Campos
Aquí puedes gestionar los campos visibles en el formulario.
- **Campos de Facturación**: Nombre, Apellido, Email, Teléfono.
- **Campos de Pasajeros**: Nombre, Pasaporte, Nacionalidad, Fecha Nacimiento.
- Puedes cambiar la **Etiqueta** (Label), marcar como **Requerido** o **Desactivar** un campo.

### Pestaña: Integraciones
Configuración de servicios externos.
- **GeoIP**: Configura la detección de ciudad/país.
- **Multi-Moneda**: Configura el comportamiento del selector de monedas.

## Shortcode
El checkout se renderiza usando el shortcode: `[wiwa_checkout]`.
El plugin crea automáticamente una página `/checkout-wiwa/` con este shortcode al activarse.
