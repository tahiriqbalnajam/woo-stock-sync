<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Stock_Sync_Admin {
  /**
   * Constructor
   */
  public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 0 );

		// Version check
		add_action( 'init', array( $this, 'version_check' ), 10, 0 );

		// Settings
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ), 10, 1 );

		// Add links to the plugins page
		if ( defined ( 'WOO_STOCK_SYNC_PRO_BASENAME' ) ) {
			add_filter( 'plugin_action_links_' . WOO_STOCK_SYNC_PRO_BASENAME, array( $this, 'add_plugin_links' ), 10, 1 );
		}
		if ( defined ( 'WOO_STOCK_SYNC_BASENAME' ) ) {
			add_filter( 'plugin_action_links_' . WOO_STOCK_SYNC_BASENAME, array( $this, 'add_plugin_links' ), 10, 1 );
		}

		// Update sync status after saving product
		add_action( 'woocommerce_product_object_updated_props', array( $this, 'update_sync_status' ), 10, 2 );

		// Add page for viewing syncable products
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

		// Page for running stock sync report and import
		add_action( 'wp_ajax_woo_stock_sync_run_action', array( $this, 'run_action' ) );

		// AJAX page for checking API access
		// /wp-admin/admin-ajax.php?action=woo_stock_sync_check_api_access
		add_action( 'wp_ajax_woo_stock_sync_check_api_access', array( $this, 'check_api_access' ) );

		//register_activation_hook(__FILE__, array( $this, 'my_activation' ) );
		//add_action( 'my_hourly_event_surprise', array( $this, 'example_add_cron_interval' ) );
		

  }

  	/*function example_add_cron_interval() {
  	run_import();
	 create_report();
	 }
*/
	/**
	 * Version check
	 */
	public function version_check() {
		if ( ! woo_stock_sync_version_check() ) {
			queue_flash_message( __( 'Stock Sync for WooCommerce requires WooCommerce 3.5 or higher. Please update WooCommerce.', 'woo-stock-sync' ), 'error' );
		}
	}

	/**
	 * Add pages
	 */
	public function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Stock Sync', 'woo-stock-sync' ),
			__( 'Stock Sync', 'woo-stock-sync' ),
			'manage_woocommerce',
			'woo-stock-sync-report',
			array( $this, 'report_page' )
		);
	}

	/**
	 * Plugin links
	 */
	public function add_plugin_links( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=woo_stock_sync' );
		$link = '<a href="' . $url . '">' . __( 'Settings' ) . '</a>';
		$links = array_merge( array( $link ), $links );

		if ( ! class_exists( 'Woo_Stock_Sync_Pro' ) ) {
			$link = '<span style="font-weight:bold;"><a href="https://wooelements.com/products/stock-sync-pro" style="color:#46b450;" target="_blank">' . __( 'Go Pro' ) . '</a></span>';

			//$links = array_merge( array( $link ), $links );
		}

	  return $links;
	}

	/**
	 * Check API access
	 */
	public function check_api_access() {
		$api_url = isset( $_POST['url'] ) ? trim( $_POST['url'] ) : '';
		$api_key = isset( $_POST['key'] ) ? trim( $_POST['key'] ) : '';
		$api_secret = isset( $_POST['secret'] ) ? trim( $_POST['secret'] ) : '';
		//$api_checkboxenale = isset( $_POST['checkboxenale'] ) ? trim( $_POST['checkboxenale'] ) : '';


		$client = Woo_Stock_Sync_Api_Client::create( $api_url, $api_key, $api_secret );

		// Check read access
		try {
			$client->get( 'products' );
		} catch ( \Exception $e ) {
			$this->check_api_access_return( $e );
			return;
		}

		// Check write access
		try {
			// In order to test write access, we need to create test product
			// which will be deleted after testing
			$client->post( 'products', array(
				'name' => 'Test product created by Stock Sync for WooCommerce',
				'type' => 'simple',
				'price' => 1,
				'status' => 'private',
			) );
		} catch ( \Exception $e ) {
			$this->check_api_access_return( $e );
			return;
		}

		$response = $client->http->getResponse();
		if ( $response->getCode() == 201 ) {
			$product = json_decode( $response->getBody() );

			// Delete the product we just created
			// Double-check name just in case
			if ( $product->name === 'Test product created by Stock Sync for WooCommerce' ) {
				try {
					$client->delete( "products/{$product->id}", array(
						'force' => true, // Delete permanently (no trash)
					) );
				} catch ( \Exception $e ) {
					$this->check_api_access_return( $e );
					return;
				}
			}
		}

		$this->check_api_access_return();
	}

	/**
	 * Return JSON info for API access check
	 */
	private function check_api_access_return( $e = false ) {
		$error = false;

		if ( is_object( $e ) && method_exists( $e, 'getMessage' ) ) {
			// Store details for further debugging
			if ( get_class( $e ) === 'Automattic\WooCommerce\HttpClient\HttpClientException' ) {
				$logger = new Woo_Stock_Sync_Logger( 'woo-stock-sync-exception' );

				$response = $e->getResponse();
				$request = $e->getRequest();
				$logger->error( sprintf( __( "HTTP exception when verifying credentials.\nURL: %s\nResponse code: %s\nResponse body:\n%s", 'woo-stock-sync' ), $request->getUrl(), $response->getCode(), $response->getBody() ) );
			}

			$error = $e->getMessage();

			if ( $error === 'Syntax error' ) {
				$error = sprintf( __( 'Syntax error. Please see <a href="%s" target="_blank">the documentation</a> for more information', 'woo-stock-sync' ), 'https://wooelements.com/products/stock-sync-pro/guide#heading-4' );
			}
		}

		echo json_encode( array(
			'success' => ! $error,
			'error' => $error,
		) );
		die;
	}

	/**
	 * Run action (report or import)
	 */
	public function run_action() {
		if ( ! isset( $_POST['woo_stock_sync_action_site'] ) || empty( $_POST['woo_stock_sync_action_site'] ) ) {
			queue_flash_message( __( 'Please select a site to run the action on.', 'woo-stock-sync' ), 'error' );

			// Redirect
			wp_safe_redirect( admin_url( 'admin.php?page=woo-stock-sync-report' ) );
			die;
		}

		$url = $_POST['woo_stock_sync_action_site'];

		if ( isset( $_POST['run_report'] ) ) {
			$this->run_report( $url );
		} else if ( isset( $_POST['run_import'] ) ) {
			$this->run_import( $url );
		} else {
			queue_flash_message( __( 'Unknown action', 'woo-stock-sync' ), 'error' );
		}

		// Redirect
		wp_safe_redirect( admin_url( 'admin.php?page=woo-stock-sync-report' ) );
		die;
	}

	/**
	 * Run report
	 */
	public function run_report( $url ) {
		$report = new Woo_Stock_Sync_Report();
		$report->create_report( $url );

		queue_flash_message( sprintf( __( 'Sync status updated with %s', 'woo-stock-sync' ), $url ) );

		// Redirect
		wp_safe_redirect( admin_url( 'admin.php?page=woo-stock-sync-report' ) );
		die;
	}

	/**
	 * Run import
	 */
	public function run_import( $url ) {
		$report = new Woo_Stock_Sync_Report();
		$report->import( $url );

		queue_flash_message( sprintf( __( 'Stock quantities imported from %s', 'woo-stock-sync' ), $url ) );

		// Redirect
		wp_safe_redirect( admin_url( 'admin.php?page=woo-stock-sync-report' ) );
		die;
	}

	/**
	 * Add settings page
	 */
	public function add_settings_page( $settings ) {
		$settings[] = include_once( plugin_dir_path( __FILE__ ) . 'class-wc-settings-woo-stock-sync.php' );

		return $settings;
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_scripts() {
		if ( defined( 'WOO_STOCK_SYNC_PRO_VERSION' ) ) {
			$version = WOO_STOCK_SYNC_PRO_VERSION . '.pro';
		} else if ( defined( 'WOO_STOCK_SYNC_VERSION' ) ) {
			$version = WOO_STOCK_SYNC_VERSION . '.free';
		} else {
			$version = FALSE;
		}

		wp_enqueue_style( 'woo-stock-sync-admin-css', plugin_dir_url( __FILE__ ) . '../../admin/css/woo-stock-sync-admin.css', array(), $version );

		wp_enqueue_script( 'woo-stock-sync-admin-js', plugin_dir_url( __FILE__ ) . '../../admin/js/woo-stock-sync-admin.js', array( 'jquery' ), $version );

		wp_localize_script( 'woo-stock-sync-admin-js', 'woo_stock_sync_settings', array(
			'check_credentials' => __( 'Check API credentials', 'woo-stock-sync' ),
			'check_credentials_success' => __( 'Valid credentials', 'woo-stock-sync' ),
		) );
	}

	/**
	 * Update sync status after changing SKU
	 */
	public function update_sync_status( $product, $updated_props ) {
		if ( in_array( 'sku', $updated_props ) ) {
			$report = new Woo_Stock_Sync_Report();
			$report->update_sync_status( $product );
		}
	}

	/**
	 * Page for viewing stock sync status
	 */
	public function report_page() {
		$table = new Woo_Stock_Sync_Report_List_Table();
	  $table->prepare_items();

	  // Show error msg if there is more than 100 products
	  $product_count = (array) wp_count_posts( 'product' );
	  $product_count = array_sum( $product_count );
	  $limit_reached = $product_count > apply_filters( 'woo_stock_sync_report_limit', 100000 );

		include( __DIR__ . '/views/report.php' );
	}
}
