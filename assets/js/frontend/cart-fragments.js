/* global wc_cart_fragments_params */
jQuery( function( $ ) {

	// wc_cart_fragments_params is required to continue, ensure the object exists
	if ( typeof wc_cart_fragments_params === 'undefined' ) {
		return false;
	}

	/* Storage Handling */
	var $supports_html5_storage;
	try {
		$supports_html5_storage = ( 'sessionStorage' in window && window.sessionStorage !== null );

		window.sessionStorage.setItem( 'wc', 'test' );
		window.sessionStorage.removeItem( 'wc' );
	} catch( err ) {
		$supports_html5_storage = false;
	}

	var $fragment_refresh = {
		url: wc_cart_fragments_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_refreshed_fragments' ),
		type: 'POST',
		success: function( data ) {
			if ( data && data.fragments ) {

				$.each( data.fragments, function( key, value ) {
					$( key ).replaceWith( value );
				});

				if ( $supports_html5_storage ) {
					sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify( data.fragments ) );
					sessionStorage.setItem( 'wc_cart_hash', data.cart_hash );
				}

				$( document.body ).trigger( 'wc_fragments_refreshed' );
			}
		}
	};

	/* Cart Handling */
	if ( $supports_html5_storage ) {

		$( document.body ).bind( 'added_to_cart', function( event, fragments, cart_hash ) {
			sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify( fragments ) );
			sessionStorage.setItem( 'wc_cart_hash', cart_hash );
		});

		try {
			var wc_fragments = $.parseJSON( sessionStorage.getItem( wc_cart_fragments_params.fragment_name ) ),
				cart_hash    = sessionStorage.getItem( 'wc_cart_hash' ),
				cookie_hash  = $.cookie( 'woocommerce_cart_hash' );

			if ( cart_hash === null || cart_hash === undefined || cart_hash === '' ) {
				cart_hash = '';
			}

			if ( cookie_hash === null || cookie_hash === undefined || cookie_hash === '' ) {
				cookie_hash = '';
			}

			if ( wc_fragments && wc_fragments['div.widget_shopping_cart_content'] && cart_hash === cookie_hash ) {

				$.each( wc_fragments, function( key, value ) {
					$( key ).replaceWith(value);
				});

				$( document.body ).trigger( 'wc_fragments_loaded' );
			} else {
				throw 'No fragment';
			}

		} catch( err ) {
			$.ajax( $fragment_refresh );
		}

	} else {
		$.ajax( $fragment_refresh );
	}

	/* Cart Hiding */
	if ( $.cookie( 'woocommerce_items_in_cart' ) > 0 ) {
		$( '.hide_cart_widget_if_empty' ).closest( '.widget_shopping_cart' ).show();
	} else {
		$( '.hide_cart_widget_if_empty' ).closest( '.widget_shopping_cart' ).hide();
	}

	$( document.body ).bind( 'adding_to_cart', function() {
		$( '.hide_cart_widget_if_empty' ).closest( '.widget_shopping_cart' ).show();
	});
});
