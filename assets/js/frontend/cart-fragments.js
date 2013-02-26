jQuery(document).ready(function($) {

	/** Cart Handling */
	$supports_html5_storage = ( 'sessionStorage' in window && window['sessionStorage'] !== null );

	$fragment_refresh = {
		url: woocommerce_params.ajax_url,
		type: 'POST',
		data: { action: 'woocommerce_get_refreshed_fragments' },
		success: function( data ) {
			if ( data && data.fragments ) {

				$.each( data.fragments, function( key, value ) {
					$(key).replaceWith(value);
				});

				if ( $supports_html5_storage ) {
					sessionStorage.setItem( "wc_fragments", JSON.stringify( data.fragments ) );
					sessionStorage.setItem( "wc_cart_hash", data.cart_hash );
				}

			}
		}
	};

	if ( $supports_html5_storage ) {

		$('body').bind( 'added_to_cart', function( event, fragments, cart_hash ) {
			sessionStorage.setItem( "wc_fragments", JSON.stringify( fragments ) );
			sessionStorage.setItem( "wc_cart_hash", cart_hash );
		});

		try {
			var wc_fragments = $.parseJSON( sessionStorage.getItem( "wc_fragments" ) );
			var cart_hash    = sessionStorage.getItem( "wc_cart_hash" );

			if ( wc_fragments && wc_fragments['div.widget_shopping_cart_content'] && cart_hash == $.cookie( "woocommerce_cart_hash" ) ) {

				$.each( wc_fragments, function( key, value ) {
					$(key).replaceWith(value);
				});

			} else {
				throw "No fragment";
			}

		} catch(err) {
			$.ajax( $fragment_refresh );
		}

	} else {
		$.ajax( $fragment_refresh );
	}

});