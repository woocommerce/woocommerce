(function( $, _, undefined ) {

	if ( 'undefined' === typeof woocommerce_network_orders ) {
		return;
	}

	var orders = [],
		deferred = [],
		$tbody = $( document.getElementById( 'network-orders-tbody' ) ),
		template = _.template( $( document.getElementById( 'network-orders-row-template') ).text() );

	// No sites, so bail
	if ( ! woocommerce_network_orders.sites.length ) {
		return
	}

	$.each( woocommerce_network_orders.sites, function( index, value ) {
		deferred.push( $.ajax( {
			url : woocommerce_network_orders.order_endpoint,
			data: {
				_wpnonce: woocommerce_network_orders.nonce,
				network_orders: true,
				blog_id: value
			},
			type: 'GET'
		} ).success(function( response ) {
			var orderindex;

			for ( orderindex in response ) {
				orders.push( response[ orderindex ] );
			}
		}) );
	} );

	if ( deferred ) {
		$.when.apply( $, deferred ).done( function() {
			var orderindex,
				currentOrder;

			for ( orderindex in orders ) {
				currentOrder = orders[ orderindex ];

				window.currentOrder = currentOrder

				$tbody.append( template( currentOrder ) );
			}

		} );
	} else {
			// @todo no sites
	}

})( jQuery, _ );
