<?php
/**
 * Plugin Name: Ordered Variable Product Report
 * Description: A Woocommerce Plugin to display and export ordered varaible product report.
 * Author: CedCommerce
 * Author URI: http://cedcommerce.com/
 * Requires at least: 4.0
 * Tested up to: 5.2.0
 * Version: 1.0.6
 * Text Domain: ordered-variable-product-report
 * Domain Path: /languages
 * 
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$activated = true;

if ( function_exists('is_multisite') && is_multisite() ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$activated = false;
	}
} else {
	/**
	 * Check if WooCommerce is active
	 **/
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )	{
		define('WOO_EXPORT_VARIABLE_PRODUCT', plugin_dir_path( __FILE__ ));
		
		if( ! class_exists( 'Ced_wvope' ) ) {
			class Ced_wvope {
				/**
				 * Hook into the appropriate actions when the class is constructed.
				 * 
				 * @name __construct
 				 * @author CedCommerce <plugins@cedcommerce.com>
 				 * @link http://cedcommerce.com/
				 */
				public function __construct() {
					$plugin = plugin_basename(__FILE__);

					define( 'WOPE_PLUGIN_DIR', plugin_dir_path(__FILE__) );
					define( 'WOPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
					define( 'WOPE_VERSION', '1.0.5' );

					add_action( 'plugins_loaded', array( $this, 'ced_wovpe_load_text_domain' ) );
					add_action( "init", array( $this, 'ced_export_activation' ) );
					add_action( "plugin_action_links_$plugin", array( $this, "ced_wovpe_add_settings_link"));
				}

				/**
				 * This function loads the text domain
				 * @name ced_wovpe_load_text_domain
 				 * @author CedCommerce <plugins@cedcommerce.com>
 				 * @link http://cedcommerce.com/
				 */
				function ced_wovpe_load_text_domain() {
					$domain = "ordered-variable-product-report";
					$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
					load_textdomain( $domain, WOO_EXPORT_VARIABLE_PRODUCT .'languages/'.$domain.'-' . $locale . '.mo' );
					load_plugin_textdomain( 'ordered-variable-product-report', false, plugin_basename( dirname(__FILE__) ) . '/languages' );
				}

				/**
				 * This function add setting link
				 * 
				 * @name ced_wovpe_add_settings_link
 				 * @author CedCommerce <plugins@cedcommerce.com>
 				 * @link http://cedcommerce.com/
				 */
				function ced_wovpe_add_settings_link( $links ) {
					$settings_link = '<a href="'.get_admin_url().'admin.php?page=wc-export_variable_products">Settings</a>';
					array_unshift( $links, $settings_link );
					return $links;
				}

				/**
				 * This function initialize the plugin
				 * 
				 * @name ced_export_activation
				 * @author CedCommerce <plugins@cedcommerce.com>
 				 * @link http://cedcommerce.com/
				 */
				function ced_export_activation() { 
					require_once WOO_EXPORT_VARIABLE_PRODUCT.'class/ced-export-variable-product-class.php';
					require_once WOO_EXPORT_VARIABLE_PRODUCT.'export/ced-export-variable-function.php';
				}
			}
			$GLOBALS['ced_wvope'] = new Ced_wvope;
		}
	}
	}?>