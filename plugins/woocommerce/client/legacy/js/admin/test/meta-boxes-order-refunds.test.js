const { readFileSync } = require( 'fs' );
const { runInNewContext } = require( 'vm' );

describe( 'Order refund tax rounding', () => {
	let $;

	beforeAll( () => {
		jest.useFakeTimers();
		jest.resetModules();
		$ = require( 'jquery' );
		global.jQuery = $;
		global.accounting = require( '../../accounting/accounting' );
		const context = {};
		runInNewContext( readFileSync( require.resolve( '../../round/round' ), 'utf8' ), context );
		global.round = context.round;
		global.woocommerce_admin = { mon_decimal_point: '.' };
		global.woocommerce_admin_meta_boxes = {};
		$.fn.selectWoo = $.fn.stupidtable = function () {
			return this;
		};
		document.body.innerHTML = '<div id="woocommerce-order-items"></div>';
		require( '../meta-boxes-order' );
		jest.runAllTimers();
		jest.useRealTimers();
	} );

	beforeEach( () => {
		Object.assign( global.woocommerce_admin_meta_boxes, {
			round_at_subtotal: 'no',
			currency_format_num_decimals: 2,
			rounding_precision: 4,
			tax_rounding_mode: 2,
		} );
		global.woocommerce_admin.mon_decimal_point = '.';
		$( '#woocommerce-order-items' ).html( `
			<table class="woocommerce_order_items"><tbody></tbody></table>
			<div class="wc-order-refund-items"><input id="refund_amount"></div>
		` );
	} );

	afterAll( () => {
		$( document.body ).off();
		document.body.innerHTML = '';
		delete $.fn.selectWoo;
		delete $.fn.stupidtable;
		delete global.jQuery;
		delete global.accounting;
		delete global.round;
		delete global.woocommerce_admin;
		delete global.woocommerce_admin_meta_boxes;
		delete window.wcTracks;
	} );

	const addItem = ( total, tax, quantity = 1 ) => {
		const localize = ( value ) => String( value ).replace( '.', global.woocommerce_admin.mon_decimal_point );
		const $row = $( `
			<tr class="item">
				<td><input class="quantity" value="${ quantity }"></td>
				<td><input class="line_total" data-total="${ localize( total ) }"></td>
				<td><input class="line_tax" data-tax_id="1" data-total_tax="${ localize( tax ) }"></td>
				<td class="refund">
					<input class="refund_order_item_qty">
					<input class="refund_line_total">
					<input class="refund_line_tax" data-tax_id="1">
				</td>
			</tr>
		` );
		$( '.woocommerce_order_items tbody' ).append( $row );
		return $row;
	};

	test.each( [
		[ 1, 1.225, '1.23' ],
		[ 2, 1.225, '1.22' ],
		[ 2, 1.22501, '1.23' ],
	] )( 'uses tax rounding mode %s for stored tax %s', ( mode, tax, expected ) => {
		global.woocommerce_admin_meta_boxes.tax_rounding_mode = mode;
		const $row = addItem( 0, tax );
		$row.find( '.refund_order_item_qty' ).val( 1 ).trigger( 'change' );

		expect( $row.find( '.refund_line_tax' ).val() ).toBe( expected );
		expect( $( '#refund_amount' ).val() ).toBe( expected );
	} );

	test.each( [ '.', ',' ] )( 'refunds a 7.35 item with decimal separator %s', ( decimalPoint ) => {
		global.woocommerce_admin.mon_decimal_point = decimalPoint;
		const $row = addItem( 6.125, 1.225 );
		$row.find( '.refund_order_item_qty' ).val( 1 ).trigger( 'change' );

		expect( $row.find( '.refund_line_tax' ).val() ).toBe( `1${ decimalPoint }22` );
		expect( $( '#refund_amount' ).val() ).toBe( `7${ decimalPoint }35` );
	} );

	test( 'prorates partial refunds from the rounded original line tax', () => {
		const $row = addItem( 10, 0.331, 2 );
		$row.find( '.refund_order_item_qty' ).val( 1 ).trigger( 'change' );

		expect( $row.find( '.refund_line_tax' ).val() ).toBe( '0.16' );
		expect( $( '#refund_amount' ).val() ).toBe( '5.16' );
	} );

	test( 'uses tax rounding rather than price rounding for manually entered taxes', () => {
		const $row = addItem( 0, 0 );
		$row.find( '.refund_line_total' ).val( '6.125' ).trigger( 'change' );
		$row.find( '.refund_line_tax' ).val( '1.225' ).trigger( 'change' );

		expect( $( '#refund_amount' ).val() ).toBe( '7.35' );
	} );

	test( 'handles stored tax amounts in scientific notation', () => {
		const $row = addItem( 10, 1e-7 );
		$row.find( '.refund_order_item_qty' ).val( 1 ).trigger( 'change' );

		expect( $row.find( '.refund_line_tax' ).val() ).toBe( '0' );
		expect( $( '#refund_amount' ).val() ).toBe( '10.00' );
	} );

	test( 'preserves unrounded taxes when rounding at subtotal level', () => {
		global.woocommerce_admin_meta_boxes.round_at_subtotal = 'yes';
		const $row = addItem( 6.125, 1.225 );
		$row.find( '.refund_order_item_qty' ).val( 1 ).trigger( 'change' );

		expect( $row.find( '.refund_line_tax' ).val() ).toBe( '1.225' );
		expect( $( '#refund_amount' ).val() ).toBe( '7.35' );
	} );

	test.each( [
		[ 0, 12.5, '12' ],
		[ 3, 1.2345, '1.234' ],
	] )( 'uses currency precision %s', ( decimals, tax, expected ) => {
		global.woocommerce_admin_meta_boxes.currency_format_num_decimals = decimals;
		const $row = addItem( 0, tax );
		$row.find( '.refund_order_item_qty' ).val( 1 ).trigger( 'change' );

		expect( $row.find( '.refund_line_tax' ).val() ).toBe( expected );
		expect( $( '#refund_amount' ).val() ).toBe( expected );
	} );
} );
