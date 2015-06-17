jQuery( function( $ ) {
	/* Storage Handling */
	var $supports_html5_storage;
	try {
		$supports_html5_storage = ( 'sessionStorage' in window && window.sessionStorage !== null );
		window.sessionStorage.setItem( 'wc', 'test' );
		window.sessionStorage.removeItem( 'wc' );
	} catch( err ) {
		$supports_html5_storage = false;
	}

	function add_geolocation_to_links() {
		if ( wc_geolocation_params.geolocation ) {
			$( "a[href^='" + wc_geolocation_params.home_url + "'], a[href^='/']").each( function() {
				var $this = $(this);
				var href  = $this.attr('href');

				if ( href.indexOf( 'location=' ) > 0 ) {
					return;
				}

				if ( href.indexOf( '?' ) > 0 ) {
					$this.attr( "href", href + '&location=' + wc_geolocation_params.geolocation );
				} else {
					$this.attr( "href", href + '?location=' + wc_geolocation_params.geolocation );
				}
			});
		}
	}

	if ( $supports_html5_storage && '1' === wc_geolocation_params.is_woocommerce ) {

		// Redirect based on geolocation to get around static caching
		var $geolocate_customer = {
			url: wc_geolocation_params.wc_ajax_url + 'geolocate',
			type: 'GET',
			success: function( response ) {
				if ( response.success && response.data.country ) {
					sessionStorage.setItem( 'wc_geolocated_country', response.data.country );
					sessionStorage.setItem( 'wc_geolocated_state', response.data.state );
					handle_geolocation_redirect( response.data.country, response.data.state );
				}
			}
		};

		function handle_geolocation_redirect( country, state ) {
			// Redirect if not base location
			if ( country !== wc_geolocation_params.base_country || state !== wc_geolocation_params.base_state ) {
				var this_page = window.location.toString();

				if ( state ) {
					state = ':' + state;
				}
				if ( this_page.indexOf( '?' ) > 0 ) {
					this_page = this_page + '&location=' + country + state;
				} else {
					this_page = this_page + '?location=' + country + state;
				}
				window.location = this_page;
			}
		}

		if ( window.location.toString().indexOf( 'location=' ) < 0 ) {
			if ( ! sessionStorage.getItem( 'wc_geolocated_country' ) ) {
				$.ajax( $geolocate_customer );
			} else {
				handle_geolocation_redirect( sessionStorage.getItem( 'wc_geolocated_country' ), sessionStorage.getItem( 'wc_geolocated_state' ) );
			}
		}
	}

	$( document.body ).on( 'added_to_cart', function() {
		add_geolocation_to_links();
	});

	add_geolocation_to_links();
});