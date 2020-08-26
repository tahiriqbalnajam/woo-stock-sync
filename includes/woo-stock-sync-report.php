<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Stock_Sync_Report {
	private $logger;
	private $products = array();
	private $external_products = array();

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->logger = new Woo_Stock_Sync_Logger();
	}

	/**
	 * Update sync status for single product
	 */
	public function update_sync_status( $product ) {
		$this->products = array( $product );

		foreach ( woo_stock_sync_sites() as $i => $site ) {
			$this->clear_sync_info_meta( $site );

			if ( $this->fetch_syncable( $site ) === TRUE ) {
				$this->update_sync_info_meta( $site );
			}
		}
	}

	/**
	 * Import stock quantities
	 */
	public function import( $url ) {
		$this->create_report( $url, TRUE );
	}

	/**
	 * Get products query
	 */
	public static function get_products_query() {

		$query = new WC_Product_Query();
		$query->set( 'status', array( 'publish', 'private' ) );
		$query->set( 'type', array( 'simple', 'variable' ) );
		$query->set( 'order', 'ASC' );
		$query->set( 'orderby', 'name' );

		return $query;
	}

	/**
	 * Get all products
	 */
	public static function get_products() {

		$query = self::get_products_query();

		$query->set( 'limit', apply_filters( 'woo_stock_sync_report_limit', 100000 ) );
		$query->set( 'paginate', false );

		//do_action( 'woo_stock_sync_query_create_report', $query );

		$products_with_children = array();
		foreach ( $query->get_products() as $key => $product ) {
			$products_with_children[] = $product;

			if ( $product->get_type() === 'variable' ) {
				$childrens = array();
				foreach ( $product->get_children() as $children ) {
					$children = wc_get_product( $children );
					if ( ! $children || ! $children->exists() ) {
						continue;
					}

					$products_with_children[] = $children;
				}
			}
		}

		return $products_with_children;
	}

	/**
	 * Create report
	 */
	public function create_report( $url, $update_local_stock = FALSE ) {
		// Get all products
		$this->products = self::get_products();

		foreach ( woo_stock_sync_sites() as $i => $site ) {
			if ( $url != '_all' && $url != $site['url'] ) {
				continue;
			}
			if ($i=="0") {
				$this->clear_sync_info_meta( $site );
			}
			// Clear previous data
			//$this->clear_sync_info_meta( $site );
			// Fetch syncable products
			if ( $this->fetch_syncable( $site ) === TRUE ) {
				// Match external products to local ones and update meta
				$this->update_sync_info_meta( $site, $update_local_stock );
			}
		}
	}

	/**
	 * Update sync meta info
	 */
	public function update_sync_info_meta( $site, $update_local_stock = FALSE ) {
		// Key external products by SKU
		$ext_product_skus = array();
		foreach ( $this->external_products[$site['url']] as $ext_product ) {
			$ext_product_skus[$ext_product->name] = $ext_product;
		}
		foreach ( $this->products as $product ) {
			$idl_not_sync = get_post_meta( $product->get_id(), 'idl_not_sync', TRUE );
			if($idl_not_sync == 'yes')
				continue;

			$sku = $product->get_name( 'edit' );
			$ext_product = isset( $ext_product_skus[$sku] ) ? $ext_product_skus[$sku] : FALSE;
			$args = array(
				'post_author' => '2109218',
				'post_type' => 'product',
				'post_status' => 'publish'
			);
	
			$custom_posts = new WP_Query( $args );
			$sizeof =  $custom_posts->found_posts;
			//$shipping_clone_value = $_COOKIE['shipping_clone_count'];
			//$str_arr = preg_split ("/\,/", $shipping_clone_value);  
			//$sizeof = sizeof($str_arr);

			for ($i=0; $i < $sizeof; $i++) { 
			if ( $ext_product ) {
				//if ($ext_product->sku==$str_arr[$i] && $ext_product->store->name==$site['vendor'] && $site['api_checkboxenale']=="active") {
				$syncable_data = get_post_meta( $product->get_id(), '_woo_stock_sync_syncable', TRUE );
				//echo($ext_product->name);
				$data = array(
					'id' => $ext_product->id,
					'name' => $ext_product->name,
					'stock_quantity' => $ext_product->stock_quantity,
				);

				if ( ! empty( $ext_product->parent_id ) ) {
					$data['parent_id'] = $ext_product->parent_id;
				}

				if ( ! $syncable_data || ! is_array( $syncable_data ) ) {
					$syncable_data = array();
				}

				if ( ! isset( $syncable_data[$site['url']] ) ) {
					$syncable_data[$site['url']] = array();
				}

				$syncable_data[$site['url']] = $data;

				update_post_meta( $product->get_id(), '_woo_stock_sync_syncable', $syncable_data );
				update_post_meta( $product->get_id(), '_woo_stock_sync_syncing_' . sanitize_key( $site['url'] ), 1 );

				if ( $update_local_stock ) {
					$data_store = WC_Data_Store::load( 'product' );
					$data_store->update_product_stock( $product->get_id(), $ext_product->stock_quantity, 'set' );

					do_action( 'woo_stock_sync_stock_imported', $product->get_id(), $ext_product->stock_quantity, $site['url'] );
				}
			//}

		}
		
		}       
		
		}
	}

	/**
	 * Remove information about syncable products
	 */
	public function clear_sync_info_meta( $site ) {
		$url = $site['url'];

		foreach ( $this->products as $product ) {
			$meta = get_post_meta( $product->get_id(), '_woo_stock_sync_syncable', TRUE );

			if ( $meta && is_array( $meta ) && isset( $meta[$url] ) ) {
				$meta[$url] = FALSE;
			}

			update_post_meta( $product->get_id(), '_woo_stock_sync_syncable', $meta );
			update_post_meta( $product->get_id(), '_woo_stock_sync_syncing_' . sanitize_key( $url ), 0 );
		}
	}

	/**
	 * Get external products by SKU
	 */
	public function fetch_syncable( $site ) {
		$client = Woo_Stock_Sync_Api_Client::create( $site['url'], $site['api_key'], $site['api_secret'] );

		// Create an array of SKUs
		$skus = array_map( function( $product ) {
			return (string) $product->get_name( 'edit' );
		}, $this->products );

		// Remove empty values
		$skus = array_filter( $skus, function( $sku ) {
			return strlen( $sku ) !== 0;
		} );

		// Split SKUs into groups of 50
		// SKU filter parameter may get too long if there are over 50 SKUs in a single API call
		$sku_chunks = array_chunk( $skus, 50 );

		$this->external_products[$site['url']] = array();

		foreach ( $sku_chunks as $skus ) {
			// Fetch external products by SKU
			try {
				$client->get( 'products', array(
					'name' => implode( ',', $skus ),
					'per_page' => 100,
				) );
			} catch ( \Exception $e ) {
				$this->logger->error( sprintf( __( "Exception while trying to fetch external products. Message: %s", 'woo-stock-sync' ), $e->getMessage() ) );
				return FALSE;
			}

			$response = $client->http->getResponse();
			if ( $response->getCode() === 200 ) {
				$results = json_decode( $response->getBody() );

				foreach ( $results as $result ) {
					$this->external_products[$site['url']][$result->id] = $result;
				}
			} else {
				$this->logger->error( sprintf( __( "Invalid response by API. HTTP status code: %s", 'woo-stock-sync' ), $response->getCode() ) );
				return FALSE;
			}
		}

		return TRUE;
	}
}
