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
class NBT_Settings {
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_add_nbt_location', [$this, 'add_nbt_location']);
        add_action('wp_ajax_edit_nbt_location', [$this, 'edit_nbt_location']);
        add_action('wp_ajax_remove_nbt_location', [$this, 'remove_nbt_location']);
        add_action('wp_ajax_save_nbt_default_location', [$this, 'save_nbt_default_location']);
        // Add settings page
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'NBT Product Locations',
            'NBT Product Locations',
            'manage_options',
            'nbt-product-locations',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        // Get locations and default location
        $locations = get_option('nbt_locations', []);
       

         $default_location = get_option('nbt_default_location', '');
        if($default_location == '' && !empty($locations) && isset($locations[0]['location'])){
            update_option('nbt_default_location', $locations[0]['location']);
            $default_location = $locations[0]['location'];
        }
        // Render HTML
        ?>
        <div class="wrap">
            <h1>NBT Product Locations</h1>
            <h2>Manage Locations</h2>
            <form id="location-form">
            <table id="location-table" class="wp-list-table widefat fixed striped">
			    <thead>
			        <tr>
			            <th>Location</th>
			            <th>Address</th>

                        <th>Email</th>
			            <th>Actions</th>
			        </tr>
			    </thead>
			    <tbody>

			        <?php 

                    $i = 0;
                        if(!empty($locations)){ 
                        foreach ($locations as $index => $details): ?>
                            <tr data-index="<?php echo esc_attr($i); ?>" data-location="<?php echo esc_attr($details['location']); ?>">
                                <td><input type="text" value="<?php echo (isset($details['address']) && $details['location'] != '') ? esc_html($details['location']) : ''; ?>" name="nbt_location[<?php echo $index; ?>][location]"  class="validate-location"   /></td>
                                <td><input type="text" value="<?php echo (isset($details['address']) && $details['address'] != '') ? esc_html($details['address']) : ''; ?>" name="nbt_location[<?php echo $index; ?>][address]"  class="validate-address"/></td>
                                 <td><input type="text" value="<?php echo isset($details['email']) && $details['email'] != '' ? esc_html($details['email']) : ''; ?>" name="nbt_location[<?php echo $index; ?>][email]"  class="validate-email" /></td>
                                <td>
                                  <a type="button" class="remove-location-btn">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                        $i++;
                        endforeach; 
                    } ?>
                 
			    </tbody>
                <tfoot>
                    <tr>
                            <td colspan=4>
                                <input type="submit" class="button button-primary save-btn" name="save" value="Update" style="width: 150px;" <?php echo empty($locations) ? 'disabled' : '' ?> />
                            </td>
                        </tr>
                </tfoot>
			</table>
            </form>
            <h3>Add New Location</h3>
            <form id="add-location-form" >
                <table>
                    <tr>
                        <td>
                        
                            <input type="text" id="nbt-new-location" name="location" placeholder="Enter Location" class="regular-text">
                        
