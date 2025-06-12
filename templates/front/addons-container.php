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
	// Hide our custom price block for simple products
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
?>
<script>
(function() {
  // Only run for simple products
  if (!window.NBT_SIMPLE_PRODUCT_PRICES) return;
  var reg = window.NBT_SIMPLE_PRODUCT_PRICES.regular;
  var sale = window.NBT_SIMPLE_PRODUCT_PRICES.sale;

  function formatPrice(amount) {
    return '$' + amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function updatePriceBlock() {
    if (!sale || sale >= reg) return;
    var priceBlock = document.querySelector('.yith-wapo-product-price, .single_variation_wrap .price, .price');
    if (priceBlock) {
      var html = '<del>' + formatPrice(reg) + '</del> <ins>' + formatPrice(sale) + '</ins> <small class="woocommerce-price-suffix">incl GST</small>';
      priceBlock.innerHTML = html;
      console.log('[NBT TEST] Overriding price block with strikethrough HTML:', html);
    }
  }

  // Run after DOM ready and after AJAX completes
  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(updatePriceBlock, 200); // Let YITH run first
  });
  if (window.jQuery) {
    jQuery(document).ajaxComplete(function() {
      setTimeout(updatePriceBlock, 100); // Let YITH run first
    });
  }
})();
</script>

<?php // Debug: Log the gold price block at different times ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var priceBlock = document.querySelector('.yith-wapo-product-price, .single_variation_wrap .price, .price');
    if (priceBlock) {
      console.log('[NBT DEBUG] Price block on DOMContentLoaded:', priceBlock.innerHTML);
    } else {
      console.log('[NBT DEBUG] Price block not found on DOMContentLoaded');
    }
    setTimeout(function() {
      var priceBlock2 = document.querySelector('.yith-wapo-product-price, .single_variation_wrap .price, .price');
      if (priceBlock2) {
        console.log('[NBT DEBUG] Price block after 1s:', priceBlock2.innerHTML);
      } else {
        console.log('[NBT DEBUG] Price block not found after 1s');
      }
    }, 1000);
  });
  // Log after any AJAX completes (jQuery required)
  if (window.jQuery) {
    jQuery(document).ajaxComplete(function() {
      var priceBlock3 = document.querySelector('.yith-wapo-product-price, .single_variation_wrap .price, .price');
      if (priceBlock3) {
        console.log('[NBT DEBUG] Price block after AJAX:', priceBlock3.innerHTML);
      } else {
        console.log('[NBT DEBUG] Price block not found after AJAX');
      }
    });
  }
</script>

<?php
do_action( 'yith_wapo_after_main_container' );
