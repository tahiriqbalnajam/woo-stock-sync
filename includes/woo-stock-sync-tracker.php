<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Stock_Sync_Tracker {
	private $is_stock_sync = FALSE;
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Stock quantity
		add_action( 'woocommerce_product_set_stock', array( $this, 'create_job_qty' ), 10, 1 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'create_job_qty' ), 10, 1 );

		// Stock status
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'create_job_status' ), 10, 3 );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'create_job_status' ), 10, 3 );

		add_filter( "woocommerce_rest_pre_insert_product_object", array( $this, 'mark_as_rest' ), 10, 3 );
		add_filter( "woocommerce_rest_pre_insert_product_variation_object", array( $this, 'mark_as_rest' ), 10, 3 );

		$this->logger = new Woo_Stock_Sync_Logger();
	}

	public function mark_as_rest( $product, $request, $creating ) {
		if ( $request->get_param( 'woo_stock_sync' ) == '1' ) {
			$this->is_stock_sync = TRUE;
		}

		return $product;
	}

	/**
	 * General checks whether or not the sync should proceed
	 * 
	 * Applies to both quantity and status syncs
	 */
	public function should_sync( $product ) {
		// Unsupported WooCommerce in use, abort
		if ( ! woo_stock_sync_version_check() ) {
			$this->logger->debug( __( "Unsupported WooCommerce version in use, skipping.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );

			return FALSE;
		}

		// Stock sync not enabled
		if ( get_option( 'woo_stock_sync_enabled', 'yes' ) !== 'yes' ) {
			$this->logger->debug( __( "Stock syncing not enabled in the settings, skipping.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );
			
			return FALSE;
		}

		// Inventory change was triggered by stock syncing, do not create new job
		if ( $this->is_stock_sync ) {
			return FALSE;
		}

		// Product SKU missing, skip
		if ( strlen( (string) $product->get_name() ) === 0 ) {
			$this->logger->debug( __( "Product title missing, skipping.", 'woo-stock-sync' ), $product->get_id(), $product->get_name(  ) );

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Create stock sync job for stock quantity
	 */
	public function create_job_qty( $product ) {
		if ( ! $this->should_sync( $product ) ) {
			return FALSE;
		}

		// Quantity should not be synced
		if ( ! woo_stock_sync_should_sync( 'stock_qty' ) ) {
			$this->logger->debug( __( "Stock quantity not in syncable data. Please check the settings.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );

			return FALSE;
		}

		// Product not managing inventory
		if ( ! $product->managing_stock() ) {
			$this->logger->debug( __( "Product not managing stock, skipping.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );

			return FALSE;
		}

		// Allow 3rd party plugins to determine whether or not sync the stock
		if ( ! apply_filters( 'woo_stock_sync_should_sync', true, $product, 'stock_qty' ) ) {
			$this->logger->debug( __( "Stock sync aborted by 3rd party plugin.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );

			return FALSE;
		}

		// Run syncing
		foreach ( woo_stock_sync_sites() as $site ) {
			do_action( 'woo_stock_sync_run',
				$product->get_id(),
				$product->get_sku( 'edit' ),
				'set', // operator (set, increase, decrease)
				$product->get_stock_quantity(),
				0, // retry count
				$site['url']
			);
		}
	}

	/**
	 * Create stock sync job for stock status
	 */
	public function create_job_status( $product_id, $stock_status, $product ) {
		if ( ! $this->should_sync( $product ) ) {
			return FALSE;
		}

		// Status should not be synced
		if ( ! woo_stock_sync_should_sync( 'stock_status' ) ) {
			$this->logger->debug( __( "Stock status not in syncable data. Please check the settings.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );

			return FALSE;
		}

		if ( $product->managing_stock() ) {
			$this->logger->debug( __( "Product is managing stock quantity, skipping stock status sync since it is set automatically.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );

			return FALSE;
		}

		// Allow 3rd party plugins to determine whether or not sync the stock
		if ( ! apply_filters( 'woo_stock_sync_should_sync', true, $product, 'stock_status' ) ) {
			$this->logger->debug( __( "Stock sync aborted by 3rd party plugin.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );

			return FALSE;
		}

		$stock_status = $product->get_stock_status( 'edit' );
		if ( $stock_status == 'onbackorder' ) {
			$this->logger->debug( __( "Cannot set stock status to backordered via API, setting to out of stock instead.", 'woo-stock-sync' ), $product->get_id(), $product->get_sku( 'edit' ) );
			$stock_status = 'outofstock';
		}

		// Run syncing
		foreach ( woo_stock_sync_sites() as $site ) {
			do_action( 'woo_stock_sync_run',
				$product->get_id(),
				$product->get_sku( 'edit' ),
				'set_status',
				$stock_status,
				0, // retry count
				$site['url']
			);
		}
	}
}