                        </td>
                        <td>
                            <input type="text" id="nbt-new-address" name="address" placeholder="Enter Address" class="regular-text">
                        </td>
                        <td>
                            <input type="text" id="nbt-new-email" name="email" placeholder="Enter Email address" class="regular-text">
                        </td>
                        <td>
                            <input type="submit" id="add-location-btn" class="button button-primary" name="add-location" value="Add Location">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
            <h2>Select Default Location</h2>
            <select id="nbt-default-location" name="nbt_default_location" class="regular-text">
                <?php foreach ($locations as $name => $details): ?>
                    <option value="<?php echo esc_attr($details['location']); ?>" <?php selected($default_location, $details['location']); ?>>
                        <?php echo esc_html($details['location']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <style>
            #add-location-form table { width: 90%;}
            #add-location-form table td {padding-right: 10px; vertical-align: top;}
            #location-table input{ width:100% }
            .error {  color: red; }   

        </style>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'woocommerce_page_nbt-product-locations') {
            return;
        }
         wp_enqueue_script('nbt-validate-js', NBT_URL . '/assets/js/jquery.validate.min.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('nbt-settings-js', NBT_URL . '/assets/js/nbt-admin-settings.js', ['jquery', 'nbt-validate-js'], '1.0.2', true);
        wp_localize_script('nbt-settings-js', 'nbtSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nbt_save_location_nonce'),
        ]);
    }

    public function add_nbt_location() {
        check_ajax_referer('nbt_save_location_nonce', 'nonce');

        if (!empty($_POST['locations']) ) {
           
            $locations_list = get_option('nbt_locations');
            if(!empty($locations_list ) && is_array($locations_list)){
                 array_push($locations_list, $_POST['locations']);
             }else{                
                $locations_list =  [ 0 => 
									[	'location' => $_POST['locations']['location'], 
									 	'address' =>$_POST['locations']['address'], 
									 	'email' => $_POST['locations']['email']
									]
								   ];
             }
          
            update_option('nbt_locations', $locations_list);

            wp_send_json_success([
                'location' =>$_POST['locations']['location'], 
                'address' =>$_POST['locations']['address'], 
                'email' => $_POST['locations']['email'], 
                
            ]);
        }

        wp_send_json_error('Invalid request.');
    }

    public function edit_nbt_location() {
        check_ajax_referer('nbt_save_location_nonce', 'nonce');

        if (!empty($_POST['locations']) && is_array($_POST['locations'])) {
            $locations = $_POST['locations'];
            $default_location = get_option('nbt_default_location', '');
            $old_locations = get_option('nbt_locations', []);

            // Only update the default location value if the name of the default location is changed
            foreach ($old_locations as $old) {
                if ($old['location'] === $default_location) {
                    foreach ($locations as $loc) {
                        // Only update if the name changed, not if just address/email changed
                        if ($old['location'] !== $loc['location'] && $old['address'] === $loc['address'] && $old['email'] === $loc['email']) {
                            update_option('nbt_default_location', $loc['location']);
                            break 2;
                        }
                    }
                }
            }

            update_option('nbt_locations', $locations);
            $current_default = get_option('nbt_default_location', '');
            wp_send_json_success(['success' => true, 'default_location' => $current_default]);
            return;
        }

        wp_send_json_error('Invalid request.');
    }

    public function remove_nbt_location() {
        check_ajax_referer('nbt_save_location_nonce', 'nonce');

        if (isset($_POST['location'])) {
            $location_name = sanitize_text_field($_POST['location']);  // Sanitize the input
            $locations = get_option('nbt_locations', []);  // Fetch all locations
            $default_location = get_option('nbt_default_location', '');

            // Count locations
            $location_count = count($locations);

            // If only one location, allow deletion and clear default
            if ($location_count === 1 && $locations[0]['location'] === $location_name) {
                update_option('nbt_locations', []);
                update_option('nbt_default_location', '');
                wp_send_json_success();
                return;
            }

            // Prevent deletion if this is the default location and more than one location exists
            if ($default_location === $location_name && $location_count > 1) {
                wp_send_json_error('Cannot delete the default location. Please change the default location first.');
                return;
            }

            // Loop through the locations and find the index of the location to remove
            foreach ($locations as $index => $details) {
                if ($details['location'] === $location_name) {
                    unset($locations[$index]);  // Remove the location
                    update_option('nbt_locations', array_values($locations));  // Reindex the array and update the option
                    wp_send_json_success();  // Send success response
                    return;
                }
            }
        }

        wp_send_json_error('Invalid request.');
    }

    public function save_nbt_default_location() {
        check_ajax_referer('nbt_save_location_nonce', 'nonce');

        if (isset($_POST['default_location'])) {
            $default_location = sanitize_text_field($_POST['default_location']);
            $old_default_location = get_option('nbt_default_location');
            // Only update product prices if the default location actually changed
            if ($old_default_location !== $default_location) {
            $this->woocommerce_product_custom_fields_save(strtolower($old_default_location), strtolower($default_location));
            update_option('nbt_default_location', $default_location);
            }
            wp_send_json_success();
            return;
        }

        wp_send_json_error('Invalid request.');
    }
	
    function woocommerce_product_custom_fields_save($old_default_location, $new_default_location)
    {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $products = get_posts($args);

        if (!empty($products)) {
            foreach ($products as $product_id) {
                $product = wc_get_product($product_id);

                if ($product && $product->is_type('simple')) {
                    // SIMPLE PRODUCT HANDLING

                    $old_default_price = $product->get_regular_price();
                    $old_default_sale_price = $product->get_sale_price();
                    $new_default_price = get_post_meta($product_id, '_' . $new_default_location . '_price', true);
                    $new_default_sale_price = get_post_meta($product_id, '_' . $new_default_location . '_sale_price', true);
                 
                    // Move current default price to old location meta
                    update_post_meta($product_id, '_' . $old_default_location . '_price', $old_default_price);
                    update_post_meta($product_id, '_' . $old_default_location . '_sale_price', $old_default_sale_price);

                    // Update main WooCommerce product prices
                    $product->set_regular_price($new_default_price);
                    $product->set_sale_price($new_default_sale_price);
                    $product->save();

                } elseif ($product && $product->is_type('variable')) {
                    // VARIABLE PRODUCT HANDLING

                    $variation_ids = $product->get_children();

                    foreach ($variation_ids as $variation_id) {
                        $variation = wc_get_product($variation_id);

                        // Get old default price
                        $old_default_price = $variation->get_regular_price();
                        $old_default_sale_price = $variation->get_sale_price();

                        // Get new default location prices from custom meta
                        $new_default_price = get_post_meta($variation_id, '_' . $new_default_location . '_price', true);
                        $new_default_sale_price = get_post_meta($variation_id, '_' . $new_default_location . '_sale_price', true);

                        // Save old default prices into old location key
                        update_post_meta($variation_id, '_' . $old_default_location . '_price', $old_default_price);
                        update_post_meta($variation_id, '_' . $old_default_location . '_sale_price', $old_default_sale_price);

                        // Update variation's actual price values
                        $variation->set_regular_price($new_default_price);
                        $variation->set_sale_price($new_default_sale_price);
                        $variation->save();
                    }

                    // Optionally, sync variable parent product price range
                    $product->save();
                }
            }
        }
    }

   
    function init(){

    
    }
}