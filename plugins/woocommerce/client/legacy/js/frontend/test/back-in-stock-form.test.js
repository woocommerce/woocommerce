describe( 'Back in stock form', () => {
	let $;

	const outOfStock = {
		variation_id: 11,
		is_in_stock: false,
		is_purchasable: true,
		variation_is_active: true,
		variation_is_visible: true,
	};

	const variationsForm = ( id, color ) => `
		<form id="${ id }" class="variations_form cart" data-product_id="10">
			<table class="variations"><tbody><tr><td>
				<select name="attribute_pa_color">
					<option value="${ color }" selected>${ color }</option>
				</select>
			</td></tr></tbody></table>
		</form>
	`;

	const prompt = `
		<div class="wc_bis_form" data-bis-product-id="10" hidden>
			<form method="post">
				<input type="hidden" name="wc_bis_product_id" value="10" />
			</form>
		</div>
	`;

	// Load the script against fresh markup, so each test starts from a clean state.
	const load = ( html ) => {
		document.body.innerHTML = html;

		// jQuery runs document-ready callbacks on a timer once the document is loaded.
		jest.useFakeTimers();
		jest.isolateModules( () => {
			$ = require( 'jquery' );
			global.jQuery = $;
			require( '../back-in-stock-form' );
		} );
		jest.runAllTimers();
		jest.useRealTimers();
	};

	afterEach( () => {
		document.body.innerHTML = '';
		delete global.jQuery;
	} );

	const $prompt = () => $( '.wc_bis_form' );
	const $productId = () => $prompt().find( '[name="wc_bis_product_id"]' );
	const pickOutOfStock = ( $form ) =>
		$form
			.trigger( 'found_variation', [ outOfStock ] )
			.trigger( 'show_variation', [ outOfStock ] );

	describe( 'with a single variations form', () => {
		test( 'shows the prompt for an out of stock variation and hides it on reset', () => {
			load( `<div class="summary">${ variationsForm( 'a', 'red' ) }${ prompt }</div>` );

			pickOutOfStock( $( '#a' ) );
			expect( $prompt().prop( 'hidden' ) ).toBe( false );
			expect( $productId().val() ).toBe( '11' );

			$( '#a' ).trigger( 'reset_data' );
			expect( $prompt().prop( 'hidden' ) ).toBe( true );
		} );

		test( 'finds the prompt when the theme wraps the form', () => {
			load(
				`<div class="summary"><div class="wrap">${ variationsForm( 'a', 'red' ) }</div>${ prompt }</div>`
			);

			pickOutOfStock( $( '#a' ) );
			expect( $prompt().prop( 'hidden' ) ).toBe( false );
		} );
	} );

	describe( 'with two variations forms for the same product', () => {
		beforeEach( () => {
			load(
				`<div class="summary">${ variationsForm( 'a', 'red' ) }${ prompt }${ variationsForm( 'b', 'blue' ) }</div>`
			);
		} );

		test( 'ignores variations picked in the form without its own prompt', () => {
			pickOutOfStock( $( '#b' ) );

			expect( $prompt().prop( 'hidden' ) ).toBe( true );
			expect( $productId().val() ).toBe( '10' );
		} );

		test( 'follows the form the prompt renders after', () => {
			pickOutOfStock( $( '#a' ) );
			$( '#b' ).trigger( 'reset_data' );

			expect( $prompt().prop( 'hidden' ) ).toBe( false );
			expect( $productId().val() ).toBe( '11' );
		} );

		test( 'leaves the prompt to the form it follows when the first form is wrapped', () => {
			const wrappedA = `<div class="wrap">${ variationsForm( 'a', 'red' ) }</div>`;
			load(
				`<div class="summary">${ wrappedA }${ variationsForm( 'b', 'blue' ) }${ prompt }</div>`
			);

			pickOutOfStock( $( '#a' ) );
			expect( $prompt().prop( 'hidden' ) ).toBe( true );

			pickOutOfStock( $( '#b' ) );
			expect( $prompt().prop( 'hidden' ) ).toBe( false );
		} );

		test( 'posts the attributes of the form the prompt renders after', () => {
			const $form = $prompt().find( 'form' );
			$form.on( 'submit', ( event ) => event.preventDefault() );
			$form.trigger( 'submit' );

			expect( $form.find( '[name="attribute_pa_color"]' ).val() ).toBe(
				'red'
			);
		} );
	} );
} );
