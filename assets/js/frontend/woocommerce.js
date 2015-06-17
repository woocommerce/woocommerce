jQuery( function( $ ) {

	// Orderby
	$( '.woocommerce-ordering' ).on( 'change', 'select.orderby', function() {
		$( this ).closest( 'form' ).submit();
	});

	// Target quantity inputs on product pages
	$( 'input.qty:not(.product-quantity input.qty)' ).each( function() {
		var min = parseFloat( $( this ).attr( 'min' ) );

		if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
			$( this ).val( min );
		}
	});

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
		if ( woocommerce_params.geolocation ) {
			$( "a[href^='" + woocommerce_params.home_url + "'], a[href^='/']").each( function() {
				var $this = $(this);
				var href  = $this.attr('href');

				if ( href.indexOf( 'location=' ) > 0 ) {
					return;
				}

				if ( href.indexOf( '?' ) > 0 ) {
					$this.attr( "href", href + '&location=' + woocommerce_params.geolocation );
				} else {
					$this.attr( "href", href + '?location=' + woocommerce_params.geolocation );
				}
			});
		}
	}

	if ( $supports_html5_storage && '1' === woocommerce_params.is_woocommerce ) {

		// Redirect based on geolocation to get around static caching
		var $geolocate_customer = {
			url: woocommerce_params.wc_ajax_url + 'geolocate',
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
			if ( country !== woocommerce_params.base_country || state !== woocommerce_params.base_state ) {
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
