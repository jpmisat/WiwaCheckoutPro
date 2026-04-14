# Changelog
All notable changes to this project will be documented in this file.

## [2.16.27] - 2026-04-14

### Fixed

- **Elementor CSS Override:** Elementor's generic `.page-content a { text-decoration: underline; }` CSS rule was overpowering Tailwind's internal `text-decoration-line` handling for some browsers. Added an explicit `text-decoration: none !important;` rule to `wiwa-cart-styles.css` directly targeting `.wiwa-cart-page a.no-underline` to permanently defeat WordPress/Elementor's forceful pseudo-underlines.

## [2.16.26] - 2026-04-14

### Style

- **Link Aesthetics:** Destroyed aggressive theme underlines on cart item titles and actions by enforcing `border-none shadow-none` alongside `no-underline hover:no-underline`.

## [2.16.25] - 2026-04-14

### Style

- **Link Aesthetics:** Removed generic theme underlines from cart item titles and the "Delete" action button by injecting `no-underline hover:no-underline` to ensure a cleaner modern look matching the mockups.

## [2.16.24] - 2026-04-14

### Fixed

- **FOUC & Rocket Loader Crash:** Hard-injected `tailwind.config` and the `cdn.tailwindcss.com` script directly into the exact top of the `<head>` execution chain via `print_critical_css` and attached `data-cfasync="false"` natively to both. This completely bypasses the WordPress enqueue stack (`wp_enqueue_script` / `wp_add_inline_script`) which allowed Cloudflare Rocket Loader to decouple the configuration from the CDN engine, causing the frontend CSS to crash randomly or be fully replaced natively instead of applying the Stitch classes after the AJAX update.

## [2.16.23] - 2026-04-14

### Fixed

- **Tailwind Rendering Delayed:** Bypassed Cloudflare Rocket Loader (`data-cfasync="false"`) on the `cdn.tailwindcss.com` script. This prevents Cloudflare from deferring the CSS compilation dynamically, ensuring the Cart UI paints styling instantly avoiding a flash of unstyled content or failed layouts on the live frontend.

## [2.16.22] - 2026-04-14

### Added

- **Elementor Override:** Added programmatic override hooking into `get_post_metadata` to selectively and completely disable Elementor rendering on the Cart page (`is_cart()`). This cleanly shields the raw Tailwind HTML templates from Elementor's wrapper containers without manual backend interventions.

## [2.16.21] - 2026-04-14

### Fixed

- **Cart Layout:** Reverted brittle manual 300-line CSS overrides in `wiwa-cart-styles.css` that broke the mobile experience.
- **Elementor Collisions:** Added `important: true` to the Tailwind configuration to cleanly force utility classes inside `.wiwa-cart-page` without manually rewriting layouts.

## [2.16.20] - 2026-04-13

### Fixed

- **Layout completamente roto por Elementor CSS:** Elementor forzaba `display: block` en todos los contenedores, sobreescribiendo las clases utilitarias de Tailwind CSS (`flex`, `lg:flex-row`, `lg:w-[65%]`). Refactorizado el CSS crítico del carrito en `wiwa-cart-styles.css` con ~300 líneas de reglas propias usando `!important` y selectores `[data-purpose]`, garantizando que el layout 2-columnas (items 65% + summary 35%) funcione independientemente de Tailwind CDN.
- **Imágenes de tour gigantes ocupando ancho completo:** Forzado sizing responsivo del thumbnail con `wiwa-thumb-wrap` (6rem mobile → 12rem desktop) usando `!important` para sobreescribir las dimensiones inline de WooCommerce.
- **Material Symbols (iconos) y Montserrat (fuente) no cargaban:** Elementor eliminaba silenciosamente los estilos externos enqueue'ados vía `wp_enqueue_style`. Implementada inyección directa de `<link>` tags en el `<head>` mediante `print_critical_css()` con detección robusta de la página de carrito por URL slug.

### Changed

- Todos los estilos de layout del carrito ahora son autónomos (CSS propio) en lugar de depender de que Tailwind CDN interprete las clases correctamente bajo Elementor.
- Header del carrito (título, subtítulo, barra verde) ahora tiene reglas CSS dedicadas con tipografía forzada.
- Botón de eliminar item ahora tiene estilos propios para desktop (texto link rojo) y mobile (icono en pill rojo).
- Cards de tour tienen padding, border-radius y box-shadow definidos mediante CSS propio.

## [2.16.19] - 2026-04-13

### Fixed

- **Tailwind CSS / Material Symbols / Montserrat no cargando en la página del carrito:** `is_cart()` de WooCommerce devolvía `false` durante `wp_enqueue_scripts` en la página `/carrito/` construida con Elementor, causando que el layout Stitch se renderizara sin framework CSS. Implementada detección multi-fallback de 5 métodos: (1) `is_cart()` nativo, (2) constante `WIWA_RENDERING_CART`, (3) comparación de `wc_get_page_id('cart')` incluyendo traducciones WPML via `icl_object_id`, (4) detección por URL slug `/carrito/`, `/cart/`, `/panier/`, `/warenkorb/`, (5) escaneo de shortcode `[wiwa_checkout_cart]` en contenido del post.

## [2.16.18] - 2026-04-13

### Added

- Implementados los métodos faltantes `checkout_cart()`, `checkout_form()` y `checkout_thankyou()` en `class-wiwa-shortcodes.php`. Estos shortcodes (`[wiwa_checkout_cart]`, `[wiwa_checkout_form]`, `[wiwa_checkout_thankyou]`) estaban registrados en el plugin principal pero carecían de funciones de renderizado, causando salida vacía silenciosa.
- `checkout_cart()` delega a `[woocommerce_cart]` y define la constante `WIWA_RENDERING_CART` para señalizar al asset loader.
- `checkout_form()` delega a `[woocommerce_checkout]` para reutilizar el flujo multi-step de Wiwa Checkout Handler.
- `checkout_thankyou()` valida la orden vía `order-received` y `key` de WooCommerce, con fallback visual de confirmación genérica.

### Changed

