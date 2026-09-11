/* global wc_orders_params */
jQuery( function( $ ) {

	if ( typeof wc_orders_params === 'undefined' ) {
		return false;
	}

	/**
	 * WCOrdersTable class.
	 */
	var WCOrdersTable = function() {

		const SELECTORS = [
			// WordPress 7.1 renders primary order cells as th instead of td.
			".post-type-shop_order .wp-list-table tbody td:not(.check-column)",
			".post-type-shop_order .wp-list-table tbody th:not(.check-column)",
			".woocommerce_page_wc-orders .wp-list-table.orders tbody td:not(.check-column)",
			".woocommerce_page_wc-orders .wp-list-table.orders tbody th:not(.check-column)"
		]

		$( document )
			.on(
				'click',
				SELECTORS.join( ', ' ),
				this.onRowClick
			)
			.on( 'click', '.order-preview:not(.disabled)', this.onPreview );
	};

	/**
	 * Click a row.
	 */
	WCOrdersTable.prototype.onRowClick = function( e ) {
		if ( $( e.target ).filter( 'a, a *, .no-link, .no-link *, button, button *' ).length ) {
			return true;
		}

		if ( window.getSelection && window.getSelection().toString().length ) {
			return true;
		}

		var $row = $( this ).closest( 'tr' ),
			href = $row.find( 'a.order-view' ).attr( 'href' );

		if ( href && href.length ) {
			e.preventDefault();

			if ( e.metaKey || e.ctrlKey ) {
				window.open( href, '_blank' );
			} else {
				window.location = href;
			}
		}
	};

	/**
	 * Preview an order.
	 */
	WCOrdersTable.prototype.onPreview = function() {
		var $previewButton    = $( this ),
			$order_id         = $previewButton.data( 'orderId' );

		if ( $previewButton.data( 'order-data' ) ) {
			$( this ).WCBackboneModal({
				template: 'wc-modal-view-order',
				variable : $previewButton.data( 'orderData' )
			});
		} else {
			$previewButton.addClass( 'disabled' );

			$.ajax({
				url:     wc_orders_params.ajax_url,
				data:    {
					order_id: $order_id,
					action  : 'woocommerce_get_order_details',
					security: wc_orders_params.preview_nonce
				},
				type:    'GET',
				success: function( response ) {
					$( '.order-preview' ).removeClass( 'disabled' );

					if ( response.success ) {
						$previewButton.data( 'orderData', response.data );

						$( this ).WCBackboneModal({
							template: 'wc-modal-view-order',
							variable : response.data
						});
					}
				}
			});
		}
		return false;

	};

	/**
	 * Init WCOrdersTable.
	 */
	new WCOrdersTable();
} );
