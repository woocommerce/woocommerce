/**
 * @jest-environment jest-fixed-jsdom
 */
const $ = require( 'jquery' );

describe( 'Back in Stock form for variable products', () => {
	beforeAll( () => {
		window.jQuery = $;
		require( '../back-in-stock-form' );
	} );

	it( 'shows the "already joined" notice instead of the form for a joined variation', () => {
		document.body.innerHTML = `
			<form class="variations_form" data-product_id="1"></form>
			<div class="wc_bis_form" data-bis-product-id="1" data-bis-signed-up-variations="[7]" hidden></div>
			<div class="wc_bis_already_signed_up" data-bis-product-id="1" hidden></div>`;
		const $variations = $( '.variations_form' ).wc_back_in_stock_form();
		const show = ( id ) =>
			$variations.trigger( 'show_variation', {
				variation_id: id,
				is_in_stock: false,
				variation_is_active: true,
				variation_is_visible: true,
			} );
		const hidden = ( selector ) => $( selector ).prop( 'hidden' );

		show( 7 );
		expect( [ hidden( '.wc_bis_form' ), hidden( '.wc_bis_already_signed_up' ) ] ).toEqual( [ true, false ] );

		show( 8 );
		expect( [ hidden( '.wc_bis_form' ), hidden( '.wc_bis_already_signed_up' ) ] ).toEqual( [ false, true ] );

		show( 7 );
		$variations.trigger( 'reset_data' );
		expect( [ hidden( '.wc_bis_form' ), hidden( '.wc_bis_already_signed_up' ) ] ).toEqual( [ true, true ] );
	} );
} );
