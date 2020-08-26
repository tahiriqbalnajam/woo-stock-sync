<?php global $title; ?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo $title; ?></h1>
  <hr class="wp-header-end">

  <?php if ( $limit_reached && ! class_exists( 'Woo_Stock_Sync_Pro' ) ) { ?>
    <div class="error woo-stock-sync-limit-reached-error">
      <p><?php printf( __( 'Stock Sync for WooCommerce supports only 100 products ', 'woo-stock-sync' ), $product_count ); ?></p>
    </div>
  <?php } ?>

  <form id="woo-stock-sync-actions" action="<?php echo admin_url( 'admin-ajax.php?action=woo_stock_sync_run_action' ); ?>" method="post">
    <select name="woo_stock_sync_action_site">
      <option value=""><?php _e( '- Select site -', 'woo-stock-sync' ); ?></option>
      <?php 
      $listitems=array();
      foreach (woo_stock_sync_sites() as $value) {
        $listitems[]=$value['url'].'-'.$value['api_checkboxenale'];
      }
       $listitems = array_unique($listitems);
      foreach ( $listitems as $site ) { 
        $data=(explode('-', $site));

        if ( $data[1]=="active") {
          ?> 
          <option value="<?php echo $data[0]; ?>"><?php echo $data[0]; ?></option>
      <?php }  }?>
    </select>
    <input type="submit" name="run_report" class="button" value="<?php _e( 'Update sync status', 'woo-stock-sync' ); ?>" />
    <input type="submit" name="run_import" class="button" value="<?php _e( 'Import stock quantities', 'woo-stock-sync' ); ?>" />
  </form>

  <form id="woo-stock-sync-filter" method="get">
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />

    <?php $table->display(); 
    ?>

  </form>
</div>
