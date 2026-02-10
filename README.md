# Wiwa Tour Checkout Pro v2.0.0

Sistema enterprise de checkout personalizado para tours, desarrollado para Wiwa Tours.

## Características Principales

- **Checkout de 2 Pasos**: Flujo optimizado con acordeones para pasajeros.
- **Soporte Multi-pasajero**: Campos dinámicos basados en la cantidad de viajeros.
- **Integración GeoIP (MaxMind)**:
  - Detección automática de ciudad y país.
  - Soporte para integración nativa de WooCommerce o API directa.
- **Multi-moneda (FOX Currency Switcher)**:
  - Selector de monedas integrado (Botones o Dropdown).
  - Actualización de precios en tiempo real.
- **Panel de Administración**:
  - Configuración general.
  - Gestor de campos (activar/desactivar).
  - Configuración de integraciones visual.

## Requisitos

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+
- Plugin "Travel Tour Booking" (OvaTheme) activo.
- (Opcional) FOX Currency Switcher Professional.

## Instalación

1. Subir la carpeta `wiwa-tour-checkout-pro` al directorio `/wp-content/plugins/`.
2. Activar el plugin desde el administrador de WordPress.
3. Configurar en **WooCommerce > Settings > Checkout > Wiwa Checkout** (o desde el menú lateral si está habilitado).

## Estructura de Archivos

```
wiwa-tour-checkout-pro/
├── admin/              # Lógica del panel de administración
├── assets/             # CSS y JS del frontend
├── includes/           # Clases PHP principales (Integraciones, Handlers)
├── languages/          # Archivos de traducción (.pot, .po, .mo)
├── templates/          # Vistas del checkout (formularios, resumen, thank you)
└── wiwa-tour-checkout-pro.php  # Archivo principal
```

## Créditos

Desarrollado por **Juan Pablo Misat** - [Connexis](http://connexis.co/)
