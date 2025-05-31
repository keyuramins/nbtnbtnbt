<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.8.0
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );
$calculator_text          = '';
$current_locations = isset($_POST['location_price']) ? sanitize_text_field($_POST['location_price']) : (isset($_COOKIE['location_price']) ? sanitize_text_field($_COOKIE['location_price']) : '');
$default_location = get_default_location();
$nbt_locations = get_option('nbt_locations', []);
$selected_address = '';
$selected_location = $current_locations;
foreach ($nbt_locations as $loc) {
    if (strtolower($loc['location']) === strtolower($selected_location)) {
        $selected_address = $loc['address'];
        break;
    }
}
$default_location_label = '';
foreach ($nbt_locations as $loc) {
    if (strtolower($loc['location']) === strtolower($default_location)) {
        $default_location_label = $loc['location'];
        break;
    }
}

?>
<tr class="woocommerce-shipping-totals shipping">
	<th>Pickup Location: <?php echo esc_html($default_location_label); ?></th>
	<td data-title="Pickup Location">
		<?php if ( ! empty( $available_methods ) && is_array( $available_methods ) ) : ?>
			<ul id="shipping_method" class="woocommerce-shipping-methods">
				<?php foreach ( $available_methods as $method ) : ?>
					<li>

						<?php
						$method_pickup = explode(":", $method->id);
						$meta_data = $method->get_meta_data();

						if(isset($method_pickup[0]) && $method_pickup[0] == 'pickup_location' && !empty($nbt_locations) && strtolower($meta_data['pickup_location']) == strtolower($current_locations) ){
						if ( 1 < count( $available_methods ) ) {
							printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" checked />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id )); // WPCS: XSS ok.
						} else {
							printf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ) ); // WPCS: XSS ok.
						}
						
						 $ll = wc_cart_totals_shipping_method_label( $method );
							printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr( sanitize_title( $method->id ) ), esc_html($selected_address));
						do_action( 'woocommerce_after_shipping_rate', $method, $index );
						
						}
					

						do_action( 'woocommerce_after_shipping_rate', $method, $index );
						?>
					</li>
				
				<?php endforeach; ?>
			</ul>
			<?php if ( is_cart() ) : ?>
				<p class="woocommerce-shipping-destination" style="display: none;">
					<?php
					if ( $formatted_destination ) {
						// Translators: $s shipping destination.
						printf( esc_html__( 'Shipping to %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' );
						$calculator_text = esc_html__( 'Change address', 'woocommerce' );
					} else {
						echo wp_kses_post( apply_filters( 'woocommerce_shipping_estimate_html', __( 'Shipping options will be updated during checkout.', 'woocommerce' ) ) );
					}
					?>
				</p>
			<?php endif; ?>
			<?php
		elseif ( ! $has_calculated_shipping || ! $formatted_destination ) :
			if ( is_cart() && 'no' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', __( 'Shipping costs are calculated during checkout.', 'woocommerce' ) ) );
			} else {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'woocommerce' ) ) );
			}
		elseif ( ! is_cart() ) :
			echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) );
		else :
			echo wp_kses_post(
				/**
				 * Provides a means of overriding the default 'no shipping available' HTML string.
				 *
				 * @since 3.0.0
				 *
				 * @param string $html                  HTML message.
				 * @param string $formatted_destination The formatted shipping destination.
				 */
				apply_filters(
					'woocommerce_cart_no_shipping_available_html',
					// Translators: $s shipping destination.
					sprintf( esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ),
					$formatted_destination
				)
			);
			$calculator_text = esc_html__( 'Enter a different address', 'woocommerce' );
		endif;
		?>

		<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
		<?php endif; ?>

		<?php if ( $show_shipping_calculator ) : ?>
			<?php woocommerce_shipping_calculator( $calculator_text ); ?>
		<?php endif; ?>
	</td>
</tr>
<tr>
	<th>
		<?php $session = WC()->session; 
		// echo '<pre>';
		// print_r($session);
		// echo '</pre>';

		$pickup_date = WC()->session->get('pickup_date');
		?>
	 Preferred Pickup Date</th>
	<td data-title="Preferred Pickup Date">
		<input type="text" name="pickup_date" class="date-picker input-text " id="shipping_date" value="<?php echo $pickup_date; ?>" />
	</td>
</tr>