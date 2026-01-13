/* global wc_admin_customer_stock_notifications_params, woocommerce_admin_meta_boxes */
;( function( $, window ) {

	/**
	 * Document ready.
	 */
	$( function() {

		// Delete confirmations.
		$( '.woocommerce-customer-stock-notification #delete-action' ).on( 'click', function( e ) {
			if ( ! window.confirm( wc_admin_customer_stock_notifications_params.i18n_wc_delete_notification_warning ) ) {
				e.preventDefault();
				return false;
			}
		} );

		$( '#customer-stock-notifications-table #doaction' ).on( 'click', function( e ) {

			var value = $( '#bulk-action-selector-top' ).val();

			if ( value === 'delete'
				&& ! window.confirm( wc_admin_customer_stock_notifications_params.i18n_wc_bulk_delete_notifications_warning ) ) {
				e.preventDefault();
				return false;
			}
		} );

		$( '#customer-stock-notifications-table .column-id .row-actions .delete a' ).on( 'click', function( e ) {
			if ( ! window.confirm( wc_admin_customer_stock_notifications_params.i18n_wc_delete_notification_warning ) ) {
				e.preventDefault();
				return false;
			}
		} );

		$( '.postbox li #delete-action .submitdelete' ).on( 'click', function( e ) {
			if ( ! window.confirm( wc_admin_customer_stock_notifications_params.i18n_wc_delete_notification_warning ) ) {
				e.preventDefault();
				return false;
			}
		} );

		$( 'input#woocommerce_customer_stock_notifications_require_double_opt_in' )
			.on( 'change', function () {
				if ( $( this ).is( ':checked' ) ) {
					$( this ).closest( 'tr' ).next( 'tr' ).show();
				} else {
					$( this ).closest( 'tr' ).next( 'tr' ).hide();
				}
			} )
			.trigger( 'change' );
	} );
} )( jQuery, window );
