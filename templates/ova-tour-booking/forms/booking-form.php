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

	<div id="ova-booking-success-layer" style="display:none;" class="wiwa-success-layer">
		<div class="wiwa-success-header">
			<div class="wiwa-success-image-col">
				<img id="success-tour-image" src="" alt="Tour" />
			</div>
			<div class="wiwa-success-details-col">
				<span class="success-message-text"><?php esc_html_e( '¡Agregado al carrito!', 'wiwa-checkout' ); ?></span>
				<h3 id="success-tour-name"><?php echo get_the_title( $product->get_id() ); ?></h3>
                <p id="success-tour-date" class="success-tour-meta"></p>
                <div class="success-actions">
                    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="btn-view-cart"><?php esc_html_e( 'Ver carrito', 'wiwa-checkout' ); ?></a>
                    <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="btn-reserve-now"><?php esc_html_e( 'Reservar ahora', 'wiwa-checkout' ); ?></a>
                </div>
			</div>
		</div>
		
		<div class="success-cross-sell">
			<h4><?php esc_html_e( 'Más actividades para explorar', 'wiwa-checkout' ); ?></h4>
            <div class="wiwa-cross-sell-wrapper">
			    <?php echo do_shortcode( '[products limit="3" columns="3" orderby="rand" visibility="visible"]' ); ?>
            </div>
		</div>
	</div>

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