- Ampliada la detección de assets del carrito en `class-wiwa-assets.php`: ahora carga Tailwind CSS CDN, Material Symbols, Montserrat y los estilos custom no solo en la página nativa `is_cart()` de WooCommerce, sino también en cualquier página que contenga el shortcode `[wiwa_checkout_cart]` (vía constante `WIWA_RENDERING_CART` + escaneo de `has_shortcode()` en el contenido del post).

## [2.16.17] - 2026-02-25

### Added

- Soporte WPML para labels de campos de pasajero de OvaTourBooking (`class-tour-booking-integration.php`): los labels ("First Name", "Identity document", "Food preferences", etc.) y opciones de select ahora se registran automáticamente en WPML String Translation bajo el dominio `wiwa-checkout`.

## [2.16.16] - 2026-02-25

### Fixed

- Eliminado código de moneda duplicado en el subtotal del side-cart (`mini-cart.php`).
- Ocultada la fila "Subtotal" redundante en el sidebar del checkout cuando el desglose de depósito está activo.
- Añadido código de moneda activo (`COP`, `USD`, etc.) a "Remaining balance" y "Total booking value" en el sidebar.
- Corregido el registro de campos en WPML String Translation (`class-fields-manager.php`): ahora usa `get_default_fields()` como fallback cuando la opción `wiwa_checkout_fields` no existe en la BD.

## [2.16.15] - 2026-02-25

### Fixed

- Restaurado el registro del shortcode `[dynamic_deposit_currency]` añadiendo la llamada a `Wiwa_Shortcodes::init()` en el hook `init` del plugin principal. La clase y método existían pero nunca se ejecutaba el `add_shortcode()`.

## [2.16.14] - 2026-02-25

### Added

- Traducciones en español, inglés y francés para los nuevos textos del desglose de depósito: "You pay today", "Deposit", "Remaining balance", "Paid on the day of the tour at our offices.", "Total booking value".

## [2.16.13] - 2026-02-25

### Changed

- Rediseño completo de la sección de totales del checkout y carrito con jerarquía visual clara: tarjeta verde para depósito (pago de hoy), tarjeta ámbar para saldo pendiente, y línea informativa para el total general de la reserva.

## [2.16.12] - 2026-02-25

### Fixed

- Corregido el cálculo del total general (Total de la reserva) para que sume correctamente el depósito actual más el saldo pendiente, en lugar de mostrar sólo el monto del depósito, y se añadió este total faltante en la barra lateral del checkout.

## [2.16.11] - 2026-02-25

### Fixed
- **Cart & Sidebar Deposit Logic:** Removed hardcoded 30% deposit calculation overrides from `cart.php` and `sidebar-summary.php` that conflicted with OvaTourBooking's native deposit logic.
- **Pending Balances:** The Cart Page and Checkout Sidebar now accurately retrieve and display the `remaining_total` natively generated by OvaTourBooking, respecting any custom percentage per tour (e.g. 10%).

## [2.16.10] - 2026-02-25

### Fixed

- **Currency Conversion:** Registered missing hooks for `Wiwa_FOX_Integration` so `woocs_exchange_value` logic finally executes correctly during AJAX recalculation and cart item text filtering without needing to modify OvaTourBooking source files.

## [2.16.9] - 2026-02-25

### Added
- Auto-registration of custom checkout fields to WPML String Translation using `wpml_register_single_string`.
- Frontend WPML translation mapping for custom checkout fields fetched from `Wiwa_Fields_Manager`.

## [2.16.8] - 2026-02-25

### Fixed
- Hotfix: Restored `templates/ova-tour-booking/forms/booking-form.php` and the template locator hook which were mistakenly deleted in previous commit, causing the "Add to Cart" button to lose its custom styling.

## [2.16.7] - 2026-02-25

### Added
- Auto-registration of custom checkout fields to WPML String Translation using `wpml_register_single_string`.
- Frontend WPML translation mapping for custom checkout fields fetched from `Wiwa_Fields_Manager`.

### Fixed
- Replaced ineffective template override with robust data filter `ovatb_get_data_guests` to translate guest labels (e.g. "Cantidad de viajeros") before template rendering.
- Re-added `__()` wrappers to Spanish labels in `class-guest-fields.php` for robust WPML scanning. Wiwa Tour Checkout Pro

## [2.16.6] - 2026-02-25

### Fixed
- **Guest Label Translation:** The "Cantidad de viajeros" label in the booking modal was hardcoded from product meta and never translated. Created a template override (`templates/ova-tour-booking/forms/booking/guests.php`) that wraps guest-type labels with `wpml_translate_single_string`, and added an auto-registration hook that scans all tour products and registers guest labels with WPML String Translation. Also expanded the cart metadata cleanup in `clean_cart_item_data` to include French and English variants.

## [2.16.5] - 2026-02-25

### Fixed
- **Booking Modal Currency:** OvaTourBooking's price conversion (`ovatb_convert_price`) only supported CURCY and WPML-MC plugins. Added a filter hook in `Wiwa_FOX_Integration` to bridge WOOCS/FOX currency exchange rates into the booking modal, so that calendar day-prices, totals, deposits, and extra-service prices now display in the user-selected currency (e.g. GBP, EUR) instead of always showing base COP.

## [2.16.4] - 2026-02-25

### Fixed
- **Add to Cart Modal i18n:** Added 7 missing translatable strings to `.pot`, `.po` (fr_FR, es_ES, es_CO), and regenerated `.mo` binary files. The modal now correctly displays French translations for "Ajouté au panier !", "Voir le panier", "Réserver maintenant", and "Plus d'activités qui pourraient vous plaire" via WPML String Translation.

## [2.16.3] - 2026-02-25

### Changed
- **Add to Cart Translations:** Replaced hardcoded Spanish strings in the "Agregar al carrito" modal (`add-to-cart.js`) with localized WordPress variables passed from `class-wiwa-assets.php`. Also wrapped backend AJAX response strings in `class-ajax-handler.php` with the `__()` translation function using the `wiwa-checkout` domain. This ensures compatibility with WPML String Translation for multilingual setups.

## [2.16.2] - 2026-02-25

