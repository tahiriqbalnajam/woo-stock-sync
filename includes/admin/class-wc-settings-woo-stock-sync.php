<?php

class WC_Settings_Woo_Stock_Sync extends WC_Settings_Page {
	public function __construct() {
		$this->id    = 'woo_stock_sync';
		$this->label = __( 'Stock Sync', 'woo-stock-sync' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );

		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

		// Custom handler for outputting API credential table
		add_action( 'woocommerce_admin_field_wss_credentials_table', array( $this, 'credentials_table' ), 10, 1 );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		$settings = $this->get_general_settings();

		$settings = apply_filters( 'woocommerce_' . $this->id . '_settings', $settings );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get general settings
	 */
	private function get_general_settings() {
		$settings = array(
			array(
				'title' => __( 'Stock Sync', 'woo-stock-sync' ),
				'type' => 'title',
				'id' => $this->id . '_page_options'
			),
    );

		$settings[$this->id . '_enabled'] = array(
			'title' => __( 'Enable', 'woo-stock-sync' ),
			'type' => 'checkbox',
			'id' => $this->id . '_enabled',
			'default' => 'yes',
		);

		$settings[$this->id . '_syncable_data'] = array(
			'title' => __( 'Syncable data', 'woo-stock-sync' ),
			'type' => 'multiselect',
			'class' => 'wc-enhanced-select',
			'id' => $this->id . '_syncable_data',
			'default' => array( 'stock_qty' ),
			'options' => array(
				'stock_qty' => __( 'Stock quantity', 'woo-stock-sync' ),
				//'stock_status' => __( 'Stock status', 'woo-stock-sync' ),
			),
		);

		$settings[$this->id . '_debug_logging'] = array(
			'title' => __( 'Debug Logging', 'woo-stock-sync' ),
			'type' => 'checkbox',
			'id' => $this->id . '_debug_logging',
			'desc' => __( 'Enable debug logging for better logging about stock sync operations. Helpful in solving any issues.', 'woo-stock-sync' ),
			'desc_tip' => TRUE,
		);

		$settings[$this->id . '_syncable_noofrows'] = array(
			'title' => __( 'No of API Credentials', 'woo-stock-sync' ),
			'type' => 'text',
			'id' => $this->id . '_syncable_noofrows',
			'default' => '2',
		);

		$settings[$this->id . '_page_options_end'] = array(
      'type' => 'sectionend',
      'id' => $this->id . '_page_options'
    );

		$settings[$this->id . '_api_settings_start'] = array(
			'title' => __( 'API credentials', 'woo-stock-sync' ),
			'type' => 'title',
			'id' => $this->id . '_api_settings'
		);

		// Add hidden fields for API credentials so they get processed in WC_Admin_Settings
		// Hidden fields dont contain real data, instead fields are outputted in wss_credentials_table
		// which wouldn't get saved without this
		for ( $i = 0; $i < apply_filters( 'woo_stock_sync_supported_api_credentials', get_option( 'woo_stock_sync_syncable_noofrows') ); $i++ ) {
			$fields = array( 'woo_stock_sync_url', 'woo_stock_sync_api_key', 'woo_stock_sync_api_secret','woo_stock_sync_checkboxvalue','woo_stock_sync_vendor' );
			foreach ( $fields as $field ) {
				$settings[$this->id . '_api_credentials_hidden_' . $field . '_' . $i] = array(
					'type' => 'hidden',
					'id' => woo_stock_sync_api_credentials_field_name( $field, $i ),
				);
			}
		}

		$settings[$this->id . '_api_credentials'] = array(
			'title' => __( 'API Credentials', 'woo-stock-sync' ),
			'type' => 'wss_credentials_table',
			'id' => $this->id . '_api_credentials',
			'default' => '',
		);

		$settings[$this->id . '_api_settings_end'] = array(
			'type' => 'sectionend',
			'id' => $this->id . '_api_settings'
		);

		return $settings;
	}

	/**
	 * Output credentials table
	 */
	public function credentials_table( $value ) {
		include 'views/credentials-table.html.php';
	}
}

return new WC_Settings_Woo_Stock_Sync();
