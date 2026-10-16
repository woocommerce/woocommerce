/**
 * @jest-environment jest-fixed-jsdom
 */

const $ = require( 'jquery' );
const accounting = require( '../../accounting/accounting' );

describe( 'Order item quantity changes', () => {
	beforeAll( () => {
		document.body.innerHTML = '<div id="woocommerce-order-items"></div>';
		global.jQuery = $;
		global.accounting = accounting;
		global.woocommerce_admin = {};
		global.woocommerce_admin_meta_boxes = {
			rounding_precision: 6,
		};
		$.fn.selectWoo = jest.fn().mockReturnThis();
		$.fn.stupidtable = jest.fn().mockReturnThis();
		jest.spyOn( $.fn, 'ready' ).mockImplementation( function ( callback ) {
			callback( $ );
			return this;
		} );
		require( '../meta-boxes-order' );
	} );

	afterAll( () => {
		jest.restoreAllMocks();
		document.body.replaceChildren();
		delete $.fn.selectWoo;
		delete $.fn.stupidtable;
		delete global.jQuery;
		delete global.accounting;
		delete global.woocommerce_admin;
		delete global.woocommerce_admin_meta_boxes;
	} );

	test.each( [
		[ '.', 2, '17.6', '52.80', '11.10' ],
		[ ',', 2, '17,6', '52,80', '11,10' ],
		[ '.', 2, '17.6033', '52.8099', '11.10' ],
		[ '.', 0, '17.6', '52.8', '11.1' ],
		[ '.', 3, '17.6', '52.800', '11.100' ],
	] )(
		'keeps currency padding and stored precision (%s, %i, %s)',
		( separator, decimals, amount, expectedAmount, expectedTax ) => {
			global.woocommerce_admin.mon_decimal_point = separator;
			global.woocommerce_admin_meta_boxes.currency_format_num_decimals =
				decimals;
			const tax = '3.7'.replace( '.', separator );
			$( '#woocommerce-order-items' ).html( `
				<table><tbody><tr class="item">
					<td><input class="quantity" value="1" data-qty="1"></td>
					<td><input class="line_total" data-total="${ amount }"></td>
					<td><input class="line_subtotal" data-subtotal="${ amount }"></td>
					<td><input class="line_tax" data-tax_id="1" data-total_tax="${ tax }"></td>
					<td><input class="line_subtotal_tax" data-tax_id="1" data-subtotal_tax="${ tax }"></td>
				</tr></tbody></table>
			` );

			$( 'input.quantity' ).val( '3' ).trigger( 'change' );

			expect( $( 'input.line_total' ).val() ).toBe( expectedAmount );
			expect( $( 'input.line_subtotal' ).val() ).toBe( expectedAmount );
			expect( $( 'input.line_tax' ).val() ).toBe( expectedTax );
			expect( $( 'input.line_subtotal_tax' ).val() ).toBe( expectedTax );
		}
	);
} );
