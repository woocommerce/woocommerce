describe( 'Product Quick Edit measurements', () => {
	let $;

	beforeAll( () => {
		// jQuery binds document-ready callbacks on a timer once the document is loaded.
		jest.useFakeTimers();
		jest.resetModules();

		$ = require( 'jquery' );
		global.jQuery = $;
		global.inlineEditPost = { revert: jest.fn() };
		global.woocommerce_admin = { decimal_point: '.' };
		global.woocommerce_quick_edit = { strings: { allow_reviews: '' } };

		document.body.innerHTML = `
			<table>
				<tbody id="the-list">
					<tr id="post-1">
						<td><button type="button" class="editinline"></button></td>
					</tr>
				</tbody>
			</table>
			<div id="woocommerce_inline_1">
				<div class="weight">1.25</div>
				<div class="length">2.50</div>
				<div class="width">3.75</div>
				<div class="height">4.00</div>
			</div>
			<div class="inline-edit-row">
				<input name="_weight">
				<input name="_length">
				<input name="_width">
				<input name="_height">
			</div>
		`;

		require( '../quick-edit' );
		jest.runAllTimers();
		jest.useRealTimers();
	} );

	afterAll( () => {
		document.body.innerHTML = '';
		delete global.jQuery;
		delete global.inlineEditPost;
		delete global.woocommerce_admin;
		delete global.woocommerce_quick_edit;
	} );

	test.each( [
		[ ',', '1,25', '2,50', '3,75', '4,00' ],
		[ '.', '1.25', '2.50', '3.75', '4.00' ],
	] )(
		'populates measurements when the decimal separator is %s',
		( decimalPoint, weight, length, width, height ) => {
			global.woocommerce_admin.decimal_point = decimalPoint;
			$( '.editinline' ).trigger( 'click' );

			expect( document.querySelector( 'input[name="_weight"]' ).value ).toBe( weight );
			expect( document.querySelector( 'input[name="_length"]' ).value ).toBe( length );
			expect( document.querySelector( 'input[name="_width"]' ).value ).toBe( width );
			expect( document.querySelector( 'input[name="_height"]' ).value ).toBe( height );
		}
	);
} );
