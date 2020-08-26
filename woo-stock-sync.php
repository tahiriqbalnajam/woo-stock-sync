<?php

/*
Plugin Name: customised Stock Sync for WooCommerce
Description: Stock synchronization for WooCommerce. Share same product stock in two WooCommerce stores.
Version:     1.2.2
Author:      Tahir Iqbal
Author URI:  https://Tahiriqbal.com
Text Domain: woo-stock-sync
Domain Path: /languages
WC requires at least: 3.5.0
WC tested up to: 3.9.1
*/

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version
 */
if ( ! defined( 'WOO_STOCK_SYNC_VERSION' ) ) {
	define( 'WOO_STOCK_SYNC_VERSION', '1.2.2' );
}

/**
 * Plugin basename
 */
if ( ! defined( 'WOO_STOCK_SYNC_BASENAME' ) ) {
	define( 'WOO_STOCK_SYNC_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Load Composer libs
 */
require __DIR__ . '/vendor/autoload.php';

/**
 * Load plugin textdomain
 *
 * @return void
 */
add_action( 'plugins_loaded', 'woo_stock_sync_load_textdomain' );
function woo_stock_sync_load_textdomain() {
  load_plugin_textdomain( 'woo-stock-sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

class Woo_Stock_Sync {
	/**
	 * Constructor
	 */
	public function __construct() {
		// Pro version exists, abort
		if ( class_exists( 'Woo_Stock_Sync_Pro' ) ) {
			return;
		}

		$this->includes();
	}

	/**
	 * Include required files
	 */
	public function includes() {
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-stock-sync-utils.php' );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-stock-sync-logger.php' );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-stock-sync-api-client.php' );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-stock-sync-api-request.php' );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-stock-sync-tracker.php', 'Woo_Stock_Sync_Tracker' );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-stock-sync-run.php', 'Woo_Stock_Sync_Run' );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-stock-sync-report.php' );

		if ( is_admin() ) {
			$this->admin_includes();
		}

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/frontend/class-woo-stock-sync-frontend.php', 'Woo_Stock_Sync_Frontend' );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/idl_sync_schedule.php', 'idl_sync_schedule' );
	}

	/**
	 * Include admin files
	 */
	private function admin_includes() {
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/wp-flash-messages.php', FALSE );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/admin/report-list-table.php', FALSE );
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/admin/class-woo-stock-sync-admin.php', 'Woo_Stock_Sync_Admin' );
	}

	/**
	 * Load class
	 */
	private function load_class( $filepath, $class_name = FALSE ) {
		require_once( $filepath );

		if ( $class_name ) {
			return new $class_name;
		}

		return TRUE;
	}
}

add_action( 'plugins_loaded', 'woo_stock_sync_load', 15 );
function woo_stock_sync_load() {
	new Woo_Stock_Sync();
}
