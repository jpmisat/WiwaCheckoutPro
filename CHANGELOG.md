# Changelog

## [2.9.8] - 2026-02-12

### Fixed

- **UI Repairs**: Fixed sidebar cart cutoff issue by enforcing fixed positioning and width.
- **UI Repairs**: Forced "Card" layout in main cart to fix broken table responsiveness.
- **UI Repairs**: Removed redundant "Cantidad de viajeros" metadata key to avoid duplication with quantity input.
- **UI Repairs**: Styled "Agregar al Carrito" button in booking popup to be prominent and consistent.

## [2.9.7] - 2026-02-12

### Added

- **Smart Pax System**: Custom quantity logic for OvaTourBooking.
  - **Simple Tours**: Direct passenger update via +/- buttons.
  - **Complex Tours**: Informative label for mixed passenger types.
- **Creative UI Overhaul**:
  - **Sidebar Cart**: Borderless design, sticky footer, premium typography.
  - **Main Cart**: Card-based layout, hidden table headers, "Receipt" style totals.

### Changed

- **JS Refactor**: Completely rewrote `wiwa-mini-cart.js` to handle conditional smart pax logic.
- **CSS Refactor**: Massive update to `wiwa-cart-styles.css` implementing the new design system.

## [2.8.7] - 2026-02-10

### Improved

- **Notice Styling**: Completely revamped WooCommerce notices (Success, Error, Info) with a modern, flat design. Used semantic colors (Emerald/Red/Blue) with soft backgrounds and left accents. Improved the "Undo" link style to be cleaner and more integrated.

## [2.8.6] - 2026-02-10

### Deployment

- **Retry**: Triggering new deployment with updated GitHub Actions `SSH_PORT` secret.

### Fixed

- **PHP Warnings**: Implemented `sanitize_cart_session_data` on `wp_loaded` to detect and remove malformed `ovatb_guest_info` from the session. This fixes the `foreach()` errors in OvaTourBooking.
- **Data Validation**: Added strict structural validation in `handle_custom_cart_updates` to prevent future data corruption.

## [2.8.5] - 2026-02-10

### Fixed

- **CSS Delivery**: Changed Critical CSS injection method to `wp_head` hook (priority 999) to ensure styles are printed directly in the head, bypassing potential queueing issues.
- **JS Verification**: Added console logging to cart persistence script to verify correct file loading.

## [2.8.4] - 2026-02-10

### Fixed

- **Critical CSS Injection**: Moved Z-index and Notice styling to inline CSS (`wp_add_inline_style`) to guarantee they load after all other stylesheets and override theme defaults.
- **Side Cart Positioning**: Enforced `position: fixed` for side cart wrapper to ensure Z-index works correctly in all contexts.

## [2.8.3] - 2026-02-10

### Fixed

- **CSS Specificity**: Forced WooCommerce notices styling with `!important` to override default theme styles.
- **Side Cart Z-Index**: Added `position: relative` and targeted more Elementor classes to ensure proper stacking context.

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
