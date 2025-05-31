<?php 
/**
 * Plugin Name: NBT Products Locations
 * Plugin URI:  
 * Description: 
 * Version: 1.0.2
 * Author: Dhara Talaviya
 * Author URI: 
 * Text Domain: nbt-products-locations
 * Domain Path: languages
 *
 */

 // Exit if accessed directly
defined('ABSPATH') or die('Sorry!, You do not access the file directly');

// If this file is accessed directory, then abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Basic plugin definitions
 * 
 * @package MSDynamicsEventsApi
 * @since 1.0.0
 */
if( !defined( 'NBT_PLUGIN_VERSION' ) ) {
    define( 'NBT_PLUGIN_VERSION', '1.0.0' ); //Plugin version number
}
if( !defined( 'NBT_DIR' ) ) {
    define( 'NBT_DIR', dirname( __FILE__ ) ); // plugin dir
}
if( !defined( 'NBT_URL' ) ) {
    define( 'NBT_URL', plugin_dir_url( __FILE__ ) ); // plugin url
}
if( !defined( 'NBT_BASENAME' ) ) {
    define( 'NBT_BASENAME', basename( NBT_DIR ) ); // base name
}
if( !defined( 'NBT_ADMIN' ) ) {
    define( 'NBT_ADMIN', NBT_DIR . '/admin' ); // plugin admin dir
}
if( !defined( 'NBT_ADMIN_URL' ) ) {
    define( 'NBT_ADMIN_URL', NBT_URL . 'admin' ); // plugin admin dir
}
if( !defined( 'NBT_ASSETS_URL' ) ) {
    define( 'NBT_ASSETS_URL', NBT_URL.'includes/assets/' ); // plugin admin dir
}

function dea_plugin_loaded() {

        //global variables
         global  $nbt_admin, $nbt_scripts, $nbt_public;
        
        
        // // Script Class to manage all scripts and styles
        // include_once( NBT_DIR . '/includes/class_nbt_script.php' );
        // $dlpickup_scripts = new DokanLocalPickupScripts();
        // $dlpickup_scripts->init();
    	
		require_once( NBT_DIR . '/includes/nbt-functions.php' );
	
    
		require_once( NBT_DIR . '/includes/class_nbt_public.php' );

        $nbt_public = new nbtPublic();
        $nbt_public->init();
       
        
        //Admin Pages Class for admin side
        require_once( NBT_ADMIN . '/class_nbt_admin.php' );
        if(is_admin()){
            require_once( NBT_ADMIN . '/class_nbt_bacs_gateway.php' );
        }
        $nbt_admin = new nbtAdmin();
        $nbt_admin->init();

        require_once( NBT_ADMIN . '/class_nbt_settings.php' );
        $nbt_settings = new NBT_Settings();
        $nbt_settings->init();
  		

}

//add action to load plugin
add_action( 'plugins_loaded', 'dea_plugin_loaded' );
function check_dependencies() {
    // List of plugins that depend on this plugin
    $dependent_plugins = [
        'woocommerce/woocommerce.php', 
        'yith-woocommerce-product-add-ons/init.php'
    ];

    // Check if any of the dependent plugins are active
    foreach ($dependent_plugins as $plugin) {
        if (!is_plugin_active($plugin)) {
            // Deactivation warning
            add_action('admin_notices', function() use ($plugin) {
                echo '<div class="notice notice-error"><p>NBT Products Locations plugin ' . esc_html($plugin) . ' depends on this plugin and must be activated  first.</p></div>';
            });
           
            // Prevent deactivation
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'remove_deactivation_link', 10, 4);

            // Prevent deletion
            add_action('admin_init', function() {
                

                if (isset($_GET['action']) && $_GET['action'] == 'delete-selected' && isset($_GET['checked'])) {
                    $plugin_file = plugin_basename(__FILE__);
                    if (in_array($plugin_file, $_GET['checked'])) {
                        wp_die('This plugin cannot be deleted until all dependent plugins are deactivated.');
                    }
                }
            });
        }
    }
}

function remove_deactivation_link($actions) {
    // Debugging line to check actions array
    error_log('Plugin action links: ' . print_r($actions, true));

    if (isset($actions['deactivate'])) {
        unset($actions['deactivate']);
    }
    return $actions;
}
add_action('admin_init', 'check_dependencies');
