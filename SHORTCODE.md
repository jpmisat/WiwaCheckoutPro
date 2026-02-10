# SHORTCODE DEL CHECKOUT WIWA

## 📝 Shortcode Disponible

El plugin crea automáticamente el shortcode:

```
[wiwa_checkout]
```

## 🎯 Cómo Usar el Shortcode

### OPCIÓN 1: Página Automática (Recomendado)
El plugin crea automáticamente la página `/checkout-wiwa/` con el shortcode incluido.

**URL:** `https://tusitio.com/checkout-wiwa/`

### OPCIÓN 2: Crear Página Manual
Si quieres crear una página personalizada:

1. Ve a **Páginas → Añadir nueva**
2. Dale un nombre (ejemplo: "Mi Checkout Personalizado")
3. En el contenido, agrega:
   ```
   [wiwa_checkout]
   ```
4. Publica la página
5. La URL será: `https://tusitio.com/mi-checkout-personalizado/`

### OPCIÓN 3: Usar en Widget o Bloque
Puedes agregar el shortcode en:
- Widgets de texto
- Bloques de shortcode de Gutenberg
- Constructores de páginas (Elementor, etc.)

## 🔧 Configuración Avanzada

### Redirigir Checkout de WooCommerce
Si quieres que el checkout estándar de WooCommerce redirija a tu checkout personalizado, agrega esto en `functions.php`:

```php
add_filter('woocommerce_get_checkout_url', 'wiwa_custom_checkout_url');
function wiwa_custom_checkout_url($url) {
    return home_url('/checkout-wiwa/?step=1');
}
```

### Cambiar la Página de Checkout
Si creaste una página personalizada con otro slug:

```php
add_filter('woocommerce_get_checkout_url', 'wiwa_custom_checkout_url');
function wiwa_custom_checkout_url($url) {
    return home_url('/mi-checkout/?step=1'); // Tu slug personalizado
}
```

## ⚙️ Parámetros del Shortcode

El shortcode no requiere parámetros. Funciona automáticamente detectando:
- Tours en el carrito
- Paso actual (step=1 o step=2)
- Datos de la sesión

## 🧪 Verificar que Funciona

1. Agrega un tour al carrito
2. Ve a la URL del checkout: `https://tusitio.com/checkout-wiwa/?step=1`
3. Deberías ver el formulario de checkout personalizado

## 📋 Ejemplo de Uso en Elementor

1. Arrastra un widget de **Shortcode**
2. Pega: `[wiwa_checkout]`
3. Guarda y visualiza

## 💡 Notas Importantes

✅ El shortcode SOLO funciona si hay tours en el carrito
✅ Si el carrito está vacío, muestra un mensaje: "Tu carrito está vacío"
✅ El plugin debe estar ACTIVO para que el shortcode funcione
✅ WooCommerce y Travel Tour Booking deben estar activos

## 🔍 Troubleshooting

**Problema:** El shortcode muestra solo texto `[wiwa_checkout]`
**Solución:** El plugin no está activo. Ve a Plugins y actívalo.

**Problema:** El shortcode muestra "WooCommerce no está activo"
**Solución:** Activa WooCommerce.

**Problema:** El shortcode muestra "Tu carrito está vacío"
**Solución:** Agrega un tour al carrito antes de ir al checkout.

---

Desarrollado por: Juan Pablo Misat - Connexis
Web: http://connexis.co/
