<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Stock_Sync_Run {
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger = new Woo_Stock_Sync_Logger();

		add_action( 'woo_stock_sync_run', array( $this, 'run' ), 10, 6 );
	}

	/**
	 * Run stock sync
	 */
	public function run( $product_id, $sku, $operator, $value, $retry_count, $site_url ) {
		$this->logger->debug( sprintf( __( 'Started stock sync with %s', 'woo-stock-sync' ), $site_url ) );
		

		$api = new Woo_Stock_Sync_Api_Request( $product_id, $sku, $operator, $value, $retry_count, $site_url );
		$api->request();

		$this->logger->debug( sprintf( __( 'Finished stock sync with %s', 'woo-stock-sync' ), $site_url ) );
	}
}
