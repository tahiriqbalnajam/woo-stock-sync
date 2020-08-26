jQuery(document).ready(function($){
  

  $( 'input[type="submit"][name="run_import"]' ).click( function( e ) {
     var favoritecheck = [];
            $.each($("input[name='chk']:checked"), function(){
                favoritecheck.push($(this).val());
                document.cookie = "shipping_clone_count="+favoritecheck;

           });
    if ( ! confirm( 'Are you sure you want to continue? Importing stock quantities will override local quantities for all matching products. Non-matching products will be left alone.' ) ) {
      return false;
    }
  } );

/*$(".woo_stock_sync_checkboxvalue").click(function() { 
                    if ($("input[type=checkbox]").prop( 
                      ":checked")) { 
                        $( this ).val("checked");
                    } else { 
                        $( this ).val("unchecked");
                    } 
                }); */

/*$(".selectsiteuri_unique option").val(function(idx, val) {
  $(this).siblings('[value="'+ val +'"]').hide();
});*/
$('.remoceclass').click(function(){
  $(this).closest('tr').find('.woo-stock-sync-url').val("");
  $(this).closest('tr').find('.woo-stock-sync-api-key').val("");
  $(this).closest('tr').find('.woo-stock-sync-api-secret').val("");
  $(this).closest('tr').find('.woo-stock-sync-vendor').val("");
});
$('.woo_stock_sync_checkboxvalueclass').click(function(){
    if(this.checked){
      $(this).prop('value','active');
    }  
   else{
     $(this).prop('value','inactive');
   }    
});

 $.each($('.woo_stock_sync_checkboxvalueclass'), function(){
  var value = $(this).val();
    if (value=="active") {
      $(this).prop( "checked", true );
    }
    else{
      $( this ).prop( "unchecked", false );
    }
 });

  var wooStockSyncSettings = {
    init: function() {
      this.triggerCredentialCheck();
    },

    triggerCredentialCheck: function() {
      var self = this;

      $( document ).on( 'click', 'a.woo-stock-sync-check-credentials', function(e) {
        e.preventDefault();
        self.checkCredentials( $( this ).closest( 'tr' ) );
      } );
    },

    checkCredentials: function( row ) {
      var self = this;
      var checkButton = $( 'a.woo-stock-sync-check-credentials', row );

      var data = {
        'action': 'woo_stock_sync_check_api_access',
        'url': $( 'input.woo-stock-sync-url', row ).val(),
        'key': $( 'input.woo-stock-sync-api-key', row ).val(),
        'secret': $( 'input.woo-stock-sync-api-secret', row ).val(),
      };

      this.showCheckProcessing( checkButton );

      jQuery.post(ajaxurl, data)
      .done(function(response) {
        responseData = jQuery.parseJSON(response);
        if ( responseData.success ) {
          self.showCheckSuccess( checkButton, woo_stock_sync_settings.check_credentials_success );
        } else {
          self.showCheckFail( checkButton, responseData.error );
        }
      })
      .fail(function(response) {
        alert( 'Error checking API credentials' );
      })
      .always(function(response) {
      });
    },

    showCheckProcessing: function( el ) {
      $( 'span.woo-stock-sync-check-credentials-indicator' ).remove();
      el.after( '<span class="woo-stock-sync-check-credentials-indicator woo-stock-sync-check-credentials-processing"></span>' );
    },

    showCheckSuccess: function( el, successMsg ) {
      $( 'span.woo-stock-sync-check-credentials-indicator' ).remove();
      el.after( '<span class="woo-stock-sync-check-credentials-indicator woo-stock-sync-check-credentials-success">' + successMsg + '</span>' );
    },

    showCheckFail: function( el, errorMsg ) {
      $( 'span.woo-stock-sync-check-credentials-indicator' ).remove();
      el.after( '<span class="woo-stock-sync-check-credentials-indicator woo-stock-sync-check-credentials-fail">' + errorMsg +'</span>' );
    },
  };

  wooStockSyncSettings.init();

});
