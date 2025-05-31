<?php
/**
 * Product loop sale flash
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/sale-flash.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $product;

$location_price = isset($_POST['location_price']) ? sanitize_text_field($_POST['location_price']) : (isset($_COOKIE['location_price']) ? sanitize_text_field($_COOKIE['location_price']) : '');
$_sydney_sale_price = get_post_meta($product->get_id(), '_'.$location_price.'_sale_price', true);
$default_location = get_default_location();
if( $location_price != $default_location){ 
    if(!empty($_sydney_sale_price)){?>
    	<div class="badge-container is-larger absolute left top z-1 133">
    	<?php 
				$custom_text = get_theme_mod( 'sale_bubble_text' );
				$text        = $custom_text ? $custom_text : __( 'Sale!', 'woocommerce' );

			
			  if($product->is_type('variable')){
			  	$flag = false;

			  	foreach($_sydney_sale_price as $key => $vid){
			  			$sale_price = get_post_meta($key,  '_'.$location_price.'_sale_price', true);
				  		if(isset($sale_price) && $sale_price > 0){
				  			$flag = true;
				  		}
		  		}
		  		if($flag){
			  			echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product );
		  		} 
			  }else{
			  	
			  	 echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product ); 
			  } ?>
		
		</div>

    <?php }
  }else{?>

	<?php if ( $product->is_on_sale() ) : ?>

		<?php echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product ); ?>

		<?php
	endif;
}
/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
