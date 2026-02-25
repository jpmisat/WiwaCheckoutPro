<?php
/**
 * Guests Fields
 * 
 * Wiwa override: wraps guest-type labels with WPML string translation
 * so that product-level labels (e.g. "Cantidad de viajeros") are translatable
 * instead of being hardcoded in the base language.
 */
defined( 'ABSPATH' ) || exit;

$product = OVATB()->options->get_product( $args );
if ( !$product ) return;

/**
 * Variables used in this file.
 *
 * @var array   $guests 
 * @var int     $min_guests
 * @var int     $max_guests
 * @var int     $numberof_guests
 */

/**
 * Helper: translate a guest-type label via WPML String Translation.
 * Registers the string on first encounter and returns the translation.
 */
if ( ! function_exists( 'wiwa_translate_guest_label' ) ) {
    function wiwa_translate_guest_label( $label ) {
        // Use WPML String Translation if available
        return apply_filters(
            'wpml_translate_single_string',
            $label,
            'wiwa-checkout',          // context / domain
            'guest_label_' . sanitize_key( $label )  // unique name
        );
    }
}
?>
<div class="form-field ovatb-guests-field">
    <?php if ( 1 === count( $guests ) ): ?>
        <?php $translated_label = wiwa_translate_guest_label( $guests[0]['label'] ); ?>
        <h3 class="ovatb-label ovatb-required">
            <?php echo esc_html( $translated_label ); ?>
            <?php if ( $guests[0]['desc'] ): ?>
                <span class="ovatb-description" aria-label="<?php echo esc_attr( $guests[0]['desc'] ); ?>">
                    <i class="ovatbicon-question" aria-hidden="true"></i>
                </span>
            <?php endif; ?>
        </h3>
        <div class="ovatb-guests-wrap">
            <div class="ovatb-guestspicker">
                <div class="guests-action">
                    <div class="guests-icon guests-minus">
                        <i class="ovatbicon-minus-sign" aria-hidden="true"></i>
                    </div>
                    <?php ovatb_text_input([
                        'type'          => 'number',
                        'class'         => 'guests-input ovatb-input-required ovatb_numberof_guests',
                        'name'          => $product->get_meta_key( 'numberof_'.$guests[0]['name'] ),
                        'value'         => ( $min_guests && !$numberof_guests ) ? $min_guests : $numberof_guests,
                        'placeholder'   => esc_html__( 'number of guests', 'ova-tour-booking' ),
                        'readonly'      => true,
                        'attrs'         => [
                            'min'           => $guests[0]['min'],
                            'max'           => $guests[0]['max'],
                            'data-name'     => $guests[0]['name'],
                            'data-label'    => $translated_label
                        ]
                    ]); ?>
                    <div class="guests-icon guests-plus">
                        <i class="ovatbicon-plus-sign" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <?php if ( OVATB()->options->guest_info_enabled( $product->get_id() ) ): // Guest information enabled ?>
                <div class="ovatb-guest-info">
                    <div class="guest-info-heading">
                        <?php esc_html_e( 'Please enter guest information', 'ova-tour-booking' ); ?>
                    </div>
                    <div class="guest-info-accordion"></div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <h3 class="ovatb-label ovatb-required">
            <?php esc_html_e( 'Number of Guests', 'ova-tour-booking' ); ?>
        </h3>
        <div class="ovatb-guests-wrap">
            <div class="ovatb-guestspicker">
                <?php ovatb_text_input([
                    'type'          => 'number',
                    'class'         => 'ovatb-input-required',
                    'name'          => $product->get_meta_key('numberof_guests'),
                    'value'         => ( $min_guests && !$numberof_guests ) ? $min_guests : $numberof_guests,
                    'placeholder'   => esc_html__( 'number of guests', 'ova-tour-booking' ),
                    'readonly'      => true
                ]); ?>
                <span class="ovatb-loader-guest">
                    <i class="ovatbicon-spinner-of-dots" aria-hidden="true"></i>
                </span>
            </div>
            <div class="ovatb-guestspicker-content">
                <?php foreach ( $guests as $k => $guest ): ?>
                    <?php $translated_guest_label = wiwa_translate_guest_label( $guest['label'] ); ?>
                    <div class="guests-item">
                        <div class="guests-info">
                            <div class="guests-label">
                                <h3 class="ovatb-label">
                                    <?php echo esc_html( $translated_guest_label ); ?>
                                    <?php if ( $guest['desc'] ): ?>
                                        <span class="ovatb-description" aria-label="<?php echo esc_attr( $guest['desc'] ); ?>">
                                            <i class="ovatbicon-question" aria-hidden="true"></i>
                                        </span>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <?php if ( 'yes' === $guest['show_price'] ): ?>
                                <div class="guests-price <?php echo esc_attr( $guest['name'] ); ?>-price">
                                    <?php printf( esc_html__( '%s/guest', 'ova-tour-booking' ), wp_kses_post( ovatb_price( $guest['price'] ) ) ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="guests-action">
                            <div class="guests-icon guests-minus">
                                <i class="ovatbicon-minus-sign" aria-hidden="true"></i>
                            </div>
                            <?php if ( $min_guests && !$numberof_guests && !$k ): ?>
                                <span class="guests-number numberof-<?php echo esc_attr( $guest['name'] ); ?>">
                                    <?php echo esc_html( $min_guests ); ?>
                                </span>
                                <?php ovatb_text_input([
                                    'type'  => 'hidden',
                                    'class' => 'guests-input',
                                    'name'  => $product->get_meta_key('numberof_'.$guest['name']),
                                    'value' => $min_guests,
                                    'attrs' => [
                                        'min'           => $guest['min'],
                                        'max'           => $guest['max'],
                                        'data-name'     => $guest['name'],
                                        'data-label'    => $translated_guest_label
                                    ]
                                ]);
                            else: ?>
                                <span class="guests-number numberof-<?php echo esc_attr( $guest['name'] ); ?>">
                                    <?php echo esc_html( $guest['min'] ); ?>
                                </span>
                                <?php ovatb_text_input([
                                    'type'  => 'hidden',
                                    'class' => 'guests-input',
                                    'name'  => $product->get_meta_key('numberof_'.$guest['name']),
                                    'value' => $guest['min'],
                                    'attrs' => [
                                        'min'           => $guest['min'],
                                        'max'           => $guest['max'],
                                        'data-name'     => $guest['name'],
                                        'data-label'    => $translated_guest_label
                                    ]
                                ]);
                            endif; ?>
                            <div class="guests-icon guests-plus">
                                <i class="ovatbicon-plus-sign" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ( OVATB()->options->guest_info_enabled( $product->get_id() ) ): ?>
                <div class="ovatb-guest-info">
                    <div class="guest-info-heading">
                        <?php esc_html_e( 'Please enter guest information', 'ova-tour-booking' ); ?>
                    </div>
                    <div class="guest-info-accordion"></div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php ovatb_text_input([
        'type'  => 'hidden',
        'name'  => $product->get_meta_key('min_guests'),
        'value' => $min_guests
    ]); ?>
    <?php ovatb_text_input([
        'type'  => 'hidden',
        'name'  => $product->get_meta_key('max_guests'),
        'value' => $max_guests
    ]); ?>
</div>
