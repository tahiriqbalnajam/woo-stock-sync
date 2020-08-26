<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Client;

class idl_sync_schedule { 

    private $logger;

    public function __construct() {
        $this->logger = new Woo_Stock_Sync_Logger();
        //$this->schedulenow();
        $this->idl_sync_cron_schedules();
        add_action("woocommerce_process_product_meta", array($this, "woo_add_custom_save"), 1, 10);
        add_action("woocommerce_product_options_general_product_data", array($this, "add_customs_settings"));
        $this->idl_sync_fun();

    }

    public function woo_add_custom_save($post_id)
    {
        $select_checkbox = isset( $_POST['idl_not_sync'] ) ? $_POST['idl_not_sync'] : 'no';
        update_post_meta($post_id, 'idl_not_sync', esc_attr( $select_checkbox ));
    }
    public function add_customs_settings () {
        global $post;
        // Custom Product Checkbox Field
        echo '<div>';
        woocommerce_wp_checkbox (array(
            'name' => 'idl_not_sync', 
            'id'        => 'idl_not_sync',
            'label'         => __('Don\'t Sync?', 'woocommerce' ), 
            'desc_tip'  => 'false'
        ));
        echo '</div>';
    }

    public function idl_sync_cron_schedules() { 
        add_filter('cron_schedules',array($this, 'idl_sync_cron_times'));
        if(!wp_next_scheduled('idl_sync_inventory')){
            add_action('init', array($this,'schedule_idl_sync_cron'));
        }
        add_action( "idl_sync_inventory",array($this,"idl_sync_fun"));
    }

    public function schedule_idl_sync_cron(){
        wp_schedule_event(time(), '5min', 'idl_sync_inventory');
    }

    public function idl_sync_cron_times($schedules){
        if(!isset($schedules["5min"])){
            $schedules["5min"] = array(
                'interval' => 60,
                'display' => __('Once every 5 minutes'));
        }
        if(!isset($schedules["30min"])){
            $schedules["30min"] = array(
                'interval' => 30*60,
                'display' => __('Once every 30 minutes'));
        }
        return $schedules;
    }

    public function idl_sync_fun(){
        foreach (woo_stock_sync_sites() as $value) {
            if($value['api_checkboxenale'] == 'active')
            {
                $url = $value['url'];
                $report = new Woo_Stock_Sync_Report();
                $report->create_report($url, true);
            }
          }


    }



}