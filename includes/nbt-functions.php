<?php
function yith_wapo_get_view( $view, $args = array(), $prefix = '' ) {

	$view_path = trailingslashit( YITH_WAPO_VIEWS_PATH ) . $prefix . $view;
	$template_path = trailingslashit(NBT_DIR).'views/'.$prefix . $view;
	extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

	if(	file_exists($template_path)){
		include $template_path;
	}elseif ( file_exists( $view_path ) ) {
		include $view_path;
	}
}

function get_locations(){

	$locations = [];
	$nbtLocations = get_option('nbt_locations', []);
	if(!empty($nbtLocations)){
		foreach($nbtLocations as $value){
			$locations[strtolower($value['location'])] = ucfirst($value['location']);
		}
		
	}

	return  $locations;
	
}

function get_default_location(){
	return strtolower(get_option('nbt_default_location'));
}

function get_price_html_display($price, $product) {
    $current_locations = isset($_POST['location_price']) ? sanitize_text_field($_POST['location_price']) : (isset($_COOKIE['location_price']) ? sanitize_text_field($_COOKIE['location_price']) : get_default_location());
    $locations = get_locations();
    $default_location = get_default_location();

    if ($current_locations != '') {
        if ($product && $product->is_type('simple')) {
            if ($current_locations != $default_location) {
                $regular_price = get_post_meta($product->get_id(), '_' . $current_locations . '_price', true);
                $sale_price = get_post_meta($product->get_id(), '_' . $current_locations . '_sale_price', true);
            } else {
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
            }
            if (!empty($sale_price)) {
                return sprintf('<del>%s</del><ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price), wc_price($sale_price));
            } else {
                return sprintf('<ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price));
            }
        } elseif ($product && $product->is_type('variation')) {
            if ($current_locations != $default_location) {
                $regular_price = get_post_meta($product->get_id(), '_' . $current_locations . '_price', true);
                $sale_price = get_post_meta($product->get_id(), '_' . $current_locations . '_sale_price', true);
            } else {
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
            }
            if (!empty($sale_price)) {
                return sprintf('<del>%s</del><ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price), wc_price($sale_price));
            } elseif (!empty($regular_price)) {
                return sprintf('<ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price));
            } else {
                return $price;
            }
        }
    }
    if ($product->is_type('variable')) {
        $reg_price = '';
        $variations = $product->get_children();
        $reg_prices = array();
        $sale_prices = array();
        foreach ($variations as $value) {
            $single_variation = new WC_Product_Variation($value);
            if ($current_locations != $default_location) {
                $variation_regular = get_post_meta($single_variation->get_id(), '_' . $current_locations . '_price', true);
                $variation_sale = get_post_meta($single_variation->get_id(), '_' . $current_locations . '_sale_price', true);
            } else {
                $variation_regular = $single_variation->get_regular_price();
                $variation_sale = $single_variation->get_price();
            }
            if ($variation_regular !== '' && $variation_regular !== false) {
                $reg_prices[] = floatval($variation_regular);
            }
            if ($variation_sale !== '' && $variation_sale !== false) {
                $sale_prices[] = floatval($variation_sale);
            }
        }
        if (!empty($reg_prices)) {
            sort($reg_prices);
            $min_price = $reg_prices[0];
            $max_price = $reg_prices[count($reg_prices) - 1];
            if ($min_price == $max_price) {
                $reg_price = wc_price($min_price);
            } else {
                $reg_price = wc_format_price_range($min_price, $max_price);
            }
        } else {
            $reg_price = '';
        }
        if (!empty($sale_prices)) {
            sort($sale_prices);
            $min_sale = $sale_prices[0];
            $max_sale = $sale_prices[count($sale_prices) - 1];
            if ($min_sale == $max_sale) {
                $sale_price = wc_price($min_sale);
            } else {
                $sale_price = wc_format_price_range($min_sale, $max_sale);
            }
        } else {
            $sale_price = '';
        }
        $suffix = $product->get_price_suffix($price);
        if ($sale_price && $reg_price && $sale_price !== $reg_price) {
            return wc_format_sale_price($reg_price, $sale_price) . $suffix;
        } elseif ($reg_price) {
            return $reg_price . $suffix;
        } else {
            return $price;
        }
    }
    // Return regular price if not on sale.
    return $price;
}