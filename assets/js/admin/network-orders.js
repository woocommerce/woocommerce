(function( $, _, undefined ) {

	if ( 'undefined' === typeof woocommerce_network_orders ) {
		return;
	}

	var orders = [],
		promises = [], // Track completion (pass or fail) of ajax requests
		deferred = [], // Tracks the ajax deferreds
		$tbody = $( document.getElementById( 'network-orders-tbody' ) ),
		template = _.template( $( document.getElementById( 'network-orders-row-template') ).text() );

	// No sites, so bail
	if ( ! woocommerce_network_orders.sites.length ) {
		return
	}

	$.each( woocommerce_network_orders.sites, function( index, value ) {
		promises[ index ] = $.Deferred();
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

			promises[ index ].resolve();
		}).fail(function (){
			promises[ index ].resolve();
		}) );
	} );

	if ( promises.length > 0 ) {
		$.when.apply( $, promises ).done( function() {
			var orderindex,
				currentOrder;

			for ( orderindex in orders ) {
				currentOrder = orders[ orderindex ];

				$tbody.append( template( currentOrder ) );
			}

		} );
	}

})( jQuery, _ );
