<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Stock_Sync_Logger {
	private $context;
	public $site_url = FALSE;

	/**
	 * Constructor
	 */
	public function __construct( $context = 'woo-stock-sync' ) {
		$this->context = array( 'source' => $context );
	}

	/**
	 * Get logger
	 */
	public function get_logger() {
		return wc_get_logger();
	}

	/**
	 * Write debug message
	 */
	public function debug( $msg, $product_id = 0, $sku = '', $ext_product_id = 0, $author = '' ) {
		if ( get_option( 'woo_stock_sync_debug_logging', 'no' ) === 'yes' ) {
			$this->get_logger()->debug( $this->format_msg( $msg, $product_id, $sku, $ext_product_id, $author ), $this->context );
		}
	}

	/**
	 * Write mismatch message
	 */
	public function mismatch( $msg, $product_id = 0, $sku = '', $ext_product_id = 0, $author = '' ) {
		$this->get_logger()->warning( $this->format_msg( $msg, $product_id, $sku, $ext_product_id, $author ), $this->context );
	}

	/**
	 * Write error message
	 */
	public function error( $msg, $product_id = 0, $sku = '', $ext_product_id = 0, $author = '' ) {
		$this->get_logger()->error( $this->format_msg( $msg, $product_id, $sku, $ext_product_id, $author ), $this->context );
	}

	/**
	 * Write success message
	 */
	public function success( $msg, $product_id = 0, $sku = '', $ext_product_id = 0, $author = '' ) {
		$this->get_logger()->info( $this->format_msg( $msg, $product_id, $sku, $ext_product_id, $author ), $this->context );
	}

	/**
	 * Format message
	 */
	private function format_msg( $msg, $product_id, $sku, $ext_product_id, $author ) {
		$product_name = '';
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product_name = $product->get_name();
		}

		if ( $product ) {
			if ( $this->site_url ) {
				return sprintf( "%s (%s) - %s - %s", $product_name, $sku, $msg, $this->site_url );
			} else {
				return sprintf( "%s (%s) - %s", $product_name, $sku, $msg );
			}
		}

		if ( $this->site_url ) {
			return sprintf( '%s - %s', $msg, $this->site_url );
		}

		return $msg;
	}
}
