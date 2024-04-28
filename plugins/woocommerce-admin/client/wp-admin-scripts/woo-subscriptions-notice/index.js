( function ( $, woocommerce_admin ) {

	$( document ).on( 'click', '.woo-subscription-expiring-notice,.woo-subscription-expired-notice', function ( e ) {
		const notice_id = this.id;
		if ( !notice_id ) {
			return;
		}
		var data = {
			action: 'dismiss_woo_subscriptions_notice',
			security: woocommerce_admin.nonces.subscriptions_notice,
			notice_id: notice_id,
		};
		$.ajax( {
	        url: woocommerce_admin.ajax_url,
	        data: data,
	        dataType: 'json',
	        type: 'POST',
	        success: function ( response ) {
		        $( notice_id ).remove();
	        },
        } );
	} )
} )( jQuery, woocommerce_admin );
