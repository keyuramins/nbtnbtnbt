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

// --- YITH Add-on Price Display Logic for Variable Products ---
// If a variable product has YITH add-ons, suppress all default WooCommerce price displays
// except for the add-on table. Only show the price if there are NO add-ons.
if ($product->is_type('variable')) {
    global $wpdb;
    $product_id = (string) $product->get_id(); // YITH stores as string
    $table = $wpdb->prefix . 'yith_wapo_blocks';
    $blocks = $wpdb->get_results("SELECT settings FROM $table");
    $has_addons = true; // Assume product has add-ons unless proven otherwise
    foreach ($blocks as $block) {
        $settings = maybe_unserialize($block->settings);
        if (
            isset($settings['rules']['show_in_products']) &&
            is_array($settings['rules']['show_in_products']) &&
            in_array($product_id, $settings['rules']['show_in_products'], true)
        ) {
            $has_addons = true;
            break;
        } else {
            $has_addons = false;
        }
    }
    // Only show price if NO add-ons
    if (!$has_addons) {
        // Output a single price block with a placeholder for JS updates
        echo '<div class="nbt_display_price" id="nbt-variable-product-price">';
        if (function_exists('get_price_html_display')) {
            echo get_price_html_display($product_price, $product);
        } else {
            echo wc_price($product_price);
        }
        echo '<small class="woocommerce-price-suffix"> incl GST </small>';
        echo '</div>';
        // Add JS to update price on variation selection
        ?>
        <script>
        jQuery(document).ready(function($) {
            var $form = $('form.variations_form');
            $form.on('show_variation', function(event, variation) {
                var priceHtml = variation.price_html;
                if (priceHtml) {
                    $('#nbt-variable-product-price').html(priceHtml + '<small class="woocommerce-price-suffix"> incl GST </small>');
                }
            });
        });
        </script>
        <?php
    }
    // If $has_addons is true, do NOT show any other price (handled by YITH add-on table)
}

if ($product->is_type('variable') && $has_addons) {
    // Hide WooCommerce's variation price output when add-ons are present
    echo '<style>.woocommerce-variation-price { display: none !important; }</style>';
}
?>

<?php
do_action( 'yith_wapo_after_main_container' );
