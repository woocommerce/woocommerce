const $ = require( 'jquery' );

describe( 'Geolocation on storefront forms', () => {
	afterEach( () => {
		document.body.replaceChildren();
		window.history.replaceState( null, '', '/' );
		delete window.wc_geolocation_params;
		delete window.Cookies;
		delete window.jQuery;
	} );

	it( 'submits the location hash exactly as supplied', async () => {
		const hash = 'region&amp;zone';
		window.history.replaceState( null, '', '?' + new URLSearchParams( { v: hash } ) );
		window.wc_geolocation_params = { home_url: window.location.origin, wc_ajax_url: '/' };
		window.Cookies = { get: () => hash };
		window.jQuery = $;
		document.body.innerHTML = '<form method="get"></form>';

		require( '../geolocation' );
		await new Promise( ( resolve ) => $( () => resolve() ) );

		const form = document.forms[ 0 ];
		expect( form.elements.namedItem( 'v' ).type ).toBe( 'hidden' );
		expect( $( form ).serializeArray() ).toEqual( [ { name: 'v', value: hash } ] );
	} );
} );
