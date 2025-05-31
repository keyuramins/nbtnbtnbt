<?php
/**
 * Add-ons Container.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ProductAddOns\Templates
 * @version 3.0.4
 *
 * @var YITH_WAPO_Front $instance
 * @var WC_Product $product
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

$style_form_style = get_option( 'yith_wapo_style_form_style', 'theme' );
if ( $product instanceof WC_Product_Variable && empty( $product->get_default_attributes() ) ) {
	$product_price = 0;
} else {
	global $nbt_public;
	$product_price = $nbt_public->yith_wapo_product_price( yit_get_display_price( $product ), $product );
}

$default_product_price = $product->get_price();

$locations = function_exists('get_locations') ? get_locations() : [];
$current_location = isset($_POST['location_price']) ? $_POST['location_price'] : (isset($_COOKIE['location_price']) ? $_COOKIE['location_price'] : '');

do_action( 'yith_wapo_before_main_container' );

?>

<!-- #yith-wapo-container -->
<div id="yith-wapo-container" class="yith-wapo-container yith-wapo-form-style-<?php echo esc_html( $style_form_style ) ?>" data-product-price="<?php echo esc_attr( $product_price ); ?>" data-default-product-price="<?php echo esc_attr( $product_price ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() )  ?>">

	<?php 
	if($product->is_type('simple')){
			echo '<div class="nbt_display_price">';
			echo wc_price($product_price);
			echo '<small class="woocommerce-price-suffix"> incl GST </small>';
			echo '</div>';
	}
 	?>
	<?php $instance->print_blocks();
    ?>
</div>

<div class="nbt-location-selector-wrapper">
    <form id="nbt-location-selector-form" method="post">
        <select name="location_price" class="nbt-location-selector">
            <?php foreach($locations as $key => $value): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_location, $key); ?>><?php echo esc_html($value); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php
do_action( 'yith_wapo_after_main_container' );
