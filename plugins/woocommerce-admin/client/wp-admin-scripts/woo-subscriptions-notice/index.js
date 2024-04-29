( function ( $ ) {
	$( document ).on( 'click', '.woo-subscription-expiring-notice,.woo-subscription-expired-notice', function ( e ) {
		const notice_id = this.id;
		if ( !notice_id ) {
			return;
		}

		var data = {
			notice_id: notice_id,
		};

		window.wp.apiFetch( {
	          path: `/wc-admin/woo_subscription_notice_dissmiss/`,
	          method: 'POST',
	          data,
	      } )
	} )
} )( jQuery );
