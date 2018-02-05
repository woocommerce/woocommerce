/*global wc_geolocation_params */
jQuery( function( $ ) {

	var this_page = window.location.toString();

	var $append_hashes = function() {
		if ( wc_geolocation_params.hash ) {
			$( 'a[href^="' + wc_geolocation_params.home_url + '"]:not(a[href*="v="]), a[href^="/"]:not(a[href*="v="])' ).each( function() {
				var $this      = $( this ),
					href       = $this.attr( 'href' ),
					href_parts = href.split( '#' );

				href = href_parts[0];

				if ( href.indexOf( '?' ) > 0 ) {
					href = href + '&v=' + wc_geolocation_params.hash;
				} else {
					href = href + '?v=' + wc_geolocation_params.hash;
				}

				if ( typeof href_parts[1] !== 'undefined' && href_parts[1] !== null ) {
					href = href + '#' + href_parts[1];
				}

				$this.attr( 'href', href );
			});
		}
	};

	var $geolocation_redirect = function( hash ) {
		if ( this_page.indexOf( '?v=' ) > 0 || this_page.indexOf( '&v=' ) > 0 ) {
			this_page = this_page.replace( /v=[^&]+/, 'v=' + hash );
		} else if ( this_page.indexOf( '?' ) > 0 ) {
			this_page = this_page + '&v=' + hash;
		} else {
			this_page = this_page + '?v=' + hash;
		}

		window.location = this_page;
	};

	var $geolocate_customer = {
		url: wc_geolocation_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_customer_location' ),
		type: 'GET',
		success: function( response ) {
			if ( response.success && response.data.hash && response.data.hash !== wc_geolocation_params.hash ) {
				$geolocation_redirect( response.data.hash );
			}
		}
	};

	if ( '1' === wc_geolocation_params.is_available ) {
		$.ajax( $geolocate_customer );

		// Support form elements
		$( 'form' ).each( function() {
			var $this  = $( this );
			var method = $this.attr( 'method' );

			if ( method && 'get' === method.toLowerCase() ) {
				$this.append( '<input type="hidden" name="v" value="' + wc_geolocation_params.hash + '" />' );
			} else {
				var href = $this.attr( 'action' );
				if ( href ) {
					if ( href.indexOf( '?' ) > 0 ) {
						$this.attr( 'action', href + '&v=' + wc_geolocation_params.hash );
					} else {
						$this.attr( 'action', href + '?v=' + wc_geolocation_params.hash );
					}
				}
			}
		});

		// Append hashes on load
		$append_hashes();
	}

	$( document.body ).on( 'added_to_cart', function() {
		$append_hashes();
	});

});
