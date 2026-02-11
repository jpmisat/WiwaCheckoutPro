# Changelog

## [2.8.2] - 2026-02-10

### Fixed

- **PHP Warnings**: Resolved "string given" error in `class-ovatb-booking.php` by correctly handling `ovatb_guest_info` array in cart updates.
- **Data Persistence**: Passenger information is now saved in `sessionStorage` to prevent data loss on page reload.
- **Z-Index**: Increased Side Cart Z-Index to `MAX_INT` to prevent overlapping issues.

### Improved

- **Empty Cart UI**: Button now redirects to `/tours/` (WPML compatible) and has improved hover styles.
- **Notices**: Custom styling for WooCommerce messages to match the theme.

## [2.8.1] - 2026-02-10

### Added

- **Pax Editing in Cart**: Added ability to edit number of travelers (Adults/Children/etc.) directly in the cart table.
- **Z-Index Fix**: Forced Side Cart to appear above all other site elements (`z-index: 999999`).
- **Cart UI Improvements**: Styled Pax inputs and hid redundant "(view information)" link.

## [2.8.0] - 2026-02-108

### Added

- Nueva estructura de plugin modular.
- Integración completa con MaxMind GeoIP (Soporte WC y API Directa).
- Integración con FOX Currency Switcher (WOOCS).
- Backend visual para configuración de integraciones.
- Selector de moneda dinámico en el checkout (Botones/Dropdown).
- Campos de autocompletado para nacionalidad y teléfonos.
- Validación JS mejorada en el paso 1.

### Changed

- Refactorización completa del código base (OOP, Singleton).
- CSS modernizado para el checkout.
- Optimización de carga de assets.

### Fixed

- Compatibilidad con WooCommerce HPOS declarada explícitamente.

## [1.0.5] - Versión Anterior

- Checkout básico de 2 pasos.
- Campos personalizados simples.
