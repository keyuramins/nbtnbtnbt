<?php

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;



/**
 * Admin Class
 * 
 * Handles all the different features and functions
 * for the front end pages.
 * 
 * @package nbtProductsLocations
 * @since 1.0.0
 */

class nbtAdmin{


	public $locations;
	public $default_locations;

    // Constructor to initialize the class properties

    public function __construct() {
        $this->locations = get_locations();
        $this->default_locations = get_default_location();
    }

	// Save location in conditional rules 
	function nbt_wapo_save_addon_settings($settings, $request){
		$settings['nbt_locations'] = $request['nbt_locations'];
		return $settings;
	}

	function woocommerce_simple_product_custom_fields(){
	    global $woocommerce, $post;
	    $product = wc_get_product($post->ID);
	    // Only show for simple products
	    if ($product && $product->is_type('simple')) {
	        echo '<div class="product_custom_field">';
	        if(!empty($this->locations)){
	            $locations = $this->locations;
	            unset($locations[$this->default_locations]);
	            foreach($locations as $key => $value){
	                if ($key != ''){    
	                    woocommerce_wp_text_input(
	                    array(
	                    'id' => '_'.$key.'_price',
						'name' => '_'.$key.'_price',
	                    'placeholder' => $value.' Price',
	                    'label' => __( $value.' Price ($)', 'woocommerce'),
	                    'desc_tip' => 'true'
	                    )
	                    );
	                    woocommerce_wp_text_input(
	                    array(
	                    'id' =>  '_'.$key.'_sale_price',
						'name' => '_'.$key.'_sale_price',
	                    'placeholder' => $value.' Sale Price',
	                    'label' => __($value.' Sale Price ($)', 'woocommerce'),
	                    'desc_tip' => 'true'
	                    )
	                    );
	                }           
	            }
	        }
	        echo '</div>';
	    }
	}

	//Adds price labels for variable products for locations in variations panel
	function woocommerce_variable_product_custom_fields($loop, $variation_data, $variation) {
	    if(!empty($this->locations)){
	    	$locations = $this->locations;
	    	unset($locations[$this->default_locations]);
	    	foreach($locations as $key => $value){
	    		$key = strtolower($key);
	    		if ($key != ''){
				    woocommerce_wp_text_input(array(
				        'id' => '_'.$key.'_price[' . $variation->ID . ']',
						'name' => '_'.$key.'_price[' . $variation->ID . ']',
				        'class' => 'short',
				        'label' => __($value.' Price', 'woocommerce'),
				        'value' => get_post_meta($variation->ID, '_'.$key.'_price', true),
				        'wrapper_class' => 'form-row form-row-first',
				    ));
				    woocommerce_wp_text_input(array(
				        'id' => '_'.$key.'_sale_price[' . $variation->ID . ']',
						'name' => '_'.$key.'_sale_price[' . $variation->ID . ']',
				        'class' => 'short',
				        'label' => __($value.' Sale Price', 'woocommerce'),
				        'value' => get_post_meta($variation->ID, '_'.$key.'_sale_price', true),
				        'wrapper_class' => 'form-row form-row-last',
				    ));
				}    		
	    	}
	    }
	}

	//saves the custom price and sale price for each location
	function woocommerce_product_custom_fields_save($post_id){
    	if(!empty($this->locations)){
	    	$locations = $this->locations;
            // Save default location price as custom meta too
            if (isset($this->default_locations) && $this->default_locations != '') {
                $default_key = $this->default_locations;
                $product = wc_get_product($post_id);
                if ($product && $product->is_type('simple')) {
                    $regular_price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    update_post_meta($post_id, '_' . $default_key . '_price', $regular_price);
                    update_post_meta($post_id, '_' . $default_key . '_sale_price', $sale_price);
                    error_log("[NBT DEBUG] Saved simple product default location meta for post_id $post_id: {$default_key}_price=$regular_price, {$default_key}_sale_price=$sale_price");
                }
            }
	    	unset($locations[$this->default_locations]);
            $product = wc_get_product($post_id);
            if ($product && $product->is_type('simple')) {
                foreach($locations as $key => $value){
                    if ($key != ''){
                        // Handle regular price
                        $_price = isset($_POST['_'.$key.'_price']) ? sanitize_text_field($_POST['_'.$key.'_price']) : null;
                        if ($_price !== null && $_price !== '') {
                            update_post_meta($post_id, '_'.$key.'_price', esc_attr($_price));
                            error_log("[NBT DEBUG] Saved simple product meta for post_id $post_id: {$key}_price=$_price");
                        } else {
                            delete_post_meta($post_id, '_'.$key.'_price');
                            error_log("[NBT DEBUG] Deleted regular price meta for post_id $post_id: {$key}_price");
                        }

                        // Handle sale price
                        $_sale_price = isset($_POST['_'.$key.'_sale_price']) ? sanitize_text_field($_POST['_'.$key.'_sale_price']) : null;
                        if ($_sale_price !== null && $_sale_price !== '') {
                            update_post_meta($post_id, '_'.$key.'_sale_price', $_sale_price);
                            error_log("[NBT DEBUG] Saved simple product meta for post_id $post_id: {$key}_sale_price=$_sale_price");
                        } else {
                            delete_post_meta($post_id, '_'.$key.'_sale_price');
                            error_log("[NBT DEBUG] Deleted sale price meta for post_id $post_id: {$key}_sale_price");
                        }
                    }
                }
            }
	    }
	}

	//saves the custom price and sale price for each location in variations panel
	function save_variable_product_price_fields($variation_id, $i) {
	    if(!empty($this->locations)){
	    	$locations = $this->locations;
            // Always update default location meta for every variation
            if (isset($this->default_locations) && $this->default_locations != '') {
                $default_key = $this->default_locations;
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $regular_price = $variation->get_regular_price();
                    $sale_price = $variation->get_sale_price();
                    update_post_meta($variation_id, '_' . $default_key . '_price', $regular_price);
                    update_post_meta($variation_id, '_' . $default_key . '_sale_price', $sale_price);
                    error_log("[NBT DEBUG] Saved variation default location meta for variation_id $variation_id: {$default_key}_price=$regular_price, {$default_key}_sale_price=$sale_price");
                }
            }
			unset($locations[$this->default_locations]);
            foreach($locations as $key => $value){
                if ($key != ''){
                   $price = isset($_POST['_'.$key.'_price'][$variation_id]) ? sanitize_text_field($_POST['_'.$key.'_price'][$variation_id]) : null;
                   $sale_price = isset($_POST['_'.$key.'_sale_price'][$variation_id]) ? sanitize_text_field($_POST['_'.$key.'_sale_price'][$variation_id]) : null;
                   if ($price !== null && $price !== '') {
                       update_post_meta($variation_id, '_'.$key.'_price', $price);
                   } else {
                       delete_post_meta($variation_id, '_'.$key.'_price');
                   }
                   if ($sale_price !== null && $sale_price !== '') {
                       update_post_meta($variation_id, '_'.$key.'_sale_price', $sale_price);
                   } else {
                       delete_post_meta($variation_id, '_'.$key.'_sale_price');
                   }
                }
            }
            
	    }
	}

	//changes the backend product regular and sale price labels to the default location name
	function change_backend_product_regular_price( $translated_text, $text, $domain ) {
	   	global $pagenow, $post_type;
    	// Check if default locations and the key exist before accessing them
	    if (isset($this->default_locations) && $this->default_locations != ''  && isset($this->locations[$this->default_locations]) && $this->locations[$this->default_locations] != '') {
 			$default_locations = $this->locations[$this->default_locations];

	        // For "Regular price" text
	        if ( is_admin() && in_array( $pagenow, ['post.php', 'post-new.php'] ) && 'product' === $post_type && 'Regular price' === $text && 'woocommerce' === $domain ) {
                $translated_text = __( $default_locations . ' price', $domain );
     

		        // For "Sale price" text
	        } elseif ( is_admin() && in_array( $pagenow, ['post.php', 'post-new.php'] ) && 'product' === $post_type && 'Sale price' === $text && 'woocommerce' === $domain ) {
  	          	$translated_text = __( $default_locations . ' Sale price', $domain );
        	}
    	}
	    return $translated_text;
	}

	//adds the bacs payment gateway
	function nbt_add_bacs_payment_gateway($gateways) {
	    $gateways[] = 'WC_Gateway_BACS_NBT';
	    return $gateways;
	}

	//initializes the class
	function init(){       
		add_filter('yith_wapo_save_addon_settings', [$this, 'nbt_wapo_save_addon_settings'], 20, 2);
		add_action('woocommerce_product_options_pricing', [$this, 'woocommerce_simple_product_custom_fields'], 20 );
		add_action('woocommerce_variation_options_pricing',  [$this, 'woocommerce_variable_product_custom_fields'], 20, 3);
		add_action('woocommerce_process_product_meta', [$this, 'woocommerce_product_custom_fields_save']);
		add_action('woocommerce_save_product_variation', [$this, 'save_variable_product_price_fields'], 20, 2);	
		add_filter('gettext', [$this, 'change_backend_product_regular_price'], 50, 3 );
		add_filter('woocommerce_payment_gateways', [$this, 'nbt_add_bacs_payment_gateway']);
	}
}

// Hide Regular price and Sale price fields in General tab for variable products
add_action('admin_head', function() {
    global $post;
    if ($post && get_post_type($post) === 'product') {
        $product = wc_get_product($post->ID);
        if ($product && $product->is_type('variable')) {
            echo '<style>.options_group.pricing { display: none !important; }</style>';
        }
    }
});