/**
 * @jest-environment jest-fixed-jsdom
 */

// Apostrophe-bearing coupon fixtures. `billing_email` is the field from the
// original report, and the coupon endpoints post it from the checkout page.
const COUPON_CODE = "SAVE'10";
const BILLING_EMAIL = "o'brien@example.com";

describe( 'createCheckoutPlaceOrderApi', () => {
	let $allNotices;
	let $checkoutFields;
	let $couponForm;
	let $form;
	let $removeCouponLink;
	let $termsCheckbox;
	let $termsRow;
	let $updateOrderReviewNotices;
	let $checkoutNotices;
	let capturedApi;
	let capturedAjaxRequests;
	let jQueryMock;
	let mockBody;
	let serializedCheckoutData;
	// Set the number of invalid `.form-row` elements that are hidden (e.g. the
	// collapsed "Ship to a different address?" shipping fields). These must never
	// block submission, so `validate()` should only count visible invalid fields.
	let setHiddenInvalidCount;
	// Fire a handler that checkout.js delegated off document.body, with `this`
	// bound to the element that would have matched the selector.
	let triggerDelegatedBodyEvent;

	beforeEach( () => {
		capturedApi = null;
		capturedAjaxRequests = [];
		serializedCheckoutData =
			"billing_email=shopper'o%40example.test" +
			'&company=Rock+%26+Roll' +
			'&items%5B%5D=one' +
			"&delivery'note=Recipient's+door" +
			'&reference=already%27encoded';
		let hiddenInvalidCount = 0;
		setHiddenInvalidCount = ( count ) => {
			hiddenInvalidCount = count;
		};

		// used to track whether terms checkbox is checked
		let termsChecked = false;

		const termsRowClasses = new Set();
		const formInvalidElements = new Set();

		$termsRow = {
			addClass: jest.fn( ( cls ) => {
				cls.split( ' ' ).forEach( ( c ) => formInvalidElements.add( 'terms-row' ) );
				cls.split( ' ' ).forEach( ( c ) => termsRowClasses.add( c ) );
				return $termsRow;
			} ),
			removeClass: jest.fn( ( cls ) => {
				cls.split( ' ' ).forEach( ( c ) => termsRowClasses.delete( c ) );
				if ( cls.includes( 'woocommerce-invalid' ) ) {
					formInvalidElements.delete( 'terms-row' );
				}
				return $termsRow;
			} ),
			hasClass: jest.fn( ( cls ) => termsRowClasses.has( cls ) ),
			length: 1,
			offset: jest.fn( () => ( { top: 100 } ) ),
		};

		$termsCheckbox = {
			length: 1,
			is: jest.fn( ( selector ) => {
				if ( selector === ':checked' ) {
					return termsChecked;
				}
				return false;
			} ),
			closest: jest.fn( () => $termsRow ),
			trigger: jest.fn(),
		};

		// a helper to set the checkbox's state.
		$termsCheckbox.setChecked = ( checked ) => {
			termsChecked = checked;
		};

		$checkoutFields = {
			trigger: jest.fn( () => $checkoutFields ),
		};
		$allNotices = {
			remove: jest.fn(),
		};
		$updateOrderReviewNotices = {
			remove: jest.fn(),
		};
		$checkoutNotices = {
			remove: jest.fn(),
		};

		$form = {
			addClass: jest.fn( () => $form ),
			block: jest.fn( () => $form ),
			data: jest.fn(),
			is: jest.fn( () => false ),
			length: 1,
			find: jest.fn( ( selector ) => {
				if ( selector === 'input[name="terms"]:visible' ) {
					return $termsCheckbox;
				}
				if ( selector === '.input-text, select, input:checkbox' ) {
					return $checkoutFields;
				}
				if ( selector === '.woocommerce-invalid:visible' ) {
					// Visible invalid fields only (e.g. the terms row). Hidden
					// invalid fields are deliberately excluded.
					return {
						length: formInvalidElements.size,
						first: jest.fn( () => ( {
							length: formInvalidElements.size > 0 ? 1 : 0,
							offset: jest.fn( () => ( { top: 100 } ) ),
						} ) ),
					};
				}
				if ( selector === '.woocommerce-invalid' ) {
					// Unfiltered query (includes hidden fields). The implementation
					// must NOT use this to gate submission; counting hidden invalid
					// fields here is the regression these tests guard against.
					const total = formInvalidElements.size + hiddenInvalidCount;
					return {
						length: total,
						first: jest.fn( () => ( {
							length: total > 0 ? 1 : 0,
							offset: jest.fn( () => ( { top: 100 } ) ),
						} ) ),
					};
				}
				if ( selector === '.validate-required:visible' ) {
					return { each: jest.fn() };
				}
				if ( selector === 'input[name="payment_method"]:checked' ) {
					return { val: jest.fn( () => 'test-gateway' ) };
				}
				if ( selector === 'input[name="billing_email"]' ) {
					// apply_coupon reads this off the checkout form.
					return { val: jest.fn( () => BILLING_EMAIL ) };
				}
				return { length: 0, trigger: jest.fn() };
			} ),
			prepend: jest.fn(),
			serialize: jest.fn( () => serializedCheckoutData ),
			trigger: jest.fn(),
			triggerHandler: jest.fn( () => true ),
		};

		// Add methods to $form for checkout.js initialization
		$form.on = jest.fn( () => $form );
		$form.attr = jest.fn( () => $form );

		// Default mock for unhandled selectors - provides all common jQuery methods
		const createDefaultMock = () => {
			const mock = {
				length: 0,
				on: jest.fn( () => mock ),
				off: jest.fn( () => mock ),
				attr: jest.fn( () => mock ),
				find: jest.fn( () => createDefaultMock() ),
				first: jest.fn( () => createDefaultMock() ),
				filter: jest.fn( () => createDefaultMock() ),
				eq: jest.fn( () => createDefaultMock() ),
				trigger: jest.fn( () => mock ),
				val: jest.fn(),
				prop: jest.fn( () => mock ),
				each: jest.fn( () => mock ),
				data: jest.fn(),
				serialize: jest.fn( () => '' ),
				addClass: jest.fn( () => mock ),
				removeClass: jest.fn( () => mock ),
				hasClass: jest.fn( () => false ),
				is: jest.fn( () => false ),
				get: jest.fn( () => [] ),
				text: jest.fn( () => '' ),
				html: jest.fn( () => '' ),
				closest: jest.fn( () => createDefaultMock() ),
				parent: jest.fn( () => createDefaultMock() ),
				parents: jest.fn( () => createDefaultMock() ),
				siblings: jest.fn( () => createDefaultMock() ),
				children: jest.fn( () => createDefaultMock() ),
				append: jest.fn( () => mock ),
				prepend: jest.fn( () => mock ),
				remove: jest.fn( () => mock ),
				empty: jest.fn( () => mock ),
				show: jest.fn( () => mock ),
				hide: jest.fn( () => mock ),
				css: jest.fn( () => mock ),
				slideUp: jest.fn( () => mock ),
				slideDown: jest.fn( () => mock ),
				fadeIn: jest.fn( () => mock ),
				fadeOut: jest.fn( () => mock ),
				offset: jest.fn( () => ( { top: 0, left: 0 } ) ),
				width: jest.fn( () => 0 ),
				height: jest.fn( () => 0 ),
				outerWidth: jest.fn( () => 0 ),
				outerHeight: jest.fn( () => 0 ),
				scrollTop: jest.fn( () => 0 ),
				focus: jest.fn( () => mock ),
				blur: jest.fn( () => mock ),
				block: jest.fn( () => mock ),
				unblock: jest.fn( () => mock ),
			};
			return mock;
		};

		// Simple event system for document.body to enable event-based API capture.
		// Registrations come in two shapes: direct `on( event, handler )` and
		// delegated `on( event, selector, handler )`. Several delegated handlers
		// share the 'click' event, so the selector has to be stored to tell them
		// apart, and only direct handlers respond to trigger().
		const bodyEventHandlers = {};
		mockBody = {
			on: jest.fn( ( event, selectorOrHandler, delegatedHandler ) => {
				const isDelegated = typeof delegatedHandler === 'function';
				if ( ! bodyEventHandlers[ event ] ) {
					bodyEventHandlers[ event ] = [];
				}
				bodyEventHandlers[ event ].push( {
					selector: isDelegated ? selectorOrHandler : null,
					handler: isDelegated ? delegatedHandler : selectorOrHandler,
				} );
				return mockBody;
			} ),
			trigger: jest.fn( ( event, args ) => {
				( bodyEventHandlers[ event ] || [] )
					.filter( ( entry ) => entry.selector === null )
					.forEach( ( entry ) => entry.handler( {}, ...( args || [] ) ) );
				return mockBody;
			} ),
			hasClass: jest.fn( () => false ),
		};

		triggerDelegatedBodyEvent = ( event, selector, element ) => {
			const entry = ( bodyEventHandlers[ event ] || [] ).find(
				( candidate ) => candidate.selector === selector
			);
			if ( ! entry ) {
				throw new Error(
					'No delegated ' + event + ' handler for ' + selector
				);
			}
			entry.handler.call( element, { preventDefault: jest.fn() } );
		};

		// update_order_review sends these as siblings of post_data, so they have
		// to carry apostrophes for the test to prove the whole body is encoded.
		const addressFieldValues = {
			'#billing_country': 'US',
			'#billing_state': "O'State",
			':input#billing_postcode': '12345',
			'#billing_city': "O'Fallon",
			':input#billing_address_1': "123 O'Brien Ave",
			':input#billing_address_2': "Apt O'2",
		};

		// The coupon form is bound directly at init(), so it needs a stable mock
		// rather than a fresh default one per lookup — otherwise the submit
		// registration can't be retrieved afterwards.
		$couponForm = {
			length: 1,
			hide: jest.fn( () => $couponForm ),
			on: jest.fn( () => $couponForm ),
			is: jest.fn( () => false ),
			addClass: jest.fn( () => $couponForm ),
			removeClass: jest.fn( () => $couponForm ),
			block: jest.fn( () => $couponForm ),
			unblock: jest.fn( () => $couponForm ),
			slideUp: jest.fn( () => $couponForm ),
			before: jest.fn( () => $couponForm ),
			find: jest.fn( ( selector ) => {
				if ( selector === 'input[name="coupon_code"]' ) {
					return { val: jest.fn( () => COUPON_CODE ) };
				}
				return createDefaultMock();
			} ),
		};

		// The clicked ".woocommerce-remove-coupon" element. remove_coupon reads
		// the code off it with data( 'coupon' ).
		$removeCouponLink = {
			length: 1,
			parents: jest.fn( () => createDefaultMock() ),
			data: jest.fn( ( key ) =>
				key === 'coupon' ? COUPON_CODE : undefined
			),
		};

		// Mock jQuery - needs to handle document ready pattern: jQuery(function($) { ... })
		jQueryMock = jest.fn( ( selectorOrCallback ) => {
			// Handle document ready: jQuery(function($) { ... })
			if ( typeof selectorOrCallback === 'function' ) {
				// Execute immediately with jQuery mock as argument
				selectorOrCallback( jQueryMock );
				return jQueryMock;
			}
			if ( selectorOrCallback === 'form.checkout' ) {
				return $form;
			}
			if ( selectorOrCallback === $form ) {
				return $form;
			}
			if (
				selectorOrCallback ===
				'.woocommerce-error, .woocommerce-message, .is-error, .is-success'
			) {
				return $allNotices;
			}
			if (
				selectorOrCallback ===
				'.woocommerce-NoticeGroup-updateOrderReview'
			) {
				return $updateOrderReviewNotices;
			}
			if (
				selectorOrCallback === '.woocommerce-NoticeGroup-checkout'
			) {
				return $checkoutNotices;
			}
			if (
				selectorOrCallback === 'form.checkout_coupon' ||
				selectorOrCallback === $couponForm
			) {
				return $couponForm;
			}
			if ( selectorOrCallback === $removeCouponLink ) {
				return $removeCouponLink;
			}
			if ( selectorOrCallback === '#order_review' ) {
				return { length: 0, on: jest.fn(), attr: jest.fn(), find: jest.fn( () => ( { length: 0, val: jest.fn() } ) ) };
			}
			if ( selectorOrCallback === 'html, body' ) {
				return { animate: jest.fn() };
			}
			if ( selectorOrCallback === document.body ) {
				return mockBody;
			}
			if ( addressFieldValues[ selectorOrCallback ] !== undefined ) {
				const addressMock = createDefaultMock();
				addressMock.val = jest.fn(
					() => addressFieldValues[ selectorOrCallback ]
				);
				return addressMock;
			}
			// Return a default mock for any other selector
			return createDefaultMock();
		} );
		jQueryMock.blockUI = { defaults: { overlayCSS: {} } };
		jQueryMock.ajax = jest.fn( ( options ) => {
			capturedAjaxRequests.push( options );
			return { abort: jest.fn() };
		} );
		jQueryMock.param = jest.fn( ( object ) => {
			const parts = [];
			const add = ( key, value ) => {
				parts.push(
					encodeURIComponent( key ) +
						'=' +
						encodeURIComponent(
							value === null || value === undefined ? '' : value
						)
				);
			};
			const buildParams = ( prefix, value ) => {
				if ( value !== null && typeof value === 'object' ) {
					Object.keys( value ).forEach( ( key ) =>
						buildParams( prefix + '[' + key + ']', value[ key ] )
					);
					return;
				}
				add( prefix, value );
			};
			Object.keys( object ).forEach( ( key ) =>
				buildParams( key, object[ key ] )
			);
			return parts.join( '&' ).split( '%20' ).join( '+' );
		} );
		jQueryMock.ajaxSetup = jest.fn();
		jQueryMock.isEmptyObject = jest.fn( ( value ) => {
			return Object.keys( value ).length === 0;
		} );
		jQueryMock.scroll_to_notices = jest.fn();

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		global.window.wc_checkout_params = {
			checkout_url: '/?wc-ajax=checkout',
			gateways_with_custom_place_order_button: [ 'test-gateway' ],
			is_checkout: '0',
			option_guest_checkout: 'no',
			update_order_review_nonce: 'nonce',
			apply_coupon_nonce: 'nonce',
			remove_coupon_nonce: 'nonce',
			wc_ajax_url: '/?wc-ajax=%%endpoint%%',
		};

		global.window.wc = {
			customPlaceOrderButton: {
				__getForm: jest.fn( () => $form ),
				__maybeShow: jest.fn( ( gatewayId, api ) => {
					capturedApi = api;
				} ),
				__maybeHideDefaultButtonOnInit: jest.fn(),
				__cleanup: jest.fn(),
			},
		};

		// requiring checkout.js - this will execute the jQuery wrapper
		jest.resetModules();
		require( '../checkout' );

		// Trigger the event to capture the API via __maybeShow
		// This simulates a gateway registering after page load
		mockBody.trigger( 'wc_custom_place_order_button_registered', [ 'test-gateway' ] );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Terms checkbox validation', () => {
		test( 'should return hasError: true when terms checkbox is not checked', async () => {
			$termsCheckbox.setChecked( false );

			const result = await capturedApi.validate();

			expect( result.hasError ).toBe( true );
			expect( $termsRow.addClass ).toHaveBeenCalledWith( 'woocommerce-invalid' );
		} );

		test( 'should return hasError: false when terms checkbox is checked', async () => {
			$termsCheckbox.setChecked( true );

			const result = await capturedApi.validate();

			expect( result.hasError ).toBe( false );
		} );

		test( 'should clear stale invalid state before re-validating terms', async () => {
			// First validation: terms not checked
			$termsCheckbox.setChecked( false );
			await capturedApi.validate();

			expect( $termsRow.addClass ).toHaveBeenCalledWith( 'woocommerce-invalid' );

			// clearing the mock history so the expectations are clearer.
			$termsRow.removeClass.mockClear();
			$termsRow.addClass.mockClear();

			// Second validation: marking the terms as checked
			$termsCheckbox.setChecked( true );
			const result = await capturedApi.validate();

			// Should have cleared the invalid state first
			expect( $termsRow.removeClass ).toHaveBeenCalledWith( 'woocommerce-invalid' );
			// Should NOT have re-added the invalid class
			expect( $termsRow.addClass ).not.toHaveBeenCalledWith( 'woocommerce-invalid' );
			// Should pass validation
			expect( result.hasError ).toBe( false );
		} );

		test( 'should allow submission after checking terms following a failed validation', async () => {
			// First attempt: terms not checked - should fail
			$termsCheckbox.setChecked( false );
			const firstResult = await capturedApi.validate();
			expect( firstResult.hasError ).toBe( true );

			// pretending the user checked the terms checkbox
			$termsCheckbox.setChecked( true );

			// Second attempt: should pass on first try (not require double-click)
			const secondResult = await capturedApi.validate();
			expect( secondResult.hasError ).toBe( false );
		} );
	} );

	describe( 'Hidden field validation', () => {
		test( 'should ignore invalid fields that are hidden (e.g. collapsed shipping address)', async () => {
			// A shippable cart renders the "Ship to a different address?" block,
			// whose required shipping fields are present but hidden when the option
			// is unchecked. Field-level validation flags them as invalid regardless
			// of visibility, so validate() must only count *visible* invalid fields
			// or the order is never submitted even when the visible form is valid.
			$termsCheckbox.setChecked( true );
			setHiddenInvalidCount( 5 );

			const result = await capturedApi.validate();

			expect( result.hasError ).toBe( false );
		} );

		test( 'should only count visible invalid fields', async () => {
			$termsCheckbox.setChecked( true );
			setHiddenInvalidCount( 5 );

			await capturedApi.validate();

			expect( $form.find ).toHaveBeenCalledWith(
				'.woocommerce-invalid:visible'
			);
			expect( $form.find ).not.toHaveBeenCalledWith( '.woocommerce-invalid' );
		} );
	} );

	describe( 'Checkout form serialization', () => {
		beforeEach( () => {
			jest.useFakeTimers();
		} );

		afterEach( () => {
			jest.clearAllTimers();
			jest.useRealTimers();
		} );

		const expectedSerializedData =
			'billing_email=shopper%27o%40example.test' +
			'&company=Rock+%26+Roll' +
			'&items%5B%5D=one' +
			'&delivery%27note=Recipient%27s+door' +
			'&reference=already%27encoded';

		test( 'should encode apostrophes in update order review data', () => {
			mockBody.trigger( 'update_checkout', [
				{ update_shipping_method: false },
			] );
			jest.runOnlyPendingTimers();

			const request = capturedAjaxRequests.find( ( options ) =>
				options.url.includes( 'update_order_review' )
			);

			expect( request ).toBeDefined();
			expect( request.data ).not.toContain( "'" );

			// Address fields travel outside post_data and must be encoded too.
			expect( request.data ).toContain( 'city=O%27Fallon' );
			expect( request.data ).toContain( 'address=123+O%27Brien+Ave' );
			expect( request.data ).toContain( 'state=O%27State' );

			// The whole body is encoded, so post_data survives the outer layer
			// byte-for-byte and raw-string consumers see what serialize() produced.
			const postData = new URLSearchParams( request.data ).get(
				'post_data'
			);
			expect( postData ).toBe( serializedCheckoutData );
		} );

		test( 'should encode apostrophes in final checkout data', () => {
			const submitRegistration = $form.on.mock.calls.find(
				( call ) => call[ 0 ] === 'submit'
			);
			expect( submitRegistration ).toBeDefined();

			submitRegistration[ 1 ].call( $form );
			const request = capturedAjaxRequests.find(
				( options ) => options.url === '/?wc-ajax=checkout'
			);

			expect( request ).toBeDefined();
			expect( request.data ).toBe( expectedSerializedData );
		} );
	} );

	// The coupon endpoints are the fourth and third of the four request bodies
	// routed through encodeApostrophes(). No fake timers here: neither handler
	// schedules one unless its success callback runs, which these never do.
	describe( 'Coupon request serialization', () => {
		test( 'should encode apostrophes in apply coupon data', () => {
			const submitRegistration = $couponForm.on.mock.calls.find(
				( call ) => call[ 0 ] === 'submit'
			);
			expect( submitRegistration ).toBeDefined();

			submitRegistration[ 1 ]( { currentTarget: $couponForm } );

			const request = capturedAjaxRequests.find( ( options ) =>
				options.url.includes( 'apply_coupon' )
			);

			expect( request ).toBeDefined();
			expect( request.data ).not.toContain( "'" );

			const body = new URLSearchParams( request.data );
			expect( body.get( 'coupon_code' ) ).toBe( COUPON_CODE );
			expect( body.get( 'billing_email' ) ).toBe( BILLING_EMAIL );
		} );

		test( 'should encode apostrophes in remove coupon data', () => {
			triggerDelegatedBodyEvent(
				'click',
				'.woocommerce-remove-coupon',
				$removeCouponLink
			);

			const request = capturedAjaxRequests.find( ( options ) =>
				options.url.includes( 'remove_coupon' )
			);

			expect( request ).toBeDefined();
			expect( request.data ).not.toContain( "'" );
			expect( new URLSearchParams( request.data ).get( 'coupon' ) ).toBe(
				COUPON_CODE
			);
		} );
	} );
	// update_order_review keeps `result` as the legacy "a notice was rendered" signal, so
	// notices are rendered for every result and only `has_errors` clears the existing ones,
	// revalidates the form fields, and scrolls. Responses without the flag fall back to `result`.
	describe( 'Checkout update notices', () => {
		beforeEach( () => {
			jest.useFakeTimers();
		} );

		afterEach( () => {
			jest.clearAllTimers();
			jest.useRealTimers();
		} );

		const sendCheckoutUpdateResponse = ( response, args ) => {
			mockBody.trigger( 'update_checkout', [
				args || { update_shipping_method: false },
			] );
			jest.runOnlyPendingTimers();

			const request = capturedAjaxRequests.find( ( options ) =>
				options.url.includes( 'update_order_review' )
			);
			expect( request ).toBeDefined();

			request.success( response );
		};

		test( 'should render successful notices without validating checkout fields', () => {
			sendCheckoutUpdateResponse( {
				result: 'failure',
				has_errors: false,
				messages:
					'<div class="woocommerce-message">Coupon applied.</div>',
			} );

			expect( $form.prepend ).toHaveBeenCalledWith(
				expect.stringContaining(
					'woocommerce-NoticeGroup-updateOrderReview'
				)
			);
			expect( $form.prepend ).toHaveBeenCalledWith(
				expect.stringContaining( 'Coupon applied.' )
			);
			expect( $updateOrderReviewNotices.remove ).toHaveBeenCalledTimes( 1 );
			expect( $allNotices.remove ).not.toHaveBeenCalled();
			expect( $checkoutFields.trigger ).not.toHaveBeenCalled();
			expect( jQueryMock.scroll_to_notices ).not.toHaveBeenCalled();
		} );

		test( 'should preserve failure notice replacement and field validation', () => {
			sendCheckoutUpdateResponse( {
				result: 'failure',
				has_errors: true,
				messages:
					'<ul class="woocommerce-error"><li>Invalid address.</li></ul>',
			} );

			expect( $form.prepend ).toHaveBeenCalledWith(
				expect.stringContaining( 'Invalid address.' )
			);
			expect( $allNotices.remove ).toHaveBeenCalledTimes( 1 );
			expect( $checkoutFields.trigger ).toHaveBeenNthCalledWith(
				1,
				'validate'
			);
			expect( $checkoutFields.trigger ).toHaveBeenNthCalledWith(
				2,
				'blur'
			);
			expect( jQueryMock.scroll_to_notices ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'should leave notices and fields unchanged for message-free success', () => {
			sendCheckoutUpdateResponse( {
				result: 'success',
				has_errors: false,
				messages: '',
			} );

			expect( $form.prepend ).not.toHaveBeenCalled();
			expect( $allNotices.remove ).not.toHaveBeenCalled();
			expect( $checkoutFields.trigger ).not.toHaveBeenCalled();
			expect( jQueryMock.scroll_to_notices ).not.toHaveBeenCalled();
		} );

		test( 'should ignore a non-boolean error flag and use the result', () => {
			sendCheckoutUpdateResponse( {
				result: 'success',
				has_errors: 'false',
				messages: '',
			} );

			expect( $allNotices.remove ).not.toHaveBeenCalled();
			expect( $checkoutFields.trigger ).not.toHaveBeenCalled();
			expect( jQueryMock.scroll_to_notices ).not.toHaveBeenCalled();
		} );

		test( 'should keep firing updated_checkout when the shipping method is gone', () => {
			// The refreshed fragment can drop the method that triggered the update, so
			// the element the focus restore points at is no longer in the document.
			expect(
				document.getElementById( 'shipping_method_0_flat_rate1' )
			).toBeNull();

			expect( () =>
				sendCheckoutUpdateResponse(
					{
						result: 'success',
						has_errors: false,
						messages: '',
					},
					{
						update_shipping_method: false,
						current_target: {
							id: 'shipping_method_0_flat_rate1',
						},
					}
				)
			).not.toThrow();

			expect( mockBody.trigger ).toHaveBeenCalledWith(
				'updated_checkout',
				expect.anything()
			);
		} );

		test( 'should clear a stale place-order notice when rendering a non-error one', () => {
			// A failed place order leaves `.woocommerce-NoticeGroup-checkout` on the page.
			// The next non-error notice supersedes it, but must leave every other notice.
			sendCheckoutUpdateResponse( {
				result: 'failure',
				has_errors: false,
				messages:
					'<div class="woocommerce-message">Coupon applied.</div>',
			} );

			expect( $checkoutNotices.remove ).toHaveBeenCalledTimes( 1 );
			expect( $allNotices.remove ).not.toHaveBeenCalled();
			expect( $form.prepend ).toHaveBeenCalledWith(
				expect.stringContaining( 'Coupon applied.' )
			);
		} );

		test( 'should ignore messages on a response that reports no notice', () => {
			// A third-party callback can answer this endpoint before Core does. Trunk
			// rendered nothing for a `success` result, so neither do we.
			sendCheckoutUpdateResponse( {
				result: 'success',
				messages:
					'<div class="woocommerce-message">Third-party notice.</div>',
			} );

			expect( $form.prepend ).not.toHaveBeenCalled();
			expect( $checkoutNotices.remove ).not.toHaveBeenCalled();
			expect( $allNotices.remove ).not.toHaveBeenCalled();
			expect( $checkoutFields.trigger ).not.toHaveBeenCalled();
			expect( jQueryMock.scroll_to_notices ).not.toHaveBeenCalled();
		} );

		test( 'should treat a response without the error flag as a failure', () => {
			sendCheckoutUpdateResponse( {
				result: 'failure',
				messages:
					'<ul class="woocommerce-error"><li>Invalid address.</li></ul>',
			} );

			expect( $allNotices.remove ).toHaveBeenCalledTimes( 1 );
			expect( $checkoutFields.trigger ).toHaveBeenNthCalledWith(
				1,
				'validate'
			);
			expect( jQueryMock.scroll_to_notices ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