### Fixed
- **Step 1 Persistence:** Falla crítica donde los datos ingresados en el Paso 1 se borraban permanentemente al recargar la página o al navegar hacia atrás desde el Paso 2. Se implementó una solución de persistencia de datos híbrida (precarga de sesión en PHP + `sessionStorage` en el cliente como respaldo en tiempo real) para garantizar retención infalible de reservas y pasajeros.

## [2.16.1] - 2026-02-25

### Fixed
- **Checkout Display Issue:** Añadida una regla CSS para forzar la visibilidad del botón de "Confirmar y Pagar" (`#place_order`) en el paso 2 del checkout. Esto soluciona un problema donde WooCommerce ocultaba el botón al cambiar de divisa mediante el switcher FOX debido a la estructura HTML personalizada de Wiwa Checkout.

## [2.16.0] - 2026-02-24

### Added
- **Dynamic Deposit Currency Shortcode:** Añadido shortcode `[dynamic_deposit_currency]` para mostrar dinámicamente el porcentaje o monto fijo del depósito configurado en Ova Tour Booking, aplicando conversión automática de moneda mediante WOOCS (FOX) y traducciones vía WPML.

## [2.15.13] - 2026-02-24

### Changed
- **Internacionalización (i18n):** Se refactorizaron 263 instancias de cadenas estáticas locales a nivel de código base (PHP), pasando del Español al Inglés nativo para alinear el plugin con las mejores prácticas de WordPress ("English-First development").
- Se generaron los archivos de catálogo de traducción de WordPress (`.pot`, `.po` y `.mo`) dentro de la carpeta `languages/` para brindar soporte automático en Español (`es_ES` y `es_CO`) y Francés (`fr_FR`). WPML y Polylang ahora podrán interceptar de manera nativa sin fallos y detectar estos strings base en inglés y aplicar los `.mo` proporcionados sin recurrir a trucos de refactorización visual.

## [2.15.12] - 2026-02-24

### Fixed
- Hid the standard WooCommerce "Item removed. Undo?" notice on non-cart pages to avoid UI clutter when using the side cart and prevent unwanted redirects back to the cart page.

## [2.15.11] - 2026-02-24

### Fixed
- Fixed mobile booking button matching widths (Reservar vs Agregar).
- Replaced missing CSS variables in the success modal close button with literal hex colors to ensure the intended design is applied.
- Prevented JetPopup and Elementor popup close buttons from overlapping modal titles on mobile devices by adding appropriate padding.

## [2.15.10] - 2026-02-24

### Fixed
- **Mobile Booking Buttons Equal Size:** Both "Reservar" and "Agregar al carrito" buttons now have identical padding (14px), font-size (15px), and min-height (50px) on mobile for perfectly matched sizing.

### Changed
- **Close Button (Brand Colors):** The success overlay close button (×) now uses brand dark green background with white icon, always visible (no more invisible-until-hover behavior). Increased to 42px for better touch targets.
- **Mobile Success Overlay UX:** Tightened spacing in the topbar, reduced hero image height to 160px, added explicit min-height (46px) and padding to action buttons ("Ver carrito" / "Reservar ahora"), and improved tour card grid spacing on mobile.

## [2.15.9] - 2026-02-24

### Changed
- **Booking Modal Buttons Redesign:** Desktop buttons now always display inline (never stacked) with equal widths via `flex: 1` and `flex-wrap: nowrap`. On mobile, "Reservar" appears on top and "Agregar al carrito" on bottom via CSS `order` swap. Added subtle pulse glow animation on the primary CTA, inline SVG cart icon on the secondary button, increased font size to 15px, and improved touch targets with `min-height: 48px`.

## [2.15.8] - 2026-02-24

### Fixed
- **Phantom Pax Updates (Self-Healing):** Fixed the bug where clicking the `+` or `-` passenger buttons on a "ghost" cart item would throw an `Item not found` console error and freeze the button. The AJAX failure handler in `wiwa-mini-cart.js` now implements a self-healing contingency that automatically forces a `wc_fragment_refresh` (or a full page reload if on the cart/checkout page) to instantly sweep away the phantom item and resync the UI.

## [2.15.7] - 2026-02-24

