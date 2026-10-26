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

describe( 'Product Bulk Edit taxonomies', () => {
	let $;

	beforeAll( () => {
		jest.useFakeTimers();
		jest.resetModules();
		$ = require( 'jquery' );
		global.jQuery = $;
		global.woocommerce_quick_edit = { strings: { some_products_have_term: 'Some selected products have this term' } };

		const taxonomies = [ 'product_cat', 'product_brand', 'wc_test_collection' ];
		document.body.innerHTML = `
			<div id="wpbody">
				<button type="button" id="doaction">Apply</button>
				<table><tbody id="the-list">
					<tr><td class="check-column"><input type="checkbox" name="post[]" value="1" checked></td></tr>
					<tr><td class="check-column"><input type="checkbox" name="post[]" value="2" checked></td></tr>
					<tr id="bulk-edit" class="inline-editor"><td>
						${ taxonomies.map( taxonomy => `
							<ul class="cat-checklist ${ taxonomy }-checklist">
								<li><label><input type="checkbox" name="tax_input[${ taxonomy }][]" value="10">Shared</label></li>
								<li><label><input type="checkbox" name="tax_input[${ taxonomy }][]" value="11">Mixed</label></li>
							</ul>
						` ).join( '' ) }
					</td></tr>
				</tbody></table>
				<div id="inline_1">
					${ taxonomies.map( taxonomy => `<div class="post_category" id="${ taxonomy }_1">10,11</div>` ).join( '' ) }
				</div>
				<div id="inline_2">
					${ taxonomies.map( taxonomy => `<div class="post_category" id="${ taxonomy }_2">10</div>` ).join( '' ) }
				</div>
			</div>
		`;
		require( '../quick-edit' );
		jest.runAllTimers();
		jest.useRealTimers();
		$( '#doaction' ).trigger( 'click' );
	} );

	afterAll( () => {
		document.body.innerHTML = '';
		delete global.jQuery;
		delete global.woocommerce_quick_edit;
	} );

	test.each( [ 'product_cat', 'product_brand' ] )( 'initializes shared and mixed terms for %s', taxonomy => {
		const list = document.querySelector( 'ul.' + taxonomy + '-checklist' );
		expect( list.querySelector( 'input[value="10"]' ).checked ).toBe( true );
		expect( list.querySelector( 'input[value="11"]' ).indeterminate ).toBe( true );
		expect( list.querySelector( 'input[name="wc_bulk_edit_taxonomies[' + taxonomy + ']"]' ).value ).toBe( '10,11' );
		expect( list.querySelector( 'input[name="wc_bulk_edit_indeterminate[' + taxonomy + '][]"]' ).value ).toBe( '11' );
	} );

	test( 'leaves custom taxonomy checklists unchanged', () => {
		const list = document.querySelector( 'ul.wc_test_collection-checklist' );
		expect( list.querySelector( 'input[value="10"]' ).checked ).toBe( false );
		expect( list.querySelector( 'input[value="11"]' ).indeterminate ).toBe( false );
		expect( list.querySelector( 'input[type="hidden"]' ) ).toBeNull();
	} );
} );
