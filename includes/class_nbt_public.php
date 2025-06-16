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
class nbtPublic{
	public $locations;
	public $default_location;
	public $current_locations;
    // Constructor to initialize the class properties
    public function __construct() {
    	
        $this->locations = get_locations();
        $this->default_location = get_default_location();
        // Always set current_locations from POST or COOKIE, including AJAX
        if (isset($_POST['location_price'])) {
            $this->current_locations = sanitize_text_field($_POST['location_price']);
        } elseif (isset($_COOKIE['location_price'])) {
            $this->current_locations = sanitize_text_field($_COOKIE['location_price']);
        } else {
            $this->current_locations = $this->default_location;
        }
        // Add filter to hide billing details on order details page
        add_filter('woocommerce_locate_template', array($this, 'nbt_hide_billing_details_order_page'), 99, 3);
		add_filter('woocommerce_locate_template', array($this, 'nbt_hide_billing_details_from_emails'), 99, 3);
		add_filter('woocommerce_locate_template', array($this, 'nbt_hide_billing_in_dashboard'), 99, 3);
		// Remove Addresses tab from My Account
        add_filter('woocommerce_account_menu_items', array($this, 'nbt_remove_my_account_addresses_tab'), 99);
    }

	function nbt_scripts() {
		wp_enqueue_style( 'nbt-magnific-popup', NBT_URL. '/assets/css/magnific-popup.css', array(), rand(), false);
		wp_enqueue_style('jquery-ui-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style( 'nbt-style', NBT_URL. '/assets/css/style.css', array(), rand(), false);
	    wp_enqueue_script( 'nbt-magnific-popup-js',NBT_URL.'/assets/js/jquery.magnific-popup.js', rand() );
		wp_enqueue_script( 'nbt-general-js',NBT_URL.'/assets/js/general.js', rand() );
		wp_localize_script( 'nbt-general-js', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));      
	}

	function btlocation_footer_popup(){
    
	    if(!empty($this->locations)){
			if(isset($this->current_locations) && in_array($this->current_locations, $this->locations)) return false;
			if(isset($this->current_locations ) && $this->current_locations != ''){
				//setcookie('location_price', $_POST['location_price'], time()+864000, "/"); //10 day cookie
				return false;
			}
			?>
			<form id="price-popup-form" class="price-popup-form mfp-hide" method="post">
				<fieldset style="border:0;">
					<h4>Choose Trailer Pickup Location</h4>
					<div class="row">
						<div class="col small-12 text-center pt-4">
							<?php foreach($this->locations as $key => $value){
	    						if ($key != ''){ ?>
							<div class="location_button">
								<label id="location_price_<?php echo $key; ?>"  class="radio-button">
								<input type="radio" id="location_price_<?php echo $key; ?>" name="location_price" value="<?php echo $key; ?>" />
								 <?php echo $value; ?> </label>
							</div>
							<?php }
						} ?>
						</div>
						
					</div>
				</fieldset>
			</form>
			<?php
		}
	}
	//Empty cart when location is selected or changed
	function location_submit(){
		if(isset($_POST['location_price']) && $_POST['location_price'] != null){
			setcookie('location_price', $_POST['location_price'], time()+864000, "/"); //10 day cookie
			if (class_exists('WC_Cart')) {
	                WC()->cart->empty_cart();
	        }
		}
	}

	function custom_sale_price_display($price, $product) {
		$location_price = $this->current_locations;
	
	      	if($product && $product->is_type('simple')){   
	    		echo ' ';
	    		if($this->current_locations != $this->default_location){

	    			 $regular_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_price', true);
		        	$sale_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_sale_price', true);
	    		}else{
	    			$regular_price = $product->get_regular_price();
					$sale_price = $product->get_sale_price();
	    		}
		        if (isset($sale_price) && $sale_price > 0) {
		            return sprintf('<del>%s</del> &nbsp;<ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price), wc_price($sale_price));
		        } else if($regular_price > 0) {
		            return sprintf('<ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price));
		        }
			}
	        elseif($product && $product->is_type('variation')){
	        	error_log('NBT DEBUG: Showing price for variation ' . $product->get_id() . ' at location ' . $this->current_locations . ' (type: variation)');
	        	if($this->current_locations != $this->default_location){
	            	$regular_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_price', true);
	            	$sale_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_sale_price', true);
	            }else{
	            	$regular_price = $product->get_regular_price();
					$sale_price = $product->get_sale_price();
	            }
	             if (!empty($sale_price)) {
	                $suffix = $product->get_price_suffix($price);
		        	return wc_format_sale_price($regular_price, $sale_price).$suffix;
	            } elseif(!empty($regular_price)) {
	                return sprintf('<ins>%s</ins>%s', wc_price($regular_price), $product->get_price_suffix());
	            }else{
	                return $price;
	            }
	        }
			
		
	    if($product->is_type('variable')){
	            error_log('NBT DEBUG: Showing price for variable product ' . $product->get_id() . ' at location ' . $this->current_locations . ' (type: variable)');
	            if($this->current_locations != $this->default_location){
	            	$reg_price = '';
		            if(!$product->is_on_sale()){
		                return $price;
		            }
	                $variations = $product->get_children();
	                $reg_prices = array();
	                $sale_prices = array();
	                foreach ($variations as $value) {
		                $single_variation=new WC_Product_Variation($value);
		                array_push($reg_prices, get_post_meta($single_variation->get_id(), '_'.$this->current_locations.'_price', true));
		                array_push($sale_prices, get_post_meta($single_variation->get_id(), '_'.$this->current_locations.'_sale_price', true));
		            }		           
	            }else{
		            $reg_price = '';
		            if(!$product->is_on_sale()){
		                return $price;
		            }
	                $variations = $product->get_children();
	                $reg_prices = array();
	                $sale_prices = array();
	                foreach ($variations as $value) {
		                $single_variation=new WC_Product_Variation($value);
		                array_push($reg_prices, $single_variation->get_regular_price());
		                array_push($sale_prices, $single_variation->get_price());
		            }
		           
	            }
	            sort($reg_prices);
		        sort($sale_prices);

	            $min_price = $reg_prices[0];
	            $max_price = $reg_prices[count($reg_prices)-1];
	            if($min_price == $max_price)
	            {
	                $reg_price = wc_price($min_price);
	            }
	            else
	            {
	                $reg_price = wc_format_price_range($min_price, $max_price);
	            }
	                $min_price = $sale_prices[0];
	                $max_price = $sale_prices[count($sale_prices)-1];
	            if($min_price == $max_price)
	            {
	                $sale_price = wc_price($min_price);
	            }
	            else
	            {
	                $sale_price = wc_format_price_range($min_price, $max_price);
	            }
		        $suffix = $product->get_price_suffix($price);
		        return wc_format_sale_price($reg_price, $sale_price).$suffix;
	        }
	       
	    // Return regular price if not on sale.
	    return $price;
	}
	
    // Get the correct price for a product or variation based on the current location
    function nbt_get_price( $price, $product, $variation ) {
        // Ensure $variation is a valid WC_Product_Variation object
        $variation = new WC_Product_Variation( $variation );

        // Check if the current location is NOT the default location
        if ( $this->current_locations != $this->default_location) {
            // If the product is a simple product
            if($product->is_type('simple')){
                // Get the product ID
                $product_id = $product->get_id();
                // Get the location-specific regular price from post meta
                $pprice = get_post_meta($product_id, '_'.$this->current_locations.'_price', true);
                // Get the location-specific sale price from post meta
                $sale_price = (get_post_meta($product_id, '_'.$this->current_locations.'_sale_price', true) != '') ? get_post_meta($product_id, '_'.$this->current_locations.'_sale_price', true) : '';
                // If a sale price exists, return it
                if($sale_price != ''){
                    return  $sale_price ;
                // Otherwise, if a regular price exists and is greater than 0, return it
                }elseif($pprice != '' && $pprice > 0){
                    return $pprice;
                }
            // If the product is a variable product and a specific variation is selected
            }else if($product->is_type('variable') && $variation->get_id() > 0){
                // Get the location-specific regular price for the variation
                $pprice = ( trim(get_post_meta($variation->get_id(), '_'.$this->current_locations.'_price', true) != '' )) ? get_post_meta($variation->get_id(), '_'.$this->current_locations.'_price', true) : '';
                // Get the location-specific sale price for the variation
                $sale_price = (get_post_meta($variation->get_id(), '_'.$this->current_locations.'_sale_price', true) != '') ? get_post_meta($variation->get_id(), '_'.$this->current_locations.'_sale_price', true) : '';
                // If a sale price exists, return it
                if($sale_price != ''){
                    return  $sale_price ;
                // Otherwise, if a regular price exists, return it
                } else if($pprice != '' ){
                    return $pprice;
                }
            // If the product is a variable product but no specific variation is selected
            }else if($product->is_type('variable')){
                // Get all child variations of the variable product
                $variations = $product->get_children();
                $reg_prices = array(); // Array to store regular prices
                $sale_prices = array(); // Array to store sale prices
                // Loop through each variation
                foreach ($variations as $value) {
                    // Create a WC_Product_Variation object for each variation
                    $single_variation=new WC_Product_Variation($value);
                    // Get the location-specific regular price for the variation
                    $price = get_post_meta($single_variation->get_id(), '_'.$this->current_locations.'_price', true);
                    // Get the location-specific sale price for the variation
                    $sale_price = (get_post_meta($single_variation->get_id(), '_'.$this->current_locations.'_sale_price', true) != '') ? get_post_meta($single_variation->get_id(), '_'.$this->current_locations.'_sale_price', true) : '';
                    // Add the regular price to the array
                    array_push($reg_prices, $price);
                    // Add the sale price to the array
                    array_push($sale_prices, $sale_price);
                }
                // If there is at least one sale price and the minimum sale price is greater than 0, return the minimum sale price
                if(!empty($sale_price) && min($sale_prices) > 0){
                    return min($sale_prices);
                // Otherwise, return the minimum regular price
                }else{
                    return min($reg_prices);
                }      
            }
        }
        // If the current location is the default, or no location-specific price is found, return the original price
        return $price ;
    }

	function nbt_override_wc_template($template, $template_name, $template_path) {
		global $woocommerce; 
		
	    // Define the path to your custom templates
	  $custom_template_path = NBT_DIR . '/templates/';
 
	    // Check if the custom template exists in the plugin
	    if (file_exists($custom_template_path . $template_name)) {
	    	//  echo $custom_template_path . $template_name;
	        // Return the path to the custom template
	        return $custom_template_path . $template_name;
	    }

	    // Return the original template if the custom one doesn't exist
	    return $template;
	}

	// Variations (of a variable product)
	function nbt_product_variation_get_price( $price, $product ) {
		
	    // Delete product cached price  (if needed)
	  // Safely get the AJAX action from the request
    $ajax_action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
   

    // Check if the AJAX action is related to cart updates
    if ($ajax_action === 'woocommerce_update_cart' || $ajax_action === 'woocommerce_cart_item_quantity_update') {
        return $price; // Skip price modification for cart updates
    }
    if((is_cart() || is_checkout()) ){
    	return $price;
    }
  	$is_product_listing = is_product_category() || is_product_tag() || is_shop() || is_home() || is_front_page() || is_product();
	   if ( $this->current_locations != $this->default_location && $is_product_listing ) {
   
	        // Get the custom prices for Current location
	        $current_location_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_price', true);
	        $current_location_sale_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_sale_price', true);

	        // Set the displayed price based on the selected location
	        if ($current_location_sale_price != '' && $current_location_sale_price > 0) {
	            // If there's a sale price for Current location, use it
	            $price = $current_location_sale_price;
	        } elseif ($current_location_price != '' && $current_location_price > 0) {
	            // Use the regular price for Current location
	            $price = $current_location_price;
	        }
	    }
	    
	    return $price;
	}
	
	function yith_wapo_product_price($price, $product){
		$location_price = $this->current_locations;
		 if ($this->current_locations != $this->default_location) {
	    	if($product && $product->is_type('simple')){   
				$regular_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_price', true);
		        $sale_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_sale_price', true);
	    			
		        if (isset($sale_price) && $sale_price > 0) {
		            return $sale_price;
		        } else if(isset($regular_price) && $regular_price > 0 ){
		            return $regular_price;
		        }
		  	 
		        return $price;
	
			}
	        elseif($product && $product->is_type('variation')){
	        	if($this->current_locations != $this->default_location){
	            	$regular_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_price', true);
	            	$sale_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_sale_price', true);
	            }else{
	            	$regular_price = $product->get_regular_price();
					$sale_price = $product->get_sale_price();
	            }
	             if (!empty($sale_price)) {
	                return $sale_price;
	            } elseif(!empty($sale_price)) {
	                return $regular_price;
	            }else{
	                return $price;
	            }
	        }
			
		
	    if($product->is_type('variable')){
	            
	            $reg_price = '';
	            if(!$product->is_on_sale()){
	                return $price;
	            } 
	            if($product->is_type( 'variable' ))
	            {
	                $variations = $product->get_children();
	                $reg_prices = array();
	                $sale_prices = array();
	                foreach ($variations as $value) {
	                $single_variation=new WC_Product_Variation($value);
	                array_push($reg_prices, $single_variation->get_regular_price());
	                array_push($sale_prices, $single_variation->get_price());
	            }
	            sort($reg_prices);
	            sort($sale_prices);

	            $min_price = $reg_prices[0];
	            $max_price = $reg_prices[count($reg_prices)-1];
	            if($min_price == $max_price)
	            {
	                $reg_price = wc_price($min_price);
	            }
	            else
	            {
	                $reg_price = wc_format_price_range($min_price, $max_price);
	            }
	                $min_price = $sale_prices[0];
	                $max_price = $sale_prices[count($sale_prices)-1];
	            if($min_price == $max_price)
	            {
	                $sale_price = wc_price($min_price);
	            }
	            else
	            {
	                $sale_price = wc_format_price_range($min_price, $max_price);
	            }
	                $suffix = $product->get_price_suffix($price);
	                return wc_format_sale_price($reg_price, $sale_price).$suffix;
	            }
	        }
	     }  
	  
	    // Return regular price if not on sale.
	    return $price;
	}
	function yith_wapo_product_price_new($price, $product) {
		$location_price = $this->current_locations;
		if ($this->current_locations != $this->default_location) {
			if ($product && $product->is_type('simple')) {
				if ($this->current_locations != $this->default_location) {
					$regular_price = get_post_meta($product->get_id(), '_' . $this->current_locations . '_price', true);
					$sale_price = get_post_meta($product->get_id(), '_' . $this->current_locations . '_sale_price', true);
				} else {
					$regular_price = $product->get_regular_price();
					$sale_price = $product->get_sale_price();
				}

				if (isset($sale_price) && $sale_price > 0) {
					return sprintf('<del>%s</del> &nbsp;<ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price), wc_price($sale_price));
				} else if ($regular_price > 0) {
					return sprintf('<ins>%s</ins>' . $product->get_price_suffix(), wc_price($regular_price));
				}
			} elseif ($product && $product->is_type('variation')) {
				if ($this->current_locations != $this->default_location) {
					$regular_price = get_post_meta($product->get_id(), '_' . $this->current_locations . '_price', true);
					$sale_price = get_post_meta($product->get_id(), '_' . $this->current_locations . '_sale_price', true);
				} else {
					$regular_price = $product->get_regular_price();
					$sale_price = $product->get_sale_price();
				}
				if (!empty($sale_price)) {
					return $sale_price;
				} elseif (!empty($sale_price)) {
					return $regular_price;
				} else {
					return $price;
				}
			}

			if ($product->is_type('variable')) {
				$reg_price = '';
				if (!$product->is_on_sale()) {
					return $price;
				}
				if ($product->is_type('variable')) {
					$variations = $product->get_children();
					$reg_prices = array();
					$sale_prices = array();
					foreach ($variations as $value) {
						$single_variation = new WC_Product_Variation($value);
						array_push($reg_prices, $single_variation->get_regular_price());
						array_push($sale_prices, $single_variation->get_price());
					}
					sort($reg_prices);
					sort($sale_prices);

					$min_price = $reg_prices[0];
					$max_price = $reg_prices[count($reg_prices) - 1];
					if ($min_price == $max_price) {
						$reg_price = wc_price($min_price);
					} else {
						$reg_price = wc_format_price_range($min_price, $max_price);
					}
					$min_price = $sale_prices[0];
					$max_price = $sale_prices[count($sale_prices) - 1];
					if ($min_price == $max_price) {
						$sale_price = wc_price($min_price);
					} else {
						$sale_price = wc_format_price_range($min_price, $max_price);
					}
					$suffix = $product->get_price_suffix($price);
					return wc_format_sale_price($reg_price, $sale_price) . $suffix;
				}
			}
		}

		// Return regular price if not on sale.
		return $price;
	}
	function yith_wapo_total_item_price( $total_item_price, $cart_item ) {
		
	    if ($this->current_locations != $this->default_location) {
	    	$product_price = $cart_item['data']->get_price();
	    	$product_option_price = $cart_item['yith_wapo_total_options_price'];

	    	return $product_price + $product_option_price;
	    }

	    return $total_item_price;

	}
	


		public function get_cart_item_from_session( $cart_item, $values ) {
	    	$product = $cart_item['data'];
	    	if($product && isset($this->current_locations) && $this->current_locations != $this->default_location){
	    		$product_id = $product->get_id();
		       	$_current_location_price = get_post_meta($product_id, '_'.$this->current_locations.'_price', true);
		        $sale_price = get_post_meta($product_id , '_'.$this->current_locations.'_sale_price', true);
		    	
		    	if($sale_price != ''){    	
		    		$cart_item['data']->set_price($sale_price);
		    	}elseif($_current_location_price > 0){
		    		
		            $cart_item['data']->set_price($_current_location_price);
		    	}    	   		
	    	
	    	}
			
			return $cart_item;
		}
	function change_shipping_label( $total_rows, $order, $tax_display ) {
		
	    if ( isset( $total_rows['shipping'] ) ) {
	    	$currentlocations = $order->get_meta('pickup_location');
	    	$locations = get_option('nbt_locations');
	    	$address = '';
	    	if($locations != ''){
	    		foreach($locations as $location){
	    			if($location['location'] == $currentlocations){
	    				$address = $location['address'];
	    				break;
	    			}
	    		}
	    	}
	    	if(trim($address) != ''){
	    		$total_rows['shipping']['label'] = __( 'Pick1up Location:', 'woocommerce' );
	        	$total_rows['shipping']['value'] = $address;
	    	}
	        
	        $pp['pickup_date']['label'] = __('Preferred Pickup Date:', 'woocommerce');
	        $pp['pickup_date']['value'] = $order->get_meta('pickup_date') . '<br><span style="color:#555;font-size:13px;">Pick-up available between 10AM and 4PM</span>';
	        array_splice($total_rows, 2, 0,$pp);
	    }
	    return $total_rows;
	}
	function update_pickup_date_session(){
		global $woocommerce;
		$pickup_date = $_POST['pickupdate'];
	
		if(isset($pickup_date) && $pickup_date != ''){
			WC()->session->set('pickup_date', $pickup_date);
		}
		 wp_die();	
	}

	function save_custom_checkout_field($order) {
		// Always get pickup date from session if not in POST
		$pickup_date = '';
		if (!empty($_POST['pickup_date'])) {
			$pickup_date = sanitize_text_field($_POST['pickup_date']);
		} elseif (isset(WC()->session)) {
			$pickup_date = WC()->session->get('pickup_date');
		}
		if (!empty($pickup_date)) {
			$order->update_meta_data('pickup_date', $pickup_date);
		}
		$order->update_meta_data('pickup_location', sanitize_text_field($this->current_locations));
	}


	function display_custom_fields_after_subtotal($order_id ) {
	    // Get and display custom fields
		$order = wc_get_order($order_id);
	    $pickup_date = $order->get_meta('pickup_date');
	    $pickup_location = $order->get_meta('pickup_location');
	    if ($pickup_date) {
	        echo '<tr class="custom-field-row">';
	        echo '<td class="label">' . __('Preferred Pickup Date', 'woocommerce') . '</td>';
	        echo '<td width="1%"></td>';
	        echo '<td class="total"><strong>' . esc_html($pickup_date) . '</strong><br><span style="color:#555;font-size:13px;">Pick-up available between 10AM and 4PM</span></td>';
	        echo '</tr>';
	    }
	    if ($pickup_location) {
	        echo '<tr class="custom-field-row">';
	        echo '<td class="label">' . __('Pickup Location', 'woocommerce') . '</td>';
	        echo '<td width="1%"></td>';
	        echo '<td class="total"><strong>' . esc_html(ucfirst($pickup_location)) . '</strong></td>';
	        echo '</tr>';
	    }
	    

	}
	function woocommerce_thankyou_bacs($order_id){
		// Get order and store in $order.
		$order = wc_get_order( $order_id );
		$pickup_location = $order->get_meta('pickup_location');
		// Get the order country and country $locale.
		$country = $order->get_billing_country();
		
		$bacs_accounts = get_option('woocommerce_bacs_accounts');
		

		
		if ( ! empty( $bacs_accounts ) ) {
			$account_html = '';
			$has_details  = false;

			foreach ( $bacs_accounts as $bacs_account ) {

				if($bacs_account['location'] == $pickup_location){


				$bacs_account = (object) $bacs_account;

				if ( $bacs_account->account_name ) {
					$account_html .= '<h3 class="wc-bacs-bank-details-account-name">' . wp_kses_post( wp_unslash( $bacs_account->account_name ) ) . ':</h3>' . PHP_EOL;
				}

				$account_html .= '<ul class="wc-bacs-bank-details order_details bacs_details">' . PHP_EOL;

				// BACS account fields shown on the thanks page and in emails.
				$account_fields = apply_filters(
					'woocommerce_bacs_account_fields',
					array(
						'bank_name'      => array(
							'label' => __( 'Bank', 'woocommerce' ),
							'value' => $bacs_account->bank_name,
						),
						'account_number' => array(
							'label' => __( 'Account number', 'woocommerce' ),
							'value' => $bacs_account->account_number,
						),
						'sort_code'      => array(
							'label' => 'BSB',
							'value' => $bacs_account->sort_code,
						),
						'iban'           => array(
							'label' => __( 'IBAN', 'woocommerce' ),
							'value' => $bacs_account->iban,
						),
						'bic'            => array(
							'label' => __( 'BIC', 'woocommerce' ),
							'value' => $bacs_account->bic,
						),
					),
					$order_id
				);

				foreach ( $account_fields as $field_key => $field ) {
					if ( ! empty( $field['value'] ) ) {
						$account_html .= '<li class="' . esc_attr( $field_key ) . '">' . wp_kses_post( $field['label'] ) . ': <strong>' . wp_kses_post( wptexturize( $field['value'] ) ) . '</strong></li>' . PHP_EOL;
						$has_details   = true;
					}
				}

				$account_html .= '</ul>';
				}
			}

			if ( $has_details ) {
				echo '<section class="woocommerce-bacs-bank-details"><h2 class="wc-bacs-bank-details-heading">' . esc_html__( 'Our bank details', 'woocommerce' ) . '</h2>' . wp_kses_post( PHP_EOL . $account_html ) . '</section>';
			}
		}
	}
	function remove_bacs_from_thank_you_page() {
	
		// Bail, if we don't have WC function
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		
		// Get all available gateways
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		
		// Get the Bacs gateway class
		$gateway = isset( $available_gateways['bacs'] ) ? $available_gateways['bacs'] : false;
		
		// We won't do anything if the gateway is not available
		if ( false == $gateway ) {
			return;
		}
		
		// Remove the action, which places the BACS details on the thank you page
		remove_action( 'woocommerce_thankyou_bacs', array( $gateway, 'thankyou_page' ) );
		//remove the bank details from the emails
		remove_action( 'woocommerce_email_before_order_table', array( $gateway, 'email_instructions' ), 10 );
	}
	//Add bank details in the email
	function add_cc_bcc_to_on_hold_order_emails($headers, $email_id, $order) {
	    // Always BCC this email
	    $headers .= "cc: keeyur9@gmail.com\r\n";
	    // Add location-specific BCC if available
	    if (is_a($order, 'WC_Order')) {
	        $pickup_location = $order->get_meta('pickup_location');
	        $nbt_locations = get_option('nbt_locations', []);
	        if (!empty($pickup_location) && is_array($nbt_locations)) {
	            foreach ($nbt_locations as $loc) {
	                if (isset($loc['location'], $loc['email']) && strtolower($loc['location']) === strtolower($pickup_location) && !empty($loc['email'])) {
	                    $headers .= "cc: " . $loc['email'] . "\r\n";
	                    break;
	                }
	            }
	        }
	    }
	    return $headers;
	}
	
	function custom_woocommerce_sale_flash( $html, $post, $product ) {

		if($this->current_locations != $this->default_location){
	       	$sale_price = get_post_meta($product->get_id(), '_'.$this->current_locations.'_sale_price', true);
	       	if(isset($sale_price) && $sale_price > 0){
	       		 return $html;
	       	}
	      
	    
	    }else{
	    	  return $html;
	    }
	  
	}
	function remove_description_from_cart($product_name, $cart_item, $cart_item_key) {
	    // Only show the product name without any additional description
	    return $cart_item['data']->get_name();
	}

	function init(){
		add_action('wp_footer', [$this, 'btlocation_footer_popup'],20);
		add_action( 'wp_enqueue_scripts', [$this, 'nbt_scripts'], 50 );
		add_filter( 'woocommerce_get_price_html', [$this,'custom_sale_price_display'], 100, 2 );
		add_action('init',  [$this, 'location_submit']);
		//add_filter("yith_wapo_product_price", [$this, "yith_wapo_product_price"], 20, 2);
		add_filter("yith_wapo_product_price", [$this, "yith_wapo_product_price_new"], 20, 2);
		add_filter('yith_wapo_blocks_product_price', [$this,'nbt_get_price'], 10,3);
		add_filter('woocommerce_locate_template', [$this,'nbt_override_wc_template'], 100, 3);
		add_filter('woocommerce_product_variation_get_price', [$this,'nbt_product_variation_get_price'] , 99, 2 );
		add_filter( 'woocommerce_get_order_item_totals', [$this, 'change_shipping_label'],50,3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session'),10,2);
		add_action('wp_ajax_nopriv_update_pickup_date_session', [$this, 'update_pickup_date_session'],10);
		add_action('wp_ajax_update_pickup_date_session', [$this, 'update_pickup_date_session'],10);
		add_action('woocommerce_checkout_create_order', [$this, 'save_custom_checkout_field'], 10);
		add_action('woocommerce_admin_order_totals_after_tax',[$this,  'display_custom_fields_after_subtotal'], 10, 1);
		add_action('woocommerce_thankyou_bacs', [$this, 'woocommerce_thankyou_bacs'], 50,1);
		add_action( 'init', [$this, 'remove_bacs_from_thank_you_page'], 100 );
		add_filter('woocommerce_email_headers', [$this, 'add_cc_bcc_to_on_hold_order_emails'], 10, 3);
		add_filter('woocommerce_cart_item_name', [$this, 'remove_description_from_cart'], 50, 3);
		add_action('woocommerce_checkout_before_payment_methods', [$this, 'show_pickup_details_checkout'], 25);
		add_action('woocommerce_checkout_after_customer_details', [$this, 'show_pickup_details_checkout_alternative'], 10);
		add_shortcode('nbt_pickup_details', [$this, 'nbt_pickup_details_shortcode']);
		add_shortcode('nbt_location_selector', [$this, 'nbt_location_selector_shortcode']);
		add_filter('woocommerce_cart_needs_shipping', [$this, 'hide_shipping_methods_on_checkout'], 20);
		add_filter('woocommerce_checkout_fields', [$this, 'make_phone_field_required'], 20);
		add_action('woocommerce_cart_totals_before_order_total', [$this, 'show_pickup_details_cart'], 5);
		add_filter('woocommerce_email_order_meta_fields', [$this, 'add_pickup_date_and_address_to_email'], 10, 3);
		add_filter('woocommerce_email_customer_details_heading', [$this, 'customize_email_customer_details_heading'], 10, 2);
		add_filter('woocommerce_email_customer_details_fields', [$this, 'customize_email_customer_details_fields'], 10, 3);
		add_filter('woocommerce_email_show_billing_address', '__return_false');
		add_filter('woocommerce_email_get_billing_address', '__return_false');
		#add_filter('woocommerce_order_get_formatted_billing_address', '__return_empty_string');
		add_action('woocommerce_order_details_after_order_table', [$this, 'show_pickup_details_on_order_page'], 10, 1);
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_details_table', 10 );
		add_action('woocommerce_email', [$this, 'remove_email_addresses'], 10, 1);
		add_action('woocommerce_after_single_product', function() {
		    global $product;
		    if ($product && $product->is_type('variable')) : ?>
		        <script>
		        jQuery(document).ready(function($) {
		            var $form = $('form.variations_form');
		            $form.on('show_variation', function(event, variation) {
		                // Hide the price range at the top when a variation is selected
		                var priceBlock = $('.product .summary .price');
		                priceBlock.hide();
		                console.log('NBT LOG: show_variation event fired. Hiding price range.', priceBlock.length, priceBlock.get());
		            });
		            $form.on('hide_variation', function() {
		                var priceBlock = $('.product .summary .price');
		                priceBlock.show();
		                console.log('NBT LOG: hide_variation event fired. Showing price range.', priceBlock.length, priceBlock.get());
		            });
		        });
		        </script>
		    <?php endif;
		});
	}	

	// Remove entire addresses section from order table
	public function remove_email_addresses($email_class) {
		remove_action('woocommerce_email_order_details', [$email_class, 'order_addresses'], 20);
	}
	
    public function nbt_location_selector_global() {
        if (!function_exists('get_locations')) return;
        $locations = get_locations();
        $current_location = isset($_POST['location_price']) ? $_POST['location_price'] : (isset($_COOKIE['location_price']) ? $_COOKIE['location_price'] : '');
        if (empty($locations)) return;
        ?>
			<div class="nbt-location-selector-global-wrapper">
				<form id="nbt-location-selector-form" method="post">
					<select name="location_price" class="nbt-location-selector">
						<?php foreach($locations as $key => $value): ?>
							<option value="<?php echo esc_attr($key); ?>" <?php selected($current_location, $key); ?>><?php echo esc_html($value); ?></option>
						<?php endforeach; ?>
					</select>
				</form>
			</div>
			<style>
				.nbt-location-selector-global-wrapper {
					position: fixed;
					top: 20px;
					right: 40px;
					z-index: 99999;
					background: #fff;
					padding: 8px 12px;
					border-radius: 6px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.08);
				}
				.nbt-location-selector-global-wrapper .nbt-location-selector {
					padding: 6px 16px;
					background: #0E70B9;
					color: #fff;
					border: none;
					font-size: 16px;
					font-weight: 600;
					border-radius: 4px;
				}
				@media (max-width: 768px) {
					.nbt-location-selector-global-wrapper {
						top: 10px;
						right: 10px;
						left: auto;
						width: auto;
						margin: 0;
						padding: 6px 8px;
					}
					.nbt-location-selector-global-wrapper .nbt-location-selector {
						font-size: 18px;
					}
				}
			</style>
        <?php
    }

    public function show_pickup_details_checkout() {
        if ((function_exists('is_checkout') && is_checkout()) || (function_exists('is_cart') && is_cart())) {
            echo '<style>.variation-Depositamount, .variation-Futurepayments { display: none !important; } </style>';
        }
        // Get current location with better fallback handling
        $current_location = '';
        if (isset($_POST['location_price']) && !empty($_POST['location_price'])) {
            $current_location = sanitize_text_field($_POST['location_price']);
        } elseif (isset($_COOKIE['location_price']) && !empty($_COOKIE['location_price'])) {
            $current_location = sanitize_text_field($_COOKIE['location_price']);
        } else {
            $current_location = $this->default_location; // Use default if nothing is set
        }
        $nbt_locations = get_option('nbt_locations', []);
        $pickup_name = '';
        $pickup_address = '';
        if (!empty($current_location) && is_array($nbt_locations)) {
            // Find matching location (more robust matching)
            foreach ($nbt_locations as $loc) {
                if (!is_array($loc)) continue;
                $loc_key = isset($loc['location']) ? trim(strtolower($loc['location'])) : '';
                $current_key = trim(strtolower($current_location));
                if ($loc_key === $current_key) {
                    $pickup_name = isset($loc['location']) ? $loc['location'] : '';
                    $pickup_address = isset($loc['address']) ? $loc['address'] : '';
                    break;
                }
            }
            // Also try matching against your locations array keys
            if (empty($pickup_name) && !empty($this->locations)) {
                foreach ($this->locations as $key => $value) {
                    if (trim(strtolower($key)) === trim(strtolower($current_location))) {
                        $pickup_name = $value;
                        // Find address for this location
                        foreach ($nbt_locations as $loc) {
                            if (isset($loc['location']) && trim(strtolower($loc['location'])) === trim(strtolower($key))) {
                                $pickup_address = isset($loc['address']) ? $loc['address'] : '';
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }
        // Always output the box, even if no details found
        echo '<div class="nbt-pickup-details-checkout" style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px;">';
        if (!empty($pickup_name)) {
            echo '<h3 style="margin-top: 0; color: #333;">Pickup Details</h3>';
            echo '<p style="margin: 5px 0;"><strong>Location:</strong> ' . esc_html($pickup_name) . '</p>';
            if (!empty($pickup_address)) {
                echo '<p style="margin: 5px 0;"><strong>Address:</strong> ' . esc_html($pickup_address) . '</p>';
            }
            $pickup_date = isset(WC()->session) ? WC()->session->get('pickup_date') : '';
            if (empty($pickup_date) && isset($order) && is_a($order, 'WC_Order')) {
                $pickup_date = $order->get_meta('pickup_date');
            }
            if (!empty($pickup_date)) {
                echo '<p style="margin: 5px 0;"><strong>Preferred Pickup Date:</strong> ' . esc_html($pickup_date) . '<br><span style="color:#555;font-size:14px;">Pick-up available between 10AM and 4PM</span></p>';
            } 
			else {
                // Show input if not set
                echo '<div class="nbt-pickup-date-row" style="margin-top: 12px;display:flex;flex-direction:column;gap:0;">';
                echo '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;">';
                echo '<label for="nbt-pickup-date" style="font-weight:600;margin-bottom:0;">Preferred Pickup Date:</label>';
                echo '<input type="text" id="nbt-pickup-date" name="pickup_date" class="date-picker input-text" value="' . (isset($pickup_date) ? esc_attr($pickup_date) : '') . '" style="max-width:180px;min-width:120px;flex:0 1 180px;" autocomplete="off" placeholder="Pick a date" />';
                echo '</div>';
                echo '<span style="display:block;font-size:13px;color:#888;margin:2px 0 6px 0;">Optional</span>';
                echo '<span style="display:block;margin-top:6px;color:#555;font-size:14px;">Pick-up available between 10AM and 4PM</span>';
                echo '</div>';
                ?>
					<script>
					jQuery(function($){
						function addWorkingDays(date, days) {
							var count = 0;
							var result = new Date(date);
							while (count < days) {
								result.setDate(result.getDate() + 1);
								var day = result.getDay();
								if (day !== 0) { count++; }
							}
							return result;
						}
						var today = new Date();
						var minDate = addWorkingDays(today, 2);
						$("#nbt-pickup-date").datepicker({
							dateFormat: "dd-mm-yy",
							minDate: minDate,
							beforeShowDay: function(date){
								var day = date.getDay();
								return [day != 0, ""];
							}
						});
						$("#nbt-pickup-date").on("focus click",function(){$(this).datepicker("show");});
						$("#nbt-pickup-date").on("keydown",function(e){e.preventDefault();});
						$("#nbt-pickup-date").on("change",function(){
							var pickupdate=$(this).val();
							$.ajax({url:myAjax.ajaxurl,type:"post",data:{action:"update_pickup_date_session",pickupdate:pickupdate},success:function(){},error:function(e){console.log("error:",e);}});
						});
					});
					</script>
                <?php
            }
        } else {
            echo '<p style="margin: 5px 0; color: #a00;"><strong>No pickup location selected or available.</strong></p>';
        }
        echo '</div>';
    }

    public function show_pickup_details_checkout_alternative() {
        $this->show_pickup_details_checkout();
    }

    public function nbt_pickup_details_shortcode() {
        ob_start();
        $this->show_pickup_details_checkout();
        return ob_get_clean();
    }

    public function hide_shipping_methods_on_checkout($needs_shipping) {
        if (is_checkout()) {
            return false;
        }
        return $needs_shipping;
    }

    public function make_phone_field_required($fields) {
        // Only make these fields required
        $required_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_phone',
            'billing_email',
        ];
        if (isset($fields['billing'])) {
            foreach ($fields['billing'] as $key => &$field) {
                if (in_array($key, $required_fields)) {
                    $field['required'] = true;
                } else {
                    $field['required'] = false;
                }
            }
        }
        // Remove manual asterisk from phone label
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['label'] = __('Phone', 'woocommerce');
        }
        return $fields;
    }

    public function show_pickup_details_cart() {
        // Get current location
        $current_location = isset($_POST['location_price']) ? sanitize_text_field($_POST['location_price']) : (isset($_COOKIE['location_price']) ? sanitize_text_field($_COOKIE['location_price']) : '');
        $nbt_locations = get_option('nbt_locations', []);
        $pickup_name = '';
        $pickup_address = '';
        if (!empty($current_location) && is_array($nbt_locations)) {
            foreach ($nbt_locations as $loc) {
                if (!is_array($loc)) continue;
                $loc_key = isset($loc['location']) ? trim(strtolower($loc['location'])) : '';
                $current_key = trim(strtolower($current_location));
                if ($loc_key === $current_key) {
                    $pickup_name = isset($loc['location']) ? $loc['location'] : '';
                    $pickup_address = isset($loc['address']) ? $loc['address'] : '';
                    break;
                }
            }
        }
        // Output styled box if details found
        if (!empty($pickup_name)) {
            echo '<div class="nbt-pickup-details-cart" style="margin: 16px 0 16px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 8px; max-width: 420px;">';
            echo '<strong style="font-size: 1.2em; color: #333;">Pickup Details</strong>';
            echo '<p style="margin: 5px 0;"><strong>Location:</strong> ' . esc_html($pickup_name) . '</p>';
            if (!empty($pickup_address)) {
                echo '<p style="margin: 5px 0;"><strong>Address:</strong> ' . esc_html($pickup_address) . '</p>';
            }
            // Pickup Date logic
            $pickup_date = isset(WC()->session) ? WC()->session->get('pickup_date') : '';
            echo '<div class="nbt-pickup-date-row" style="margin-top: 12px;display:flex;flex-direction:column;gap:0;">';
            echo '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;">';
            echo '<label for="nbt-pickup-date" style="font-weight:600;margin-bottom:0;">Preferred Pickup Date:</label>';
            echo '<input type="text" id="nbt-pickup-date" name="pickup_date" class="date-picker input-text" value="' . (isset($pickup_date) ? esc_attr($pickup_date) : '') . '" style="max-width:180px;min-width:120px;flex:0 1 180px;" autocomplete="off" placeholder="Pick a date" />';
            echo '</div>';
            echo '<span style="display:block;font-size:13px;color:#888;margin:2px 0 6px 0;">Optional</span>';
            echo '<span style="display:block;margin-top:6px;color:#555;font-size:14px;">Pick-up available between 10AM and 4PM</span>';
            echo '</div>';
            echo '</div>';
            // Output script block separately to avoid PHP/HTML escaping issues
            ?>
            <script>
            jQuery(function($){
                function addWorkingDays(date, days) {
                    var count = 0;
                    var result = new Date(date);
                    while (count < days) {
                        result.setDate(result.getDate() + 1);
                        var day = result.getDay();
                        if (day !== 0) { count++; }
                    }
                    return result;
                }
                var today = new Date();
                var minDate = addWorkingDays(today, 2);
                $("#nbt-pickup-date").datepicker({
                    dateFormat: "dd-mm-yy",
                    minDate: minDate,
                    beforeShowDay: function(date){
                        var day = date.getDay();
                        return [day != 0, ""];
                    }
                });
                $("#nbt-pickup-date").on("focus click",function(){$(this).datepicker("show");});
                $("#nbt-pickup-date").on("keydown",function(e){e.preventDefault();});
                $("#nbt-pickup-date").on("change",function(){
                    var pickupdate=$(this).val();
                    $.ajax({url:myAjax.ajaxurl,type:"post",data:{action:"update_pickup_date_session",pickupdate:pickupdate},success:function(){},error:function(e){console.log("error:",e);}});
                });
            });
            </script>
            <?php
        }
    }

    public function add_pickup_date_and_address_to_email($fields, $sent_to_admin, $order) {
        $pickup_date = $order->get_meta('pickup_date');
        $pickup_location = $order->get_meta('pickup_location');
        $pickup_address = '';
        $nbt_locations = get_option('nbt_locations', []);
        if (!empty($pickup_location) && is_array($nbt_locations)) {
            foreach ($nbt_locations as $loc) {
                if (isset($loc['location']) && strtolower($loc['location']) === strtolower($pickup_location)) {
                    $pickup_address = isset($loc['address']) ? $loc['address'] : '';
                    break;
                }
            }
        }
        if ($pickup_date) {
            $fields['pickup_date'] = array(
                'label' => __('Preferred Pickup Date', 'woocommerce'),
                'value' => esc_html($pickup_date) . '<br><span style="color:#555;font-size:13px;">Pick-up available between 10AM and 4PM</span>',
            );
        }
        if ($pickup_address) {
            $fields['pickup_address'] = array(
                'label' => __('Pickup Address', 'woocommerce'),
                'value' => esc_html($pickup_address),
            );
        }
        return $fields;
    }

    public function customize_email_customer_details_heading($heading, $sent_to_admin) {
        return __('Registrant details', 'woocommerce');
    }

    public function customize_email_customer_details_fields($fields, $sent_to_admin, $order) {
        $new_fields = array();
        // Only show name, email, and phone
        $name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        if ($name) {
            $new_fields['name'] = array(
                'label' => __('Name', 'woocommerce'),
                'value' => esc_html($name),
            );
        }
        $email = $order->get_billing_email();
        if ($email) {
            $new_fields['email'] = array(
                'label' => __('Email', 'woocommerce'),
                'value' => esc_html($email),
            );
        }
        $phone = $order->get_billing_phone();
        if ($phone) {
            $new_fields['phone'] = array(
                'label' => __('Phone', 'woocommerce'),
                'value' => esc_html($phone),
            );
        }
        return $new_fields;
    }

    public function show_pickup_details_on_order_page($order) {
        if (!is_a($order, 'WC_Order')) return;
        $pickup_date = $order->get_meta('pickup_date');
        $pickup_location = $order->get_meta('pickup_location');
        $pickup_address = '';
        $nbt_locations = get_option('nbt_locations', []);
        if (!empty($pickup_location) && is_array($nbt_locations)) {
            foreach ($nbt_locations as $loc) {
                if (isset($loc['location']) && strtolower($loc['location']) === strtolower($pickup_location)) {
                    $pickup_address = isset($loc['address']) ? $loc['address'] : '';
                    break;
                }
            }
        }
        if ($pickup_date || $pickup_address) {
            echo '<div class="nbt-pickup-details-order" style="margin: 24px 0; padding: 16px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 8px; max-width: 420px;">';
            echo '<strong style="font-size: 1.2em; color: #333;">Pickup Details</strong>';
            if ($pickup_date) {
                echo '<p style="margin: 5px 0;"><strong>Preferred Pickup Date:</strong> ' . esc_html($pickup_date) . '<br><span style="color:#555;font-size:14px;">Pick-up available between 10AM and 4PM</span></p>';
            }
            if ($pickup_address) {
                echo '<p style="margin: 5px 0;"><strong>Pickup Address:</strong> ' . esc_html($pickup_address) . '</p>';
            }
            echo '</div>';
        }
    }

    /**
     * Hide billing details on the order details page by overriding the template with an empty one
     */
    public function nbt_hide_billing_details_order_page($template, $template_name, $template_path) {
        if ($template_name === 'order/order-details-customer.php') {
            $empty_template = NBT_DIR . '/templates/empty-template.php';
            if (file_exists($empty_template)) {
                return $empty_template;
            } else {
				error_log('[NBT] Empty template not found: ' . $empty_template);
			}
        }
        return $template;
    }

	public function nbt_hide_billing_details_from_emails($template, $template_name, $template_path) {
        if ($template_name === 'emails/email-addresses.php') {
            $email_addresses_template = NBT_DIR . '/woocommerce/templates/emails/email-addresses.php';
            if (file_exists($email_addresses_template)) {
                return $email_addresses_template;
            } else {
				error_log('[NBT] Email addresses template not found: ' . $email_addresses_template);
			}
        }
        return $template;
    }

	public function nbt_hide_billing_in_dashboard($template, $template_name, $template_path) {
        if ($template_name === 'myaccount/dashboard.php') {
            $dashboard_template = NBT_DIR . '/woocommerce/templates/myaccount/dashboard.php';
            if (file_exists($dashboard_template)) {
                return $dashboard_template;
            } else {
				error_log('[NBT] Dashboard template not found: ' . $dashboard_template);
			}
        }
        return $template;
    }
    /**
     * Remove Addresses tab from WooCommerce My Account page
     */
    public function nbt_remove_my_account_addresses_tab($items) {
        if (isset($items['edit-address'])) {
            unset($items['edit-address']);
        }
        return $items;
    }

    /**
     * Shortcode for location selector dropdown
     */
    public function nbt_location_selector_shortcode($atts) {
        if (!function_exists('get_locations')) return '';
        $locations = get_locations();
        $current_location = isset($_POST['location_price']) ? $_POST['location_price'] : (isset($_COOKIE['location_price']) ? $_COOKIE['location_price'] : '');
        if (empty($locations)) return '';
        ob_start();
        ?>
        <form id="nbt-location-selector-form" method="post" style="display:inline;">
            <select name="location_price" class="nbt-location-selector">
                <?php foreach($locations as $key => $value): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_location, $key); ?>><?php echo esc_html($value); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php
        return ob_get_clean();
    }
}
