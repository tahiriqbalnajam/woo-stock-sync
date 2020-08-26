<tr valign="top">
  <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>" colspan="2">
    <table class="form-table wp-list-table widefat fixed">
      <thead>
        <tr>

          <th class="min"><?php _e( '#', 'woo-stock-sync' ); ?></th>
          <th><?php _e( 'URL', 'woo-stock-sync' ); ?></th>
          <th><?php _e( 'API Key', 'woo-stock-sync' ); ?></th>
          <th><?php _e( 'API Secret', 'woo-stock-sync' ); ?></th>
          <th><?php _e( 'Check', 'woo-stock-sync' ); ?></th>
          <th><?php _e( 'Enter Vendor', 'woo-stock-sync' ); ?></th>
          <th class="enabledisable"><?php _e( 'Enable/Disable', 'woo-stock-sync' ); ?></th>
          <th class="justremove"><?php _e( '', 'woo-stock-sync' ); ?></th>
        </tr>
      </thead>
      <tbody>

        <?php for ( $i = 0; $i < apply_filters( 'woo_stock_sync_supported_api_credentials', get_option( 'woo_stock_sync_syncable_noofrows') ); $i++ ) { ?>
          <tr>
            <td class="min">
              <?php echo $i + 1; ?>
            </td>
            <td>
              <input
                type="text"
                class="woo-stock-sync-url"
                name="<?php echo woo_stock_sync_api_credentials_field_name( 'woo_stock_sync_url', $i ); ?>"
                value="<?php echo woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_url', $i ); ?>"
              />
            </td>
            <td>
              <input
                type="text"
                class="woo-stock-sync-api-key"
                name="<?php echo woo_stock_sync_api_credentials_field_name( 'woo_stock_sync_api_key', $i ); ?>"
                value="<?php echo woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_api_key', $i ); ?>"
              />
            </td>
            <td>
              <input
                type="text"
                class="woo-stock-sync-api-secret"
                name="<?php echo woo_stock_sync_api_credentials_field_name( 'woo_stock_sync_api_secret', $i ); ?>"
                value="<?php echo woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_api_secret', $i ); ?>"
              />
            </td>
            <td>
              <a href="#" class="woo-stock-sync-check-credentials"><?php _e( 'Check credentials', 'woo-stock-sync' ); ?></a>
            </td>
            <td>
              <input
                type="text"
                class="woo-stock-sync-vendor"
                name="<?php echo woo_stock_sync_api_credentials_field_name( 'woo_stock_sync_vendor', $i ); ?>"
                value="<?php echo woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_vendor', $i ); ?>"
              />
            </td>
            <td class="min classcheckbox enabledisable">
              <select id="cars" name="<?php echo woo_stock_sync_api_credentials_field_name( 'woo_stock_sync_checkboxvalue', $i ); ?>">
                <option value="active"
                <?php echo (woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_checkboxvalue', $i ) == "active") ? "selected": ""; ?>>Active</option>
                <option 
                value="inactive"
                <?php echo (woo_stock_sync_api_credentials_field_value( 'woo_stock_sync_checkboxvalue', $i ) == "inactive") ? "selected": ""; ?>
                >Inactive</option>
              </select>
            </td>
            <td class="classremove">
              
              <p class="remoceclass">&#10060;</p>
            </td>
          </tr>

        <?php } ?>
      </tbody>
    </table>
  </td>
</tr>






