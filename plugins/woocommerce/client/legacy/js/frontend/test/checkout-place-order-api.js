describe('createCheckoutPlaceOrderApi', () => {
	let $form;
	let $termsCheckbox;
	let $termsRow;
	let capturedApi;

	beforeEach(() => {
		capturedApi = null;

		// used to track whether terms checkbox is checked
		let termsChecked = false;

		const termsRowClasses = new Set();
		const formInvalidElements = new Set();

		$termsRow = {
			addClass: jest.fn((cls) => {
				cls.split(' ').forEach((c) => formInvalidElements.add('terms-row'));
				cls.split(' ').forEach((c) => termsRowClasses.add(c));
				return $termsRow;
			}),
			removeClass: jest.fn((cls) => {
				cls.split(' ').forEach((c) => termsRowClasses.delete(c));
				if (cls.includes('woocommerce-invalid')) {
					formInvalidElements.delete('terms-row');
				}
				return $termsRow;
			}),
			hasClass: jest.fn((cls) => termsRowClasses.has(cls)),
			length: 1,
			offset: jest.fn(() => (
				{ top: 100 }
			)),
		};

		$termsCheckbox = {
			length: 1,
			is: jest.fn((selector) => {
				if (selector === ':checked') {
					return termsChecked;
				}
				return false;
			}),
			closest: jest.fn(() => $termsRow),
			trigger: jest.fn(),
		};

		// a helper to set the checkbox's state.
		$termsCheckbox.setChecked = (checked) => {
			termsChecked = checked;
		};

		$form = {
			length: 1,
			find: jest.fn((selector) => {
				if (selector === 'input[name="terms"]:visible') {
					return $termsCheckbox;
				}
				if (selector === '.input-text, select, input:checkbox') {
					return { trigger: jest.fn() };
				}
				if (selector === '.woocommerce-invalid') {
					return {
						length: formInvalidElements.size,
						first: jest.fn(() => (
							{
								length: formInvalidElements.size > 0 ? 1 : 0,
								offset: jest.fn(() => (
									{ top: 100 }
								)),
							}
						)),
					};
				}
				if (selector === '.validate-required:visible') {
					return { each: jest.fn() };
				}
				if (selector === 'input[name="payment_method"]:checked') {
					return { val: jest.fn(() => 'test-gateway') };
				}
				return { length: 0, trigger: jest.fn() };
			}),
			trigger: jest.fn(),
		};

		$form.on = jest.fn(() => $form);
		$form.attr = jest.fn(() => $form);

		const createDefaultMock = () => {
			const mock = (
				{
					length: 0,
					on: jest.fn(() => mock),
					find: jest.fn(() => createDefaultMock()),
					filter: jest.fn(() => createDefaultMock()),
					eq: jest.fn(() => createDefaultMock()),
					trigger: jest.fn(() => mock),
					prop: jest.fn(() => mock),
					addClass: jest.fn(() => mock),
					removeClass: jest.fn(() => mock),
					hasClass: jest.fn(() => false),
					hide: jest.fn(() => mock),
				}
			);

			return mock;
		};

		// creating this kind of event system for document.body to enable event-based API capture
		const bodyEventHandlers = {};
		const mockBody = {
			on: jest.fn((event, handler) => {
				if (!bodyEventHandlers[event]) {
					bodyEventHandlers[event] = [];
				}
				bodyEventHandlers[event].push(handler);
				return mockBody;
			}),
			trigger: jest.fn((event, args) => {
				const handlers = bodyEventHandlers[event] || [];
				handlers.forEach((handler) => handler({}, ...(
					args || []
				)));
				return mockBody;
			}),
			hasClass: jest.fn(() => false),
		};

		// this implementation to handle a "document ready" pattern like jQuery(function($) { ... })
		const jQueryMock = jest.fn((selectorOrCallback) => {
			// Handle document ready: jQuery(function($) { ... })
			if (typeof selectorOrCallback === 'function') {
				// Execute immediately with jQuery mock as argument
				selectorOrCallback(jQueryMock);
				return jQueryMock;
			}
			if (selectorOrCallback === 'form.checkout') {
				return $form;
			}
			if (selectorOrCallback === '#order_review') {
				return { length: 0 };
			}
			if (selectorOrCallback === 'html, body') {
				return { animate: jest.fn() };
			}
			if (selectorOrCallback === document.body) {
				return mockBody;
			}
			return createDefaultMock();
		});
		jQueryMock.blockUI = { defaults: { overlayCSS: {} } };

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		global.window.wc_checkout_params = {
			gateways_with_custom_place_order_button: ['test-gateway'],
		};

		global.window.wc = {
			customPlaceOrderButton: {
				__getForm: jest.fn(() => $form),
				__maybeShow: jest.fn((gatewayId, api) => {
					capturedApi = api;
				}),
				__maybeHideDefaultButtonOnInit: jest.fn(),
				__cleanup: jest.fn(),
			},
		};

		// requiring checkout.js - this will execute the jQuery wrapper
		jest.resetModules();
		require('../checkout');

		// Trigger the event to capture the API via __maybeShow
		// This simulates a gateway registering after page load
		mockBody.trigger('wc_custom_place_order_button_registered', ['test-gateway']);
	});

	afterEach(() => {
		jest.clearAllMocks();
	});

	describe('Terms checkbox validation', () => {
		test('should allow submission after checking terms following a failed validation', async () => {
			// First attempt: terms not checked - should fail
			$termsCheckbox.setChecked(false);
			const firstResult = await capturedApi.validate();
			expect(firstResult.hasError).toBe(true);
			expect($termsRow.addClass).toHaveBeenCalledWith('woocommerce-invalid');

			// clearing the mock history so the expectations are clearer.
			$termsRow.removeClass.mockClear();
			$termsRow.addClass.mockClear();

			// pretending the user checked the terms checkbox
			$termsCheckbox.setChecked(true);

			// Second attempt: should pass on first try (not require double-click)
			const secondResult = await capturedApi.validate();

			// Should have cleared the invalid state first
			expect($termsRow.removeClass).toHaveBeenCalledWith('woocommerce-invalid');
			// Should NOT have re-added the invalid class
			expect($termsRow.addClass).not.toHaveBeenCalledWith('woocommerce-invalid');
			// Should pass validation
			expect(secondResult.hasError).toBe(false);
		});
	});
});
