<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Woo_Stock_Sync_Report_List_Table extends WP_List_Table {
  function __construct(){
    global $status, $page;

    parent::__construct( array(
      'singular' => 'product',
      'plural' => 'products',
      'ajax' => false
    ) );
  }

  function column_default( $item, $column_name ) {
    if ( strpos( $column_name, 'ext_stock_' ) !== FALSE ) {
      return $this->column_ext_stock( $item, str_replace( 'ext_stock_', '', $column_name ) );
    }

    return apply_filters( 'woo_stock_sync_report_column_content_' . $column_name, "handler missing for {$column_name}", $item );
  }

  function column_title( $item ) {
    if ( $item->get_type() === 'variation' ) {
      return str_repeat( '&nbsp;', 5 ) . wc_get_formatted_variation( $item, $flat = true, $include_names = false, $skip_attributes_in_name = false );
    } else {
      return sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'post.php?post=' . $item->get_id() . '&action=edit' ),
        $item->get_name()
      );
    }
  }

  function column_sku( $item ) {
    return $item->get_sku( 'edit' );
  }

  function column_local_stock( $item ) {
    return $item->get_stock_quantity( 'edit' );
  }

  function column_ext_stock( $item, $sanitized_url ) {
    $sites = woo_stock_sync_sites();

    $url = FALSE;
    foreach ( $sites as $site ) {
      if ( sanitize_key( $site['url'] ) == $sanitized_url ) {
        $url = $site['url'];
        break;
      }
    }

    if ( $url ) {
      $qty = woo_stock_external_stock_qty( $item->get_id(), $url );
      $syncing = woo_stock_sync_status_label( $item->get_id(), $url );

      if ( woo_stock_sync_is_syncing( $item->get_id(), $url ) ) {
        return sprintf( '%s %s', $syncing, $qty );
      } else {
        return $syncing;
      }
    }

    return __( 'N/A', 'woo-stock-sync' );
  }

  function column_cb( $item ) {
    return sprintf(
      '<input type="checkbox" name="chk" value="'.$item->get_sku().'" />',
      $this->_args['singular'],
      $item->get_id()
    );

  }

  function get_columns(){
    $columns = array(
      'cb' => '<input type="checkbox" />', // Render a checkbox instead of text
      'title' => __( 'Product', 'woo-stock-sync' ),
      'sku' => __( 'SKU', 'woo-stock-sync' ),
      'local_stock' => __( 'Local stock', 'woo-stock-sync' ),
    );

    // Add each external stock
    foreach ( woo_stock_sync_sites() as $i => $site ) {
      $columns['ext_stock_' . sanitize_key( $site['url'] )] = sprintf( '%s<br>(status / stock qty)', $site['url'] );
    }

    $columns = apply_filters( 'woo_stock_sync_report_columns', $columns );
    
    return $columns;
  }

  function get_sortable_columns() {
    $sortable_columns = array(
      'title' => array( 'title', false ),
    );

    foreach ( woo_stock_sync_sites() as $i => $site ) {
      $sortable_columns['ext_stock_' . sanitize_key( $site['url'] )] = array( 'ext_stock_' . sanitize_key( $site['url'] ), false );
    }

    return $sortable_columns;
  }

  function get_bulk_actions() {
    $actions = array(
      #'delete'    => 'Delete'
    );

    return $actions;
  }

  function process_bulk_action() {
    //Detect when a bulk action is being triggered...
    #if( 'delete'===$this->current_action() ) {
    #    wp_die('Items deleted (or they would be if we had items to delete)!');
    #}
  }

  function prepare_items() {

    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array( $columns, $hidden, $sortable );

    # $this->process_bulk_action();

    $current_page = $this->get_pagenum();

    $per_page = apply_filters( 'woo_stock_sync_report_limit', 100000 );

    $query = Woo_Stock_Sync_Report::get_products_query();

    if ( isset( $_GET['orderby'] ) && isset( $sortable[$_GET['orderby']] ) ) {
      if ( $_GET['orderby'] === 'title' ) {
        $query->set( 'orderby', 'name' );
      } else if ( strpos( $_GET['orderby'], 'ext_stock_' ) !== FALSE ) {
        $sanitized_url = str_replace( 'ext_stock_', '', $_GET['orderby'] );

        $query->set( 'orderby_sync_status', true );
        $query->set( 'orderby_sync_status_sanitized_url', $sanitized_url );
      }

      if ( isset( $_GET['order'] ) && in_array( $_GET['order'], array( 'asc', 'desc' ) ) ) {
        $query->set( 'order', $_GET['order'] );
      }
    }

    $query->set( 'page', 1 );
    $query->set( 'limit', $per_page );
    $query->set( 'paginate', false );

    do_action( 'woo_stock_sync_query_report_list', $query, $this );

    $results = $query->get_products();

    $total_items = $query->get( 'paginate' ) ? $results->total : count( $results );

    $products = $query->get( 'paginate' ) ? $results->products : $results;

    $pages = $query->get( 'paginate' ) ? $results->max_num_pages : 1;

    $products_with_children = array();

    foreach ( $products as $key => $product ) {
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

    $this->items = $products_with_children;

    $this->set_pagination_args( array(
      'total_items' => $total_items,
      'per_page' => $per_page,
      'total_pages' => $pages,
    ) );
  }
}
