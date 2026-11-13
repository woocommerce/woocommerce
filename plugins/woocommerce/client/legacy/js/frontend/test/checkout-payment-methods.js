/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'Classic checkout payment method radios', () => {
	let $;
	// The payment section the next update_order_review request answers with.
	let nextPaymentSection;

	/**
	 * Markup of the payment section, as checkout/payment.php prints it.
	 *
	 * @param {Object}   args
	 * @param {string}   [args.preselected] Value of data-preselected; left out as an older template copy would.
	 * @param {string[]} args.methods       Gateway ids to list.
	 * @param {string}   [args.checked]     Gateway id the server marked as selected.
	 * @return {string} HTML.
	 */
	function paymentSection( { preselected, methods, checked } ) {
		const attribute = preselected
			? ` data-preselected="${ preselected }"`
			: '';
		const items = methods
			.map(
				( id ) => `
					<li class="wc_payment_method payment_method_${ id }">
						<input id="payment_method_${ id }" type="radio" class="input-radio" name="payment_method" value="${ id }"${
							id === checked ? ' checked="checked"' : ''
						} />
						<label for="payment_method_${ id }">${ id }</label>
					</li>`
			)
			.join( '' );

		return `
			<div id="payment" class="woocommerce-checkout-payment">
				<ul class="wc_payment_methods payment_methods methods"${ attribute }>${ items }</ul>
			</div>`;
	}

	function loadCheckout( section ) {
		document.body.innerHTML = `<form class="checkout woocommerce-checkout">${ paymentSection(
			section
		) }</form>`;

		jest.resetModules();
		$ = require( 'jquery' );
		global.jQuery = $;
		// Surface an error from the script's document-ready callback as a test failure.
		$.readyException = ( error ) => {
			throw error;
		};
		$.blockUI = { defaults: { overlayCSS: {} } };
		$.fn.block = function () {
			return this;
		};
		$.fn.unblock = function () {
			return this;
		};
		$.ajax = jest.fn( ( request ) => {
			request.success( {
				result: 'success',
				has_errors: false,
				messages: '',
				fragments: {
					'.woocommerce-checkout-payment': nextPaymentSection,
				},
			} );
			return { abort: jest.fn() };
		} );

		global.wc_checkout_params = {
			is_checkout: '0',
			option_guest_checkout: 'no',
			update_order_review_nonce: 'nonce',
			wc_ajax_url: '/?wc-ajax=%%endpoint%%',
		};
		global.window.wc = {
			customPlaceOrderButton: {
				__getForm: jest.fn(),
				__maybeShow: jest.fn(),
				__maybeHideDefaultButtonOnInit: jest.fn(),
				__cleanup: jest.fn(),
			},
		};

		require( '../checkout' );
		// jQuery runs document-ready callbacks on a timer once the document is loaded.
		jest.runAllTimers();
	}

	function updateOrderReview( section ) {
		nextPaymentSection = paymentSection( section );
		$( document.body ).trigger( 'update_checkout' );
		// update_checkout waits a few milliseconds to group changes into one request.
		jest.runAllTimers();
	}

	function radio( id ) {
		return document.getElementById( 'payment_method_' + id );
	}

	function checkedMethods() {
		return Array.from(
			document.querySelectorAll( 'input[name="payment_method"]:checked' )
		).map( ( input ) => input.value );
	}

	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
		document.body.replaceChildren();
	} );

	test( 'selects the first method when the list carries no data-preselected, as an older template copy would', () => {
		loadCheckout( { methods: [ 'bacs', 'cod' ] } );

		expect( checkedMethods() ).toEqual( [ 'bacs' ] );
	} );

	test( 'keeps the method the server selected', () => {
		loadCheckout( {
			preselected: 'yes',
			methods: [ 'bacs', 'cod' ],
			checked: 'cod',
		} );

		expect( checkedMethods() ).toEqual( [ 'cod' ] );
	} );

	test( 'selects nothing when the server selected none', () => {
		loadCheckout( { preselected: 'no', methods: [ 'bacs', 'cod' ] } );

		expect( checkedMethods() ).toEqual( [] );
	} );

	test( 'still selects nothing after an order review update that selected none', () => {
		loadCheckout( { preselected: 'no', methods: [ 'bacs', 'cod' ] } );

		updateOrderReview( { preselected: 'no', methods: [ 'bacs', 'cod' ] } );

		expect( checkedMethods() ).toEqual( [] );
	} );

	test( 'hides the radio of the only method when it is selected', () => {
		loadCheckout( {
			preselected: 'yes',
			methods: [ 'cod' ],
			checked: 'cod',
		} );

		expect( checkedMethods() ).toEqual( [ 'cod' ] );
		expect( radio( 'cod' ).style.display ).toBe( 'none' );
	} );

	test( 'shows the radio of the only method while the server selected none', () => {
		loadCheckout( { preselected: 'no', methods: [ 'cod' ] } );

		expect( checkedMethods() ).toEqual( [] );
		expect( radio( 'cod' ).style.display ).not.toBe( 'none' );
	} );

	test( 'keeps the radio of the only method on screen after the shopper selected it and the order review updated', () => {
		loadCheckout( { preselected: 'no', methods: [ 'cod' ] } );
		radio( 'cod' ).click();

		updateOrderReview( {
			preselected: 'yes',
			methods: [ 'cod' ],
			checked: 'cod',
		} );

		expect( checkedMethods() ).toEqual( [ 'cod' ] );
		expect( radio( 'cod' ).style.display ).not.toBe( 'none' );
	} );
} );
