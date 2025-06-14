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
	//Hide our custom price block for simple products
	// if($product->is_type('simple')){
	// 	if(function_exists('get_price_html_display')) {
	// 		echo '<div class="nbt_display_price">';
	// 		echo get_price_html_display($product_price, $product);
	// 		echo '<small class="woocommerce-price-suffix"> incl GST </small>';
	// 		echo '</div>';
	// 	} else {
	// 		echo '<div class="nbt_display_price">';
	// 		echo wc_price($product_price);
	// 		echo '<small class="woocommerce-price-suffix"> incl GST </small>';
	// 		echo '</div>';
	// 	}
	// }
  	?>
	<?php $instance->print_blocks();
    ?>
</div>

<?php 
// Output regular and sale price as JS variables for simple products
global $product;
if ($product->is_type('simple')) {
    $regular_price = floatval($product->get_regular_price());
    $sale_price = floatval($product->get_sale_price());
    echo "<script>window.NBT_SIMPLE_PRODUCT_PRICES = {regular: $regular_price, sale: $sale_price};</script>";
}

// Only for variable products
if ($product->is_type('variable')) {
    $has_addons = false;
    // Preferred: Use YITH API if available
    if (function_exists('YITH_WAPO')) {
        $addons = YITH_WAPO()->get_product_addons($product->get_id());
        if (!empty($addons)) {
            $has_addons = true;
        }
    }
    // Fallback: Check post meta for blocks
    $blocks = get_post_meta($product->get_id(), '_yith_wapo_blocks', true);
    if (!empty($blocks)) {
        $has_addons = true;
    }
    // If no add-ons, display the price
    if (!$has_addons) {
        if (function_exists('get_price_html_display')) {
            echo '<div class="nbt_display_price">';
            echo get_price_html_display($product_price, $product);
            echo '<small class="woocommerce-price-suffix"> incl GST </small>';
            echo '</div>';
        } else {
            echo '<div class="nbt_display_price">';
            echo wc_price($product_price);
            echo '<small class="woocommerce-price-suffix"> incl GST </small>';
            echo '</div>';
        }
    }
}
?>

<?php
do_action( 'yith_wapo_after_main_container' );
