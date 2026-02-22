# Changelog

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
