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
    $current_locations = isset($_POST['location_price']) ? sanitize_text_field($_POST['location_price']) : (isset($_COOKIE['location_price']) ? sanitize_text_field($_COOKIE['location_price']) : '');
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
        // --- Variable product price range for selected location ---
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            $reg_prices = array();
            $sale_prices = array();
            foreach ($variations as $value) {
                $single_variation = new WC_Product_Variation($value);
                if ($current_locations != $default_location) {
                    $reg = get_post_meta($single_variation->get_id(), '_' . $current_locations . '_price', true);
                    $sale = get_post_meta($single_variation->get_id(), '_' . $current_locations . '_sale_price', true);
                } else {
                    $reg = $single_variation->get_regular_price();
                    $sale = $single_variation->get_price();
                }
                if ($reg !== '' && $reg !== false) $reg_prices[] = floatval($reg);
                if ($sale !== '' && $sale !== false) $sale_prices[] = floatval($sale);
            }
            // Remove empty values
            $reg_prices = array_filter($reg_prices, function($v) { return $v > 0; });
            $sale_prices = array_filter($sale_prices, function($v) { return $v > 0; });
            if (!empty($sale_prices)) {
                sort($reg_prices);
                sort($sale_prices);
                $min_price = min($reg_prices);
                $max_price = max($reg_prices);
                $min_sale = min($sale_prices);
                $max_sale = max($sale_prices);
                $reg_price_html = ($min_price == $max_price) ? wc_price($min_price) : wc_format_price_range($min_price, $max_price);
                $sale_price_html = ($min_sale == $max_sale) ? wc_price($min_sale) : wc_format_price_range($min_sale, $max_sale);
                $suffix = $product->get_price_suffix($price);
                return wc_format_sale_price($reg_price_html, $sale_price_html) . $suffix;
            } elseif (!empty($reg_prices)) {
                sort($reg_prices);
                $min_price = min($reg_prices);
                $max_price = max($reg_prices);
                $reg_price_html = ($min_price == $max_price) ? wc_price($min_price) : wc_format_price_range($min_price, $max_price);
                return sprintf('<ins>%s</ins>%s', $reg_price_html, $product->get_price_suffix());
            } else {
                return $price;
            }
        }
    }
    // Fallback to original price
    return $price;
}