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
	    // Access the global $woocommerce and $post variables. 
	    // $woocommerce is the main instance of the WooCommerce class, and $post is the current post object.
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
	                    'placeholder' => $value.' Price',
	                    'label' => __( $value.' Price ($)', 'woocommerce'),
	                    'desc_tip' => 'true'
	                    )
	                    );
	                    woocommerce_wp_text_input(
	                    array(
	                    'id' =>  '_'.$key.'_sale_price',
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


	// Variable product add fields for sydney Price
	function woocommerce_variable_product_custom_fields($loop, $variation_data, $variation) {
	    // Display fields for Sydney price and sale price
	    if(!empty($this->locations)){
	    	$locations = $this->locations;
	    	unset($locations[$this->default_locations]);
	    	foreach($locations as $key => $value){
	    		if ($key != ''){
				    woocommerce_wp_text_input(array(
				        'id' => '_'.$key.'_price[' . $variation->ID . ']',
				        'class' => 'short',
				        'label' => __($value.' Price', 'woocommerce'),
				        'value' => get_post_meta($variation->ID, '_'.$key.'_price', true),
				        'wrapper_class' => 'form-row form-row-first',
				    ));
				    woocommerce_wp_text_input(array(
				        'id' => '_'.$key.'_sale_price[' . $variation->ID . ']',
				        'class' => 'short',
				        'label' => __($value.' Sale Price', 'woocommerce'),
				        'value' => get_post_meta($variation->ID, '_'.$key.'_sale_price', true),
				        'wrapper_class' => 'form-row form-row-last',
				    ));
				}    		
	    	}
	    }
	}


	function woocommerce_product_custom_fields_save($post_id){
    	if(!empty($this->locations)){
	    	$locations = $this->locations;
	    	unset($locations[$this->default_locations]);
	    	foreach($locations as $key => $value){
	    		if ($key != ''){
				    $_price = $_POST['_'.$key.'_price'];
				    // Checks if the Sydney Price field is not empty.
				    if (!empty($_price)){
				        update_post_meta($post_id, '_'.$key.'_price', esc_attr($_price));
				    }
				    // Retrieves the value of the custom Sydney Sale Price field from the $_POST superglobal.
				    $_sale_price = $_POST['_'.$key.'_sale_price'];
				   	update_post_meta($post_id, '_'.$key.'_sale_price', $_POST['_'.$key.'_sale_price']);
				}
			}    
	   	}
	}

	function save_variable_product_price_fields($variation_id, $i) {
	    if(!empty($this->locations)){
	    	$locations = $this->locations;
	    	unset($locations[$this->default_locations]);  

	    	foreach($locations as $key => $value){
	    		if ($key != ''){
				    // Save Sydney price and sale price
				   $price = isset($_POST['_'.$key.'_price'][$variation_id]) ? sanitize_text_field($_POST['_'.$key.'_price'][$variation_id]) : '';
				    $sale_price = isset($_POST['_'.$key.'_sale_price'][$variation_id]) ? sanitize_text_field($_POST['_'.$key.'_sale_price'][$variation_id]) : '';
				
				    update_post_meta($variation_id, '_'.$key.'_price', $price);		  	
				    update_post_meta($variation_id, '_'.$key.'_sale_price', $sale_price);
				}
			}
		}		
	}


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

	function nbt_add_bacs_payment_gateway($gateways) {
	    $gateways[] = 'WC_Gateway_BACS_NBT';
	    return $gateways;
	}

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