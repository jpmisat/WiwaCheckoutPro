<?php
/**
 * Tour Product Booking Form (Wiwa Checkout Custom Override)
 */
defined( 'ABSPATH' ) || exit;

// Get Product
$product = OVATB()->options->get_product( $args );
if ( !$product ) return;

// Action
$action = apply_filters( 'ovatb_booking_form_action', $product->get_permalink() );

// Class
// In the original template it uses ovatb_get_meta_data. 
// If it's a global function it will work. Otherwise we might ignore it or use a default.
$class = function_exists('ovatb_get_meta_data') ? ovatb_get_meta_data( 'class', $args ) : '';

?>
<form id="booking-form" class="ovatb-form ovatb-booking-form <?php echo esc_attr( $class ); ?>" action="<?php echo esc_url( $action ); ?>" method="post" enctype="multipart/form-data" autocomplete="off">
	<div class="field-wrap">
		<?php
            /**
             * Hook: ovatb_booking_form
             */
            do_action( 'ovatb_booking_form', [ 'product_id' => $product->get_id() ] );
        ?>
	</div>
	
	<div id="ova-booking-actions-container" class="wiwa-modal-actions">
		<button type="button" id="btn-direct-checkout" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button ovatb-btn-submit wiwa-btn-primary ladda-button" data-style="zoom-in">
			<?php esc_html_e( 'Reservar', 'wiwa-checkout' ); ?>
		</button>
		
		<button type="button" id="btn-add-to-cart-soft" class="wiwa-btn-secondary ladda-button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-style="zoom-in">
			<span class="icon-cart"></span> <?php esc_html_e( 'Agregar al carrito', 'wiwa-checkout' ); ?>
		</button>
	</div>

	<?php /* Success overlay is injected dynamically by add-to-cart.js into <body> */ ?>

    <input
        type="hidden"
        name="ovatb-product-id"
        value="<?php echo esc_attr( $product->get_id() ); ?>"
    />
    <input
        type="hidden"
        name="ovatb-duration-type"
        value="<?php echo esc_attr( $product->get_duration() ); ?>"
    />
</form>
