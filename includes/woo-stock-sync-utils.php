<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get other sites which are to be sync
 */
function woo_stock_sync_sites() {
	$sites = array();

	for ( $i = 0; $i < apply_filters( 'woo_stock_sync_supported_api_credentials', get_option( 'woo_stock_sync_syncable_noofrows') ); $i++ ) {
		$url = woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_url', $i );
		$api_key = woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_api_key', $i );
		$api_secret = woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_api_secret', $i );
		$checkboxenale = woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_checkboxvalue', $i );
		$vendor = woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_vendor', $i );
		//$localproduct = woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_localproduct', $i );

		if ( ! empty( $url ) && ! empty( $api_key ) && ! empty( $api_secret ) ) {
			$sites[$i] = array(
				'i' => $i,
				'url' => $url,
				'api_key' => $api_key,
				'api_secret' => $api_secret,
				'api_checkboxenale' => $checkboxenale,
				'vendor' => $vendor,
			);
		}
	}

	return $sites;
}

/**
 * Get syncable data from settings
 */
function woo_stock_sync_syncable_data() {
	return get_option( 'woo_stock_sync_syncable_data', array( 'stock_qty' ) );
}

/**
 * Checks if data field should be synced
 */
function woo_stock_sync_should_sync( $field ) {
	$syncable_data = woo_stock_sync_syncable_data();

	return in_array( $field, $syncable_data );
}

/**
 * Get status label (syncing, not syncing, mismatch) for a product
 */
function woo_stock_sync_status_label( $product_id, $url ) {
	if ( woo_stock_sync_is_syncing( $product_id, $url ) ) {
		if ( woo_stock_sync_mismatching( $product_id, $url ) ) {
			return '<span class="woo-stock-sync-mismatch-label" title="' . esc_attr( __( 'Stock mismatches with local stock', 'woo-stock-sync' ) ) . '"></span>';
		} else {
			return '<span class="woo-stock-sync-yes-label"></span>';
		}
	}

	#return '<span class="woo-stock-sync-no-label"></span>';
}

/**
 * Check if product is syncing or not
 */
function woo_stock_sync_is_syncing( $product_id, $url ) {
	$data = get_post_meta( $product_id, '_woo_stock_sync_syncable', TRUE );

	if ( $data && is_array( $data ) && isset( $data[$url] ) && ! empty( $data[$url] ) ) {
		return TRUE;
	}

	return FALSE;
}

/**
 * Check if product stock mismatches with local quantity
 */
function woo_stock_sync_mismatching( $product_id, $url ) {
	$data = get_post_meta( $product_id, '_woo_stock_sync_syncable', TRUE );
	$product = wc_get_product( $product_id );

	if ( isset( $data[$url] ) && isset( $data[$url]['stock_quantity'] ) && intval( $data[$url]['stock_quantity'] ) === intval( $product->get_stock_quantity( 'edit' ) ) ) {
		return FALSE;
	}

	return TRUE;
}

/**
 * Get external stock quantity
 */
function woo_stock_external_stock_qty( $product_id, $url ) {
	$meta = get_post_meta( $product_id, '_woo_stock_sync_syncable', TRUE );

	if ( $meta && is_array( $meta ) && isset( $meta[$url] ) && isset( $meta[$url]['stock_quantity'] ) ) {
		return $meta[$url]['stock_quantity'];
	}

	return FALSE;
}

/**
 * Check if WooCommerce 3.5 or higher is running
 */
function woo_stock_sync_version_check() {
	if ( class_exists( 'WooCommerce' ) ) {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, '3.5', '>=' ) ) {
			return TRUE;
		}
	}

	return FALSE;
}

/**
 * Handle order by syncing status in WC_Product_Query
 */
function woo_stock_sync_sync_status_orderby( $query, $query_vars ) {
	if ( ! empty( $query_vars['orderby_sync_status'] ) && $query_vars['orderby_sync_status'] ) {
		$query['orderby'] = 'meta_value';
		$query['meta_key'] = '_woo_stock_sync_syncing_' . $query_vars['orderby_sync_status_sanitized_url'];

		if ( $query['order'] == 'asc' ) {
			$query['order'] = 'desc';
		} else {
			$query['order'] = 'asc';
		}
	}

	return $query;
}
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'woo_stock_sync_sync_status_orderby', 10, 2 );

/**
 * Format API credentials field name
 */
function woo_stock_sync_api_credentials_field_name( $name, $i ) {
  if ( $i == 0 ) {
    return $name;
  }

  return sprintf( '%s_%d', $name, $i );
}

/**
 * Get API credentials field value
 */
function woo_stock_sync_api_credentials_field_value( $name, $i, $default = '' ) {
  if ( $i == 0 ) {
    $value_key = $name;
  } else {
    $value_key = sprintf( '%s_%d', $name, $i );
  }

  return get_option( $value_key, $default );
}

