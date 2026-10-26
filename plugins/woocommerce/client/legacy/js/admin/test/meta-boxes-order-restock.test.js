describe( 'Order refund restock checkbox', () => {
	let $;

	const renderItemRow = ( itemId, canRestock ) => `
		<tr class="item" data-order_item_id="${ itemId }">
			<td class="quantity">
				<input type="number" class="quantity" value="3">
				<div class="refund">
					<input type="number" class="refund_order_item_qty" data-can-restock="${ canRestock }">
				</div>
			</td>
			<td class="line_cost">
				<input type="text" class="line_total" data-total="30">
				<div class="refund">
					<input type="text" class="refund_line_total">
				</div>
			</td>
		</tr>
	`;

	// Handlers are delegated from #woocommerce-order-items, so only its contents are replaced.
	const renderOrderItems = ( { lines, defaultChecked = true } ) => {
		document.getElementById( 'woocommerce-order-items' ).innerHTML = `
				<table class="woocommerce_order_items">
					<tbody>
						${ lines
							.map( ( canRestock, index ) =>
								renderItemRow( index + 1, canRestock )
							)
							.join( '' ) }
					</tbody>
				</table>
				<button type="button" class="refund-items"></button>
				<div class="wc-order-refund-items">
					<table class="wc-order-totals">
						<tr>
							<td class="label">
								<label for="restock_refunded_items">Restock refunded items:</label>
								<p class="description" id="restock_refunded_items_description"></p>
							</td>
							<td class="total">
								<input type="checkbox" id="restock_refunded_items" aria-describedby="restock_refunded_items_description" ${
									defaultChecked ? 'checked' : ''
								}>
							</td>
						</tr>
					</table>
					<input type="text" id="refund_amount">
				</div>
		`;
	};

	const checkbox = () => document.getElementById( 'restock_refunded_items' );
	const description = () =>
		document.getElementById( 'restock_refunded_items_description' )
			.textContent;
	const openRefundPanel = () => $( 'button.refund-items' ).trigger( 'click' );
	const setRefundQty = ( itemId, qty ) =>
		$( `tr[data-order_item_id="${ itemId }"] input.refund_order_item_qty` )
			.val( qty )
			.trigger( 'change' );

	beforeAll( () => {
		// jQuery binds document-ready callbacks on a timer once the document is loaded.
		jest.useFakeTimers();
		jest.resetModules();

		$ = require( 'jquery' );
		global.jQuery = $;
		global.accounting = require( '../../accounting/accounting' );
		$.fn.selectWoo = jest.fn().mockReturnThis();
		$.fn.stupidtable = jest.fn().mockReturnThis();
		global.woocommerce_admin = { mon_decimal_point: '.' };
		global.woocommerce_admin_meta_boxes = {
			currency_format_num_decimals: 2,
			rounding_precision: 6,
			i18n_restock_enter_quantity:
				'Enter a quantity above to return items to stock.',
			i18n_restock_count_single: '1 item will be restocked.',
			i18n_restock_count_plural: '%qty% items will be restocked.',
			i18n_restock_none: 'No items will be restocked.',
			i18n_restock_items_not_reduced:
				'Stock was not reduced for these items.',
			i18n_restock_order_not_reduced:
				'Stock was not reduced for this order.',
		};

		document.body.innerHTML = '<div id="woocommerce-order-items"></div>';
		require( '../meta-boxes-order' );
		jest.runAllTimers();
		jest.useRealTimers();
	} );

	afterAll( () => {
		document.body.innerHTML = '';
		delete global.jQuery;
		delete global.accounting;
		delete global.woocommerce_admin;
		delete global.woocommerce_admin_meta_boxes;
	} );

	test( 'is disabled and unticked when the refund panel opens', () => {
		renderOrderItems( { lines: [ 'yes' ] } );
		openRefundPanel();

		expect( checkbox().disabled ).toBe( true );
		expect( checkbox().checked ).toBe( false );
		expect( description() ).toBe(
			'Enter a quantity above to return items to stock.'
		);
	} );

	test( 'stays disabled when only a refund amount is entered', () => {
		renderOrderItems( { lines: [ 'yes' ] } );
		openRefundPanel();
		$( '#refund_amount' ).val( '10' ).trigger( 'change' );

		expect( checkbox().disabled ).toBe( true );
		expect( checkbox().checked ).toBe( false );
	} );

	test( 'enables, ticks and counts the items once a quantity is entered', () => {
		renderOrderItems( { lines: [ 'yes', 'yes' ] } );
		openRefundPanel();

		setRefundQty( 1, 1 );
		expect( checkbox().disabled ).toBe( false );
		expect( checkbox().checked ).toBe( true );
		expect( description() ).toBe( '1 item will be restocked.' );

		setRefundQty( 2, 2 );
		expect( description() ).toBe( '3 items will be restocked.' );
	} );

	test( 'keeps the merchant choice when unticked and quantities change', () => {
		renderOrderItems( { lines: [ 'yes' ] } );
		openRefundPanel();
		setRefundQty( 1, 1 );

		$( checkbox() ).prop( 'checked', false ).trigger( 'change' );
		expect( description() ).toBe( 'No items will be restocked.' );

		setRefundQty( 1, 2 );
		expect( checkbox().checked ).toBe( false );
		expect( description() ).toBe( 'No items will be restocked.' );
	} );

	test( 'disables and unticks again when all quantities are cleared', () => {
		renderOrderItems( { lines: [ 'yes' ] } );
		openRefundPanel();
		setRefundQty( 1, 1 );
		setRefundQty( 1, '' );

		expect( checkbox().disabled ).toBe( true );
		expect( checkbox().checked ).toBe( false );
		expect( description() ).toBe(
			'Enter a quantity above to return items to stock.'
		);
	} );

	test( 'only counts lines that can be restocked', () => {
		renderOrderItems( { lines: [ 'yes', 'no' ] } );
		openRefundPanel();
		setRefundQty( 1, 1 );
		setRefundQty( 2, 2 );

		expect( description() ).toBe( '1 item will be restocked.' );
	} );

	test( 'stays disabled when quantities are only on lines that cannot be restocked', () => {
		renderOrderItems( { lines: [ 'yes', 'no' ] } );
		openRefundPanel();
		setRefundQty( 2, 2 );

		expect( checkbox().disabled ).toBe( true );
		expect( checkbox().checked ).toBe( false );
		expect( description() ).toBe(
			'Stock was not reduced for these items.'
		);
	} );

	test( 'stays disabled when no line in the order can be restocked', () => {
		renderOrderItems( { lines: [ 'no' ] } );
		openRefundPanel();

		expect( checkbox().disabled ).toBe( true );
		expect( description() ).toBe( 'Stock was not reduced for this order.' );

		setRefundQty( 1, 1 );
		expect( checkbox().disabled ).toBe( true );
		expect( checkbox().checked ).toBe( false );
		expect( description() ).toBe( 'Stock was not reduced for this order.' );
	} );

	test( 'respects an unticked default from the woocommerce_restock_refunded_items filter', () => {
		renderOrderItems( { lines: [ 'yes' ], defaultChecked: false } );
		openRefundPanel();
		setRefundQty( 1, 1 );

		expect( checkbox().disabled ).toBe( false );
		expect( checkbox().checked ).toBe( false );
		expect( description() ).toBe( 'No items will be restocked.' );
	} );
} );
