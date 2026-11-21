describe( 'Back in stock form', () => {
	let $;

	const outOfStock = {
		variation_id: 11,
		is_in_stock: false,
		is_purchasable: true,
		variation_is_active: true,
		variation_is_visible: true,
	};

	const form = ( id, productId = 10 ) => `
		<form id="${ id }" class="variations_form cart" data-product_id="${ productId }">
			<table class="variations"><tbody><tr><td>
				<select name="attribute_pa_color"><option value="${ id }" selected></option></select>
			</td></tr></tbody></table>
		</form>
	`;

	const wrap = ( html ) => `<div class="wrap">${ html }</div>`;

	const prompt = `
		<div class="wc_bis_form" data-bis-product-id="10" hidden>
			<form method="post"><input type="hidden" name="wc_bis_product_id" value="10" /></form>
		</div>
	`;

	// Load the script against fresh markup. jQuery runs document-ready callbacks on a timer.
	const load = ( html ) => {
		document.body.innerHTML = html;
		jest.useFakeTimers();
		jest.isolateModules( () => {
			$ = global.jQuery = require( 'jquery' );
			require( '../back-in-stock-form' );
		} );
		jest.runAllTimers();
		jest.useRealTimers();
	};

	afterEach( () => {
		document.body.innerHTML = '';
		delete global.jQuery;
	} );

	const pickOutOfStock = ( id ) =>
		$( `#${ id }` )
			.trigger( 'found_variation', [ outOfStock ] )
			.trigger( 'show_variation', [ outOfStock ] );

	// [ layout, form that drives the prompt, another form that must not ].
	test.each( [
		[ 'one form', form( 'a' ) + prompt, 'a', null ],
		[ 'one wrapped form', wrap( form( 'a' ) ) + prompt, 'a', null ],
		[ 'a second form after the prompt', form( 'a' ) + prompt + form( 'b' ), 'a', 'b' ],
		[ 'a second form before the prompt', wrap( form( 'a' ) ) + form( 'b' ) + prompt, 'b', 'a' ],
		[ 'two wrapped forms', wrap( form( 'a' ) ) + wrap( form( 'b' ) ) + prompt, 'b', 'a' ],
		[ "another product's form in between", form( 'a' ) + form( 'c', 20 ) + prompt, 'a', 'c' ],
	] )( 'with %s, only the closest form before the prompt drives it', ( _, html, owner, other ) => {
		load( html );
		const $prompt = $( '.wc_bis_form' );
		const $promptForm = $prompt.find( 'form' ).on( 'submit', ( event ) => event.preventDefault() );

		if ( other ) {
			pickOutOfStock( other );
			expect( $prompt.prop( 'hidden' ) ).toBe( true );
			expect( $prompt.find( '[name="wc_bis_product_id"]' ).val() ).toBe( '10' );
		}

		pickOutOfStock( owner );
		expect( $prompt.prop( 'hidden' ) ).toBe( false );
		expect( $prompt.find( '[name="wc_bis_product_id"]' ).val() ).toBe( '11' );

		$promptForm.trigger( 'submit' );
		expect( $promptForm.find( '[name="attribute_pa_color"]' ).val() ).toBe( owner );

		$( `#${ owner }` ).trigger( 'reset_data' );
		expect( $prompt.prop( 'hidden' ) ).toBe( true );
	} );
} );
