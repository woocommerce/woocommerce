jQuery( function( $ ) {

	var $geolocate_customer = {
		url: wc_geolocation_params.wc_ajax_url + 'geolocate',
		type: 'GET',
		success: function( response ) {
			if ( response.success && response.data.country ) {
				handle_geolocation_redirect( response.data.country, response.data.state );
			}
		}
	};

	function handle_geolocation_redirect( country, state ) {
		var this_page = window.location.toString();

		if ( this_page.indexOf( '?' ) > 0 ) {
			this_page = this_page + '&location=' + country;
		} else {
			this_page = this_page + '?location=' + country;
		}
		if ( state ) {
			this_page = this_page + ':' + state;
		}

		window.location = this_page;
	}

	function add_geolocation_to_links() {
		if ( wc_geolocation_params.geolocation ) {
			$( "a[href^='" + wc_geolocation_params.home_url + "']:not(a[href*='location=']), a[href^='/']:not(a[href*='location='])").each( function() {
				var $this = $(this);
				var href  = $this.attr('href');

				if ( href.indexOf( '?' ) > 0 ) {
					$this.attr( "href", href + '&location=' + wc_geolocation_params.geolocation );
				} else {
					$this.attr( "href", href + '?location=' + wc_geolocation_params.geolocation );
				}
			});
		}
	}

	if ( '1' === wc_geolocation_params.is_woocommerce && 0 > window.location.toString().indexOf( 'location=' ) ) {
		if ( wc_geolocation_params.geolocation ) {
			handle_geolocation_redirect( wc_geolocation_params.geolocation );
		} else {
			$.ajax( $geolocate_customer );
		}
	}

	$( document.body ).on( 'added_to_cart', function() {
		add_geolocation_to_links();
	});

	add_geolocation_to_links();
});