<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Client;

class Woo_Stock_Sync_Api_Request {
	private $product_id;
	private $sku;
	private $operator;
	private $value;
	private $ext_product_id;
	private $ext_product;
	private $retry_count;
	private $site_url;
	private $site = FALSE;
	private $client = NULL;
	private $logger = NULL;

	/**
	 * Constructor
	 */
	public function __construct( $product_id, $sku, $operator, $value, $retry_count, $site_url = FALSE ) {
		$this->product_id = $product_id;
		$this->sku = $sku;
		$this->operator = $operator;
		$this->value = $value;
		$this->retry_count = $retry_count;
		$this->site_url = $site_url;

		// Set site by URL
		foreach ( woo_stock_sync_sites() as $site ) {
			if ( $site['url'] == $this->site_url && $site['api_checkboxenale']=="active") {
				$this->site = $site;
				break;
			}
		}

		$this->logger = new Woo_Stock_Sync_Logger();
		$this->logger->site_url = $site_url;
	}

	/**
	 * Make request
	 */
	public function request() {
		if ( ! $this->site ) {
			$this->logger->error( sprintf( __( 'Site not set for %s, aborting', 'woo-stock-sync' ), $this->site_url ) );
			return FALSE;
		}

		if ( $this->find_product_id_by_sku() === TRUE ) {
			if ( $this->update_inventory() === TRUE ) {
				return TRUE;
			}
		}

		if ( $this->may_retry ) {
			$this->schedule_for_retry();
		}

		return FALSE;
	}

	/**
	 * Schedule for retry
	 */
	public function schedule_for_retry() {
		if ( $this->retry_count > 3 ) {
			$this->logger->error( __( 'Aborting sync job, too many retries.', 'woo-stock-sync' ) );

			return FALSE;
		}

		$args = array(
			'product_id' => $this->get_product()->get_id(),
			'sku' => $this->get_product()->get_sku( 'edit' ),
			'operator' => $this->operator,
			'value' => $this->value,
			'retry_count' => $this->retry_count + 1,
			'site_url' => $this->site_url,
		);

		// First retry will be done immediately
		if ( $args['retry_count'] === 1 ) {
			$this->logger->debug( __( "Stock sync failed, retrying immediately", 'woo-stock-sync' ), $this->get_product()->get_id(), $this->get_product()->get_sku( 'edit' ) );

			$args = array( 'action' => 'woo_stock_sync_run' ) + $args;
			call_user_func_array( 'do_action', array_values( $args ) );
		}
		// Each retry after the first one will be postponed 10 seconds
		else {
			$this->logger->debug( __( "Stock sync failed, retrying in 10 seconds", 'woo-stock-sync' ), $this->get_product()->get_id(), $this->get_product()->get_sku( 'edit' ) );

			$run_at = time() + 10;

			wp_schedule_single_event( $run_at, 'woo_stock_sync_run', $args );
		}
	}

	/**
	 * Send product inventory to another store
	 */
	private function update_inventory() {
		$client = $this->get_api_client();
		$params = FALSE;
		switch ( $this->operator ) {
			case 'set':
				$success_msg = sprintf( __( "Set stock quantity to %d", 'woo-stock-sync' ), $this->value );
				$params = array( 'stock_quantity' => $this->value );
				break;
			case 'increase':
				$success_msg = sprintf( __( "Increased stock quantity by %d", 'woo-stock-sync' ), $this->value );
				$params = array( 'inventory_delta' => absint( $this->value ) );
				break;
			case 'decrease':
				$success_msg = sprintf( __( "Decreased stock quantity by %d", 'woo-stock-sync' ), $this->value );
				$params = array( 'inventory_delta' => -1 * absint( $this->value ) );
				break;
			case 'set_status':
				$success_msg = sprintf( __( "Set stock status to %s", 'woo-stock-sync' ), $this->value );
				$params = array( 'in_stock' => ( $this->value === 'instock' ) );
				break;
		}

		if ( ! $params ) {
			$this->may_retry = FALSE;

			$this->logger->error( __( "Couldn't form parameters while trying to push update", 'woo-stock-sync' ), $this->product_id, $this->sku );

			return FALSE;
		}

		$params['woo_stock_sync'] = '1';
		$params['woo_stock_sync_source'] = get_site_url();

		try {
			$client->post( $this->get_update_endpoint(), $params );
		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( __( "Exception while trying to push update. Message: %s", 'woo-stock-sync' ), $e->getMessage() ), $this->product_id, $this->sku );
			$this->may_retry = TRUE;

			return FALSE;
		}

		$response = $client->http->getResponse();
		if ( $response->getCode() === 200 ) {
			$ext_product = json_decode( $response->getBody() );

			// Record stock mismatch (external stock is different than local)
			if ( $this->operator !== 'set_status' && $ext_product->stock_quantity != $this->get_product()->get_stock_quantity() ) {
				$mismatch_msg = sprintf( __( 'Stock mismatch, local quantity %d and external quantity %d', 'woo-stock-sync' ), $this->get_product()->get_stock_quantity(), $ext_product->stock_quantity );

				$this->logger->mismatch( $mismatch_msg, $this->product_id, $this->sku, $this->ext_product_id );
			}

			// @TODO Log source (e.g. order decrease / increase or admin edit)

			$this->logger->success( $success_msg, $this->product_id, $this->sku, $this->ext_product_id );

			return TRUE;
		}

		$this->logger->error( sprintf( __( "Connection error while trying to push update. Response code: %d", 'woo-stock-sync' ), $response->getCode() ), $this->product_id, $this->sku, $this->ext_product_id );

		$this->may_retry = TRUE;

		return FALSE;
	}

	/**
	 * Gets product update endpoint URL
	 */
	private function get_update_endpoint() {
		if ( $this->ext_product && $this->ext_product->type === 'variation' ) {
			return "products/{$this->ext_product->parent_id}/variations/{$this->ext_product_id}";
		}

		return "products/{$this->ext_product_id}";
	}

	/**
	 * Find product ID by SKU
	 */
	private function find_product_id_by_sku() {
		$client = $this->get_api_client();

		try {
			$client->get( 'products', array( 'sku' => $this->sku ) );
		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( __( "Exception while trying to find external product ID. Message: %s", 'woo-stock-sync' ), $e->getMessage() ), $this->product_id, $this->sku );
			$this->may_retry = TRUE;

			return FALSE;
		}

		$response = $client->http->getResponse();
		if ( $response->getCode() === 200 ) {
			$results = json_decode( $response->getBody() );

			if ( ! empty( $results ) ) {
				$result = reset( $results );

				$this->ext_product_id = $result->id;
				$this->ext_product = $result;

				return TRUE;
			}

			// Product not found, debug message
			$this->may_retry = FALSE;
			$this->logger->debug( __( "External product not found.", 'woo-stock-sync' ), $this->product_id, $this->sku );

			return FALSE;
		}

		$this->logger->error( sprintf( __( "Connection error while trying to find external product ID. Response code: %d", 'woo-stock-sync' ), $response->getCode() ), $this->product_id, $this->sku );

		$this->may_retry = TRUE;

		return FALSE;
	}

	/**
	 * Get product
	 */
	private function get_product() {
		return wc_get_product( $this->product_id );
	}

	/**
	 * Get API client
	 */
	private function get_api_client() {
		return Woo_Stock_Sync_Api_Client::create( $this->site['url'], $this->site['api_key'], $this->site['api_secret'] );
	}
}