### Added
- **Sync Guardian for WooCommerce:** Completely overhauled JS cart sync logic in `side-cart.js`. The DOM now listens to raw browser `storage` events to detect and synchronize cross-tab and cross-language cart modifications (bypassing WPML's localized AJAX constraints). Additionally acts as a safety net observing `wc_cart_hash` drifts to force native Checkout/Cart refreshes when the side-cart injects items quietly in the background.

## [2.15.6] - 2026-02-24

### Fixed
- **Elementor Cart vs Native Cart Sync:** Fixed the bug where the Custom Elementor Side Cart Widget was not synchronizing when users deleted items natively via the main `/cart/` table, and vice-versa. Integrated specific listeners for `elementor/menu-cart/product-removed` and `updated_wc_div` events to forcefully purge stale `sessionStorage` fragments and trigger two-way visual refreshes or hard reloads as needed.

## [2.15.5] - 2026-02-24

### Fixed
- **Dynamic Varnish Bypass:** Added a PHP dynamic handler in `class-cart-handler.php` connected to the `template_redirect` hook. It forces Varnish and caching plugins to skip caching the Cart and Checkout pages, regardless of the URL slug or language (`is_cart()`, `is_checkout()`). This drastically reduces the need for manual URL excludes.

## [2.15.4] - 2026-02-24

### Fixed
- **Side Cart Ghost Item:** Fixed an issue where removing an item directly from the Main Cart page left a "ghost" item in the side cart. Added a script that detects the standard WooCommerce `?removed_item=1` parameter on page load, clears the stale `sessionStorage` fragments, and forces an immediate side cart sync.

## [2.15.3] - 2026-02-24

### Fixed
- **Fatal Error Hotfix:** Added missing `prevent_varnish_cache_on_lang_switch()` method in `class-cart-handler.php` that was causing a fatal error.

## [2.15.2] - 2026-02-24

### Fixed
- **Add to Cart Modal Error:** Fixed an `Uncaught TypeError: o.removeClass is not a function` in WooCommerce core by providing a dummy `$('<button/>')` object instead of the global `$` when triggering `added_to_cart`. This prevented the date selection from reopening.
- **Side Cart Removal Desync:** Side cart now explicitly reloads the Cart and Checkout pages with a cache-busting timestamp `?t=` when an item is removed, ensuring the main page view stays synchronized.
- **Language Switch Cache Issues:** Added a footer script specifically for Cart and Checkout pages that appends a cache-busting timestamp `?t=` to all language switcher links. This forces Varnish to serve a fresh page when changing languages.

## [2.15.1] - 2026-02-24

### Fixed
- **Suggested Tours Intermittent:** Added fallback when `product_cat` taxonomy is empty — now also tries `tour_cat`, and if no categories found, queries any published product. Wrapped in try/catch to prevent modal breakage.
- **Side Cart Stale Cache (Varnish):** Added `no-store` + `Vary: Cookie` headers to WC fragment AJAX responses. Client-side: clear `wc_fragments` sessionStorage before triggering refresh, plus a delayed secondary refresh.

## [2.15.0] - 2026-02-24

### Added
- **Suggested Tours in Add-to-Cart Modal:** After adding a tour, the success overlay now shows 4 random tours from the same category with images, ratings, and prices.
- **Varnish Cache Compatibility:** AJAX add-to-cart responses now include `no-store` and `Vary: Cookie` headers to prevent CloudPanel Varnish from caching personalized data.

### Changed
- **Larger Product Image:** The success overlay image now uses `medium_large` (768px) instead of `thumbnail` (150px) for a crisp hero image.
- **Redesigned Overlay Layout:** New horizontal top bar with product info + CTA buttons, and a 4-column suggested tours grid below.
- **Premium Close Button:** Replaced `×` character with SVG X icon, white background, subtle border and shadow.
- **Responsive Design:** Modal slides up as a bottom sheet on mobile with 2-column tour grid.

## [2.14.2] - 2026-02-23

### Fixed
- **CRITICAL: Broken Add-to-Cart Buttons**: Fixed wrong AJAX action name in `add-to-cart.js` — was sending `wiwa_add_to_cart` instead of the registered `wiwa_ajax_add_to_cart`, causing 400 Bad Request errors. Both "Reservar" and "Agregar al carrito" buttons now work again.

## [2.14.1] - 2026-02-23

### Fixed
- **Checkout Persistence — Stale Data**: `saveInput` now persists empty values so cleared fields stay cleared after page reload instead of restoring stale data.
- **Checkout Persistence — Ghost Data**: sessionStorage keys are now cleaned up after successful step-1 submit, preventing old form data from auto-filling on subsequent bookings.
- **Cart Persistence — Empty Cart Cleanup**: `cart-persistence.js` now clears all stored cart input data when the cart page shows an empty cart state.
- **Cart Persistence — Hardcoded Version**: Removed outdated `v2.8.5` console log from `cart-persistence.js`.

## [2.14.0] - 2026-02-22

### Added
- **Standalone Success Popup**: The success message after "Agregar al carrito" is now a full-screen overlay appended to `<body>` instead of an in-modal layer. Features animated card entrance, backdrop blur, close on ESC/click/button, and responsive mobile layout.

### Changed
- **Modal Auto-Close**: The JetPopup/Elementor booking modal now closes automatically before displaying the success overlay, resolving layout and z-index constraints.
- **Success Overlay Design**: Premium card-based design with tour image, checkmark animation, branded badge, and dual action buttons (Ver carrito / Reservar ahora).

## [2.13.1] - 2026-02-22

### Fixed
- **Check-in Date Validation**: Fixed "Check-in date is required" error that occurred even when a date was selected. Root cause: OvaTour Booking names its field `ovatb_checkin_date`, but our JS/PHP was looking for `checkin_date`. Added fallback support for `ovatb_` prefixed POST variables (`ovatb_checkin_date`, `ovatb_checkout_date`, `ovatb_start_time`) in both the frontend validation and the AJAX handler.
- **Button Click Handler**: Changed "Reservar" button from relying on native form `submit` to an explicit `click` handler, preventing conflicts with the OvaTour Booking plugin's own form handling.
- **Modal Reset**: Fixed modal not fully resetting `.field-wrap` visibility when closing and reopening.

### Changed
- **Button Visual Redesign**: Removed off-brand purple color scheme. Both buttons now use the Wiwa brand green palette (`#1a3015`). "Reservar" is a solid gradient pill (primary CTA), "Agregar al carrito" is an outlined pill (secondary). Used CSS `order` to place "Reservar" on the right side for correct UX hierarchy.
- **Success Layer Colors**: Updated success layer buttons and accents from purple to brand green, matching the overall site identity.

## [2.13.0] - 2026-02-22

### Added
- **Dual Button Action**: Splitted the tour booking modal into two explicit actions: "Reservar" (Primary) and "Agregar al carrito" (Secondary).
- **Direct Checkout Flow**: The main "Reservar" button now skips the cart entirely and redirects instantly to the checkout page via AJAX, bypassing WooCommerce standard cart notices.
- **Premium Success View**: The "Agregar al carrito" button now triggers an elegant, modal-bound success layer featuring the tour's image, date, and a cross-selling section.
- **Template Override Architecture**: Added `ovatb_locate_template` filter in the main plugin file. Refactored the modal form HTML layout out of the core OvaTheme folder into `templates/ova-tour-booking/forms/booking-form.php` for safe updates.

### Changed
- **Tour Booking Form JS**: Completely rewrote `assets/js/add-to-cart.js` to intelligently handle `submit` intercepts for direct checkout and `click` for soft-adds, alongside modal resetting and success data population.
- **AJAX Response Payload**: Enhanced `wiwa_ajax_add_to_cart` response payload in `class-ajax-handler.php` with dynamic properties: `product_image`, `checkout_url`, and `product_date` to feed the new JavaScript success UI.

## [2.12.33] - 2026-02-22

### Fixed
- **Translation Domains:** Fixed multiple strings in `cart.php` and `mini-cart.php` (such as "Ver carrito", "Finalizar compra", "Envío", "Aplicar", "Ingresa tu código") that were incorrectly assigned to the base `woocommerce` text domain while actually containing Spanish source texts. This caused Loco Translate and WPML to overlook them, breaking translations. They are now correctly assigned to the `wiwa-checkout` domain.

## [2.12.32] - 2026-02-22

### Fixed
- **Empty Cart on Translated Pages:** Added explicit filters `woocommerce_cart_item_visible` and `woocommerce_widget_cart_item_visible` set to `true` to override Polylang/WPML behaviors that hide products in the Cart page when switching languages. This fixes the issue where the cart appeared empty on the Spanish/English cart page specifically.
- **Language Switch Ajax Drop:** Added `lang` query parameters to the AJAX url in `step-2.php` to prevent FOX currency switchers from losing the active WooCommerce translated session.

## [2.12.31] - 2026-02-22

### Fixed
- **Multilingual Cart Preservation:** Added explicit `lang` query parameters to `wiwaAjax.ajaxUrl` and `wiwaCheckout.ajaxUrl` scripts to ensure WPML/Polylang plugins load the correct WooCommerce session on translated checkout pages.
- **Session Initialization:** Forced `WC()->session->set_customer_session_cookie(true)` during the Add to Cart AJAX handler when a session doesn't exist to guarantee cart persistence across cross-domain language switchers.

## [2.12.30] - 2026-02-22

### Changed
- Refactorización de la persistencia de datos frontend en `checkout.js`: Se migró de `localStorage` a `sessionStorage`. Esto soluciona un problema crítico donde los campos del checkout persistían indefinidamente en el navegador bloqueando subsecuentes cargas de ciudad/país vía GeoIP. Ahora, los campos en caché se destruyen al cerrar la pestaña o el navegador, permitiendo iniciar nuevas compras de forma limpia. Se agregó script de limpieza para borrar rastros de implementaciones previas en `localStorage`.

## [2.12.29] - 2026-02-22

### Fixed
- Agregada corrección al panel de configuración de integraciones donde las casillas de verificación (checkboxes) deseleccionadas no se guardaban debido a la falta de inputs ocultos que pasaran el valor `0` a las opciones de WordPress.
- Se añadió un delay/retry recursivo en `geoip.js` cuando elegimos la estrategia YellowTree, asegurando un máximo de 10 reintentos si `geoip_detect` carga después de que la página haya levantado (arregla *"Wiwa GeoIP: geoip_detect object is not available"*).

## [2.12.28] - 2026-02-22

## [2.12.27] - 2026-02-22

### Fixed
- Corregido el mapeo de los datos geográficos en el frontend (`geoip.js`) para soportar la serialización especifica de la API JS de GeoIP Detect YellowTree (ej: `response.city.name` y `response.country.isoCode`).

## [2.12.26] - 2026-02-22

### Changed
- Refactorización completa de la integración de GeoIP: Se eliminó la dependencia paralela directa con MaxMind en el backend de PHP a favor de consumir de manera nativa la API de JavaScript inyectada por el plugin "GeoIP Detect" de YellowTree. 
- Modificado el script `geoip.js` para consultar la función `geoip2.city()` en lugar de consumir nuestro propio endpoint AJAX.

### Removed
- Eliminados los endpoints `wiwa_get_geoip` y `test_maxmind` orientados a retornar el país/ciudad vía Ajax, debido a que el Frontend maneja estas validaciones de manera local con la caché manejada por GeoIP Detect.
- Removida la lógica compleja de `Wiwa_GeoIP_Integration::detect_city` enfocada a MaxMind API directa.

## [2.12.25] - 2026-02-22

### Fixed
- Eliminados los textos "fallback" quemados en `side-cart.js` (`Tu carrito está vacío`, etc.) para garantizar que el script dependa 100% de la versión traducida inyectada por PHP.
- Añadido archivo de configuración `wpml-config.xml` para mejorar la compatibilidad y escaneo de cadenas por parte de WPML.
- Test: Verificación de acceso a consola para commits.

## [2.12.24] - 2026-02-22

### Changed
- Internacionalización completa de JavaScript y PHP templates: Se agregaron las funciones y variables localizadas de traducción de WordPress (`wiwa-checkout`) a los textos duros previamente encontrados en `checkout.js`, `add-to-cart.js` y `wiwa-mini-cart.js`.
- Corrección del *text domain* en `mini-cart.php` pasando de `wiwa-tour-checkout` a `wiwa-checkout` para compatibilidad con WPML.

## [2.12.23] - 2025-02-22

### Fixed
- Corregido el problema donde el campo `Ciudad` (`billing_city`) no se autocompletaba al usar la integración nativa de WooCommerce con MaxMind (ya que la mayoría de descargas gratuitas de WC solo incluyen datos a nivel país). Se añadió un "fallback" que, si la ciudad está vacía y el usuario ha ingresado las API keys de MaxMind propias en la pestaña "Integraciones" de Wiwa Checkout, consulta la de MaxMind API para rellenarlo.

## [2.12.22] - 2025-02-22

### Changed
- Modificada la persistencia de datos del checkout de contacto (Paso 1) para usar `localStorage` en lugar de `sessionStorage`, garantizando que la información del cliente persista entre sesiones del navegador.
- Añadido un script en la página de agradecimiento (Thank You) que borra automáticamente las llaves temporales de pasajeros (`sessionStorage`) tras una orden exitosa.

## [2.12.21] - 2025-02-22

### Fixed
- Corregida la detección de llaves de MaxMind para versiones de WooCommerce >= 3.9 (`woocommerce_maxmind_geolocation_settings`).
- Eliminado error 404 en la consola relacionado al archivo `geoip.js` faltante en el checkout.
- Creada función AJAX dinámica (`wiwa_get_geoip`) y archivo `geoip.js` para auto-completar los campos de ciudad y país en el frontend de forma segura contra cachés de página.

## [2.12.20] - 2025-02-22

### Fixed
- **Form Persistence**: Select2 fields (like "Tipo de Documento" and "Nacionalidad") now properly save their values on change and correctly update their UI visually when restored upon page reload.
- **Nationality Storage**: Passenger "Nacionalidad" now saves the full country name (e.g. "Colombia") instead of standard 2-letter country codes ("CO") in the order meta data.

## [2.12.19] - 2026-02-22

### Fixed
- Checkout field order in Desktop UI now strictly respects the vertical sorting order generated by the backend Field Manager. Replaced arbitrary left/right grid distribution with sequential flow.

## [2.12.18] - 2026-02-22

### Fixed
- Passenger information not saving correctly to backend orders. The guest data is now appropriately formatted and grouped by passenger type (e.g. `Adult`, `Child`, `Pax`) instead of as a flat array, mapping perfectly to Ova Tour Booking.

## [2.12.17] - 2026-02-22

### Changed
- Massively improved the mobile UI for the main cart page (`cart.php`), restructuring item cards into a compact grid layout on small screens, preventing the giant full-width images and fixing spacing to fit significantly more information comfortably.

## [2.12.16] - 2026-02-22

### Changed
- Refined mini-cart content container height to `90%` and reset list max-height to `100vh` base.

## [2.12.15] - 2026-02-22

### Changed
- Refined mini-cart content container height (switched to `95%`).

## [2.12.14] - 2026-02-22

### Changed
- Refined cart list maximal height calculation (switched from `100vh` to `95vh` base).

## [2.12.13] - 2026-02-22

### Changed
- Refactored cart header and cart item padding, gaps, and sizes to use relative, responsive `clamp()` functions instead of fixed `px` sizes or specific media queries.

## [2.12.12] - 2026-02-22

### Changed
- Increased top padding on the "Tu carrito" header title to move the separator down.
- Increased gap and padding inside cart item grid elements for a more spacious layout.

## [2.12.11] - 2026-02-22

### Changed
- Added Elementor mini-cart CSS override to force `margin: auto` and perfectly center the empty cart container vertically.

## [2.12.10] - 2026-02-22

### Changed
- Added `margin: auto` to mini-cart main container for improved centering.

## [2.12.9] - 2026-02-22

### Changed
- Refined empty cart content padding and margins.
- Adjusted mobile mini-cart button bottom clearance.

## [2.12.8] - 2026-02-22

### Fixed
- **Empty Cart Centering:** `.wiwa-empty-cart` now uses `flex: 1` and fills the drawer height so the message/icon/button block is genuinely centered in the panel (both desktop and mobile).
- **Close Button Position:** Moved to `top: 20px` / `right: 18px` for better breathing room from the top edge.
- **Cart Title Spacing:** Title padding increased to `16px` top so it naturally clears the X button.
- **"Finalizar compra" Cut-off:** Button section padding on mobile bumped to `4rem` (was `3rem`) to guarantee the button is fully visible above the Android nav bar.

## [2.12.7] - 2026-02-22

### Fixed
- **Close Button:** Removed gray pill background and border — now fully transparent, just the icon fades on hover.
- **Cart Title Separator:** Title has slightly more bottom padding (`16px`) and margin below (`16px`) so the line sits a bit lower.
- **"Finalizar compra" cut-off:** Bottom padding on the buttons container bumped to `3rem` (`~1.2rem` more) so the button is never half-visible on mobile.

## [2.12.6] - 2026-02-22

### Changed

- **Cart Header Redesign:** The "Tu carrito" title now has proper top padding so it sits clearly below the close button without overlapping.
- **Close Button:** Redesigned to a clean `32px` circular pill with a subtle gray background and border, replacing the dropped-shadow white circle.
- **Footer Safe Padding:** Replaced fixed `calc()` with CSS `max()` so the bottom padding always gives at least 28-32px of space above the screen edge, regardless of device.

## [2.12.5] - 2026-02-21

### Fixed

- **Mobile Cart Close Button:** Forced the Elementor mini-cart close button (the X) to position absolutely inside the viewport so it doesn't get clipped or pushed out of bounds on mobile screens.

## [2.12.4] - 2026-02-21

### Changed

- **Mobile Cart Layout:** Aggressively optimized vertical spacing in the mini-cart for screens under 480px.
- **Cart Stepper Removal:** Completely removed the quantity stepper (+/- buttons) from the mini-cart overlay to prevent users from bypassing the required passenger detail validation on the main checkout page.

## [2.12.3] - 2026-02-21

### Fixed

- **Mobile Cart Margins:** Improved the spacing of the "Tu carrito" header to look better on smaller screens.
- **Cart Button Bottom Margin:** Fixed an issue where the `padding-bottom` on the cart buttons was sometimes overridden, causing them to sit too close to the bottom edge on some mobile browsers.
- **Quantity Loader:** Added instant visual feedback (animated spinner) when adjusting the number of travelers before the AJAX refresh finishes.

## [2.12.2] - 2026-02-21

### Fixed

- **Empty Cart Duplication:** Fixed `side-cart.js` fallback logic to prevent duplicating empty states in nested Elementor modals.
- **Cart Drawer Scroll:** Added Shopify-style scrolling container to the cart items list, keeping subtotal and checkout buttons fixed at the bottom.
- **Cart Drawer Title:** Added elegant "Tu carrito" header when items are present.

## [2.12.1] - 2026-02-13

### Fixed

- **Mini-Cart Duplication:** Replaced custom subtotal wrappers with standard `woocommerce-mini-cart__total` to prevent theme/plugin conflicts appending elements.
- **Empty Cart Fallback:** Updated JS to target `querySelectorAll` to handle themes mapping multiple empty carts correctly.
- **Visuals:** Increased cart item separation and subtotal font sizes. Removed price icon for cleaner display.

## [2.12.0] - 2026-02-13

### Changed

- **JS Migration:** Completely refactored `side-cart.js`, `hide-guest-form.js`, and `checkout.js` to Vanilla JS, removing jQuery dependencies where possible for better performance and modern browser compatibility.
- **Session Safety:** Refactored `sanitize_cart_session_data` (PHP) to be granular, removing only corrupt data instead of entire guest sessions.
- **Documentation:** Added `CHANGELOG_FORK.md` for the included `woocommerce-currency-switcher` fork.

## [2.11.13] - 2026-02-13

### Changed

- Restored branded "Empty Cart" UI in side cart (fixed JS regression).
- Enhanced Mini-Cart UI:
  - Added custom `mini-cart.php` template.
  - Improved item layout (Grid with thumbnail).
  - Cleaned up buttons (no underline) and separators.

## [2.11.12] - 2026-02-13

### Changed

- Refactored `side-cart.js` and `hide-guest-form.js` to Vanilla JS for better performance.

### Removed

- Deleted legacy script `checkout-scripts.js` (replaced by `checkout.js`).
- Deleted unused scripts `fox-sync.js` and `geoip.js`.
- Removed enqueue of `geoip.js`.

## [2.11.11] - 2026-02-13

### Removed

- Deleted redundant and legacy CSS files (`cart.css`, `checkout-styles.css`, `product-popup-override.css`, etc.)
- Removed enqueue of legacy `cart.css` from `class-checkout-handler.php`.

## [2.11.10] - 2026-02-13

### Removed

- Deleted legacy template files (`checkout-step-1.php`, `checkout-step-2.php`, `checkout-wrapper.php`, `order-summary.php`) from `templates/` root.

## [2.11.9] - 2024-05-24

### Improved

- **Frontend:** Refactored Empty Cart template for better code quality and centered the SVG icon.
- **Frontend:** Migrated Empty Cart inline styles to `assets/css/wiwa-cart-styles.css`.

## [2.11.8] - 2024-05-24

### Fixed

- **Core:** Resolved fatal error caused by redeclaration of `wiwa_extract_tour_meta` function.

## [2.11.7]

### Improved

- **Frontend:** Extracted inline CSS from `templates/cart/cart.php` to `assets/css/wiwa-cart-styles.css` for better performance and maintainability.
- **Frontend:** Cleaned up cart template file structure.

## [2.11.6] - 2026-02-13

### Fixed

- **Critical:** Moved `wiwa_extract_tour_meta` helper function from `cart.php` template to main plugin file to prevent "Cannot redeclare function" fatal error (HTTP 500) when template is loaded multiple times (e.g. via AJAX).

## [2.11.5] - 2026-02-13

### Fixed

- **Calc:** Fixed unit price display bug where tour pax count > 1 showed the total line price as the unit price. Now correctly recalculates `Unit Price = Line Total / Pax Count`.

## [2.11.4] - 2026-02-13

### Fixed

- **Typography Adjustments:** Reduced "Total de la reserva" font size from `text-5xl` to `text-4xl` (approx 2.25rem).
- **Card Price Sizing:** Further reduced the tour card special price size to `1.75rem` on desktop for better visual balance.

## [2.11.3] - 2026-02-13

### Fixed

- **Cart Price Formatting:** Split the WOOCS price/deposit text so the main amount is prominent and the "(10% deposit...)" text is smaller and unobtrusive.
- **Price Text Sizing:** Refined `.woocs_special_price_code` font size (max 2rem on desktop, 1.5rem on mobile) and added word-break to prevent overflow on long price strings.

## [2.11.2] - 2026-02-13

### Fixed

- **Cart Container Width:** Forced the cart container to be 100% full width (`max-width: 100%`) for a more spacious layout as requested.
- **Delete Button Color:** Added `!important` rule to force the "Eliminar" link and icon to be red (`#ef4444`).
- **Tour Duration Calculation:** Adjusted duration logic to `(checkout - checkin) + 1` to correctly reflect inclusive "Days".
- **Stepper Value:** Fixed the initial value of the quantity stepper to reflect the actual traveler count (from OvaTourBooking metadata) instead of the WC quantity (which is always 1).

## [2.11.1] - 2026-02-12

### Fixed

- **Remove "Popular" badge:** Removed the floating "Popular" badge from cart item cards.
- **Wider container:** Expanded cart page max-width to 1440px with proper padding for a fuller layout.
- **CTA "Proceder al Pago" contrast:** Changed to gradient green background with white text for proper UX contrast.
- **Price text sizing:** Tamed `woocs_special_price_code` font size to inherit from parent for harmonious proportions.
- **Stepper pill clean borders:** Removed red border artifacts from +/- buttons; now uses clean gray/transparent styling.
- **Quantity stepper AJAX for tours:** Fixed stepper to use custom `wiwa_update_tour_pax` AJAX handler for OvaTourBooking tour items (WC quantity is always 1 for tours, pax is stored in `numberof_{guest}` metadata).

### Added

- **Tour date & duration display:** Reads `checkin_date` and `checkout_date` from OvaTourBooking cart item data to show check-in date and calculated duration (days) with calendar/clock icons.
- **Currency code in sidebar total:** Shows the active WooCommerce currency code (e.g., COP) subtly next to the "Total de la reserva" price.
- **Guest detail extraction:** Extracts per-guest-type breakdown from OvaTourBooking for future use.

## [2.11.0] - 2026-02-12

### Added

- **Pixel-Perfect Stitch Cart:** Complete rewrite of `templates/cart/cart.php` to exactly match the Stitch HTML design (`code-stich.html`).
- **Material Symbols Icons:** Integrated Google Material Symbols for calendar, schedule, group, delete, and lock icons.
- **Montserrat Font:** Loaded via Google Fonts CDN for typography matching.
- **OvaTourBooking Meta Extraction:** Smart helper function `wiwa_extract_tour_meta()` to pull check-in date, duration, and traveler count from booking metadata.
- **Deposit & Pending Balance:** Per-item and sidebar-level deposit (30%) and pending balance calculations.

### Changed

- **Tailwind Scoped to Cart Page:** Tailwind CDN now only loads on `is_cart()` to avoid breaking other pages.
- **Tailwind Config Injected Inline:** Custom color tokens (`wiwa-green`, `wiwa-bg`, etc.) injected via `wp_add_inline_script` so utility classes work correctly.
- **CSS Rewritten:** `wiwa-cart-styles.css` cleaned up — removed unused old classes, focused on WC overrides, qty pill, and responsive polish.
- **Plugin Version:** Bumped to `2.11.0`.

## [2.10.9] - 2026-02-12

### Added

- **Stitch Sidebar:** Implemented pixel-perfect "Order Summary" sidebar matching the Stitch design.
- **Visuals:** Added premium styling for totals, deposit information (placeholder), and checkout button.

### Changed

- **Templates:** Completely replaced `woocommerce_cart_collaterals` in `cart.php` with custom HTML structure.
- **CSS:** Updated color tokens and component styles to match high-fidelity design specs.

## [2.10.8] - 2026-02-12

### Fixed

- **Cart HTML Structure:** Simplified "Remove" link and "Pax Control" markup for cleaner styling.
- **CSS Refinement:** Enforced "pill" shape for pax control using flexbox and strict overrides.
- **Remove Link:** Changed to a subtle "×" with no text, styled for minimalism.
- **Grid Stability:** Added min-width safety to details column to prevent layout breakage.

## [2.10.7] - 2026-02-12

### Fixed

- **Visual Sync:** Corrected "Viajeros" pill sizing to be compact and capsule-like.
- **Typography:** Adjusted Price hierarchy; reduced "Remaining" font size to avoid dominance.
- **Components:** Replaced text "Eliminar" with a cleaner text+icon style.
- **Layout:** Optimized grid columns (140px | 1fr | 200px) for better spacing.

## [2.10.6] - 2026-02-12

### Changed

- **Strict Stitch Sync:** Implemented Pixel-Perfect Card Grid.
- **Layout:** 3-Column Grid on Desktop (Image | Details | Actions).
- **Actions:** Pax Control moved to top-right, Price to bottom-right.
- **Visuals:** Updated Red "Eliminar" link, accurate Pill shape, and precise Typography.

## [2.10.5] - 2026-02-12

### Changed

- **Stitch Design Sync:** Implemented "Carrito - Wiwatour Final" high-fidelity design.
- **Visual Overhaul:** New color palette (Wiwa Green/Cream), Montserrat typography, and refined shadows.
- **Responsive Layout:** Optimized Flex/Grid layout for desktop (row) and mobile (column).
- **Components:** Redesigned Pax Control and Price hierarchy to match Stitch specifics.

## [2.10.4] - 2026-02-12

### Changed

- **Aesthetic Polish:** Refined "Card UI" with premium styling.
- **Pax Control:** Redesigned quantity selector to be minimalist and solid (removed cheap borders).
- **Typography:** Improved hierarchy in price display (Total vs Remaining) and metadata.
- **Organization:** Better grid spacing and alignment for card elements.

## [2.10.3] - 2026-02-12

### Changed (Emergency Redesign)

- **Template Rewrite:** Deleted legacy `<table>` structure in `cart.php`. Replaced with `div.wiwa-cart-grid` and semantic `.wiwa-cart-card` components.
- **CSS Overhaul:** Completely rewrote styles to support the new grid layout. Eliminated all table-based CSS hacks and "transparent background" issues.
- **Visuals:** Implemented deep shadow card design, fixed image aspect ratios, and polished typography.
- **JS Support:** Updated selectors to target new `.wiwa-cart-card` elements for AJAX updates.

## [2.10.2] - 2026-02-12

### Changed

- **Premium Card UI Refactor:** Completely replaced cart table structure with a modern Grid/Card layout. Removed all border opacities and table styles.
- **Cart Totals:** Redesigned as a sticky, clean panel with proper hierarchy and spacing.
- **Sidebar Cart:** Fixed positioning (forced right), z-index, and implemented matching card style for items.
- **Quantity Controls:** New pill-shaped design integrating +/- buttons and input.
- **AJAX Logic:** Implemented debounce (500ms) for quantity updates and **forced** cart update trigger to ensure accurate totals calculation on every change.

## [2.10.1] - 2026-02-12

### Fixed

- Complete CSS rewrite for cart page and sidebar cart with system-safe font stack (`-apple-system`, `Segoe UI`, `Roboto`).
- Sidebar cart now correctly positioned as a fixed right-anchored drawer with proper flex layout and scrollable product list.
- Main cart page rebuilt with clean 4-column card grid (thumbnail, name, qty, subtotal) and rounded card design.
- Removed broken `Wiwa_Tour_Booking_Integration` class references from `cart.php` that caused potential fatal errors.
- Simplified pax/passenger detection logic to use inline `numberof_*` metadata from cart items.
- Fixed quantity pill alignment, subtotal overflow, and product title wrapping.
- Improved mobile responsive breakpoints for cart cards and sidebar.
- Restored simple `×` character for remove button instead of unreliable SVG.

## [2.10.0] - 2026-02-12

### Added

- AJAX feedback loop for cart passenger controls: row locking, spinner state, and inline error feedback without full-page reload.
- Dynamic cart refresh payloads from backend endpoints, including line subtotal updates and regenerated totals HTML for instant UI sync.

### Changed

- Rebuilt `templates/cart/cart.php` into a card-first structure with premium hierarchy: larger tour title, native metadata chips, and branded pax controls.
- Redesigned `assets/css/wiwa-cart-styles.css` to a Wiwa brand system using manual palette (`#8d0e12`, `#d27800`, `#480000`, `#152a03`, `#f0dfcd`) with soft shadows, rounded cards, and responsive layout.
- Refactored `assets/js/wiwa-mini-cart.js` to handle both mini-cart and main-cart quantity actions through a unified AJAX flow.

### Fixed

- Main cart passenger +/- controls now recalculate totals and update DOM instantly.
- WooCommerce cart line subtotals and totals card now remain in sync after pax changes.
- Tour pax updates now use Tour Booking metadata (`numberof_*` and `numberof_guests`) instead of relying on cart quantity alone.

## [2.9.9] - 2026-02-12

### Changed

- **CSS Full Rewrite**: Migrated cart card layout to CSS Grid for reliable alignment. Fixed sidebar positioning (`left: auto`). Ensured quantity pill buttons are always visible with `min-width: 110px`. Added `wiwa-loading` spinner animation class.
- **PHP Cleanup**: Removed duplicate docblock comments. Enhanced `clean_cart_item_data` with case-insensitive matching and broader hidden key list (includes `numberof_guests`, `numberof_infant`, etc.).
- **JS Improvements**: Switched from inline opacity to CSS class-based loading state. Added error handling to AJAX calls. Cleaned up "Agregar al Carrito" button injection.

### Fixed

- Sidebar cart quantity +/- buttons being cut off or invisible.
- Main cart page not showing custom quantity pill — was displaying raw WC quantity input.
- Redundant metadata ("Cantidad de viajeros", "numberof_guests") still leaking through.
- "Actualizar Carrito" button now fully hidden via CSS.
- Mobile responsive layout for cart cards.

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
