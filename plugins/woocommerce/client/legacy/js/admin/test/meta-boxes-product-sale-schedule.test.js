describe( 'Product sale schedule cancel', () => {
	let $;

	beforeAll( () => {
		// jQuery binds document-ready callbacks on a timer once the document is loaded.
		jest.useFakeTimers();
		jest.resetModules();

		$ = require( 'jquery' );
		global.jQuery = $;
		global.woocommerce_admin_meta_boxes = { product_types: [] };
		global._ = { escape: ( value ) => value };
		[ 'tipTip', 'datepicker', 'sortable', 'selectWoo' ].forEach(
			( plugin ) => {
				$.fn[ plugin ] = function () {
					return this;
				};
			}
		);

		document.body.innerHTML = `
			<div id="woocommerce-product-data">
				<div class="woocommerce_variation">
					<p class="form-row">
						<label>
							<a href="#" class="sale_schedule">Schedule</a>
							<a href="#" class="cancel_sale_schedule">Cancel schedule</a>
						</label>
					</p>
					<div class="sale_price_dates_fields">
						<input type="text" class="sale_price_dates_from">
						<input type="text" class="sale_price_dates_to">
					</div>
				</div>
			</div>
		`;

		require( '../meta-boxes-product' );
		jest.runAllTimers();
		jest.useRealTimers();
	} );

	afterAll( () => {
		document.body.innerHTML = '';
		delete global.jQuery;
		delete global.woocommerce_admin_meta_boxes;
		delete global._;
	} );

	const cancelSchedule = ( from, to ) => {
		const changed = [];
		const $inputs = $( '.sale_price_dates_fields input' );

		$inputs.eq( 0 ).val( from );
		$inputs.eq( 1 ).val( to );
		$inputs.on( 'change.test', function () {
			changed.push( this.className );
		} );

		$( '.cancel_sale_schedule' ).trigger( 'click' );
		$inputs.off( 'change.test' );

		return { changed, values: $inputs.map( ( i, el ) => el.value ).get() };
	};

	test( 'does not fire change when the schedule dates are empty', () => {
		const { changed, values } = cancelSchedule( '', '' );

		expect( changed ).toEqual( [] );
		expect( values ).toEqual( [ '', '' ] );
	} );

	test( 'clears saved dates and fires change only for those dates', () => {
		const { changed, values } = cancelSchedule( '2030-01-01', '' );

		expect( changed ).toEqual( [ 'sale_price_dates_from' ] );
		expect( values ).toEqual( [ '', '' ] );
	} );
} );
