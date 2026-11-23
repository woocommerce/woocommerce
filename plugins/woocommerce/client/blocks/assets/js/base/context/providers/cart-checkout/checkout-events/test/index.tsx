/**
 * External dependencies
 */
import { act, render, waitFor } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import {
	cartStore,
	checkoutStore,
	paymentStore,
} from '@woocommerce/block-data';
import { checkoutEvents } from '@woocommerce/blocks-checkout-events';
import { previewCart } from '@woocommerce/resource-previews';
import { server, http, HttpResponse } from '@woocommerce/test-utils/msw';

/**
 * Internal dependencies
 */
import { CheckoutEventsProvider } from '../index';
import { EditorProvider } from '../../../editor-context';

// Mock the registry functions. Like the real registry, they return the same objects on every call, so a re-render does
// not re-run the provider's payment method check.
jest.mock( '@woocommerce/blocks-registry', () => {
	const paymentMethods = {};
	const expressPaymentMethods = {
		stripe: {
			name: 'stripe',
			title: 'Stripe',
			description: 'Pay with Stripe',
			gatewayId: 'stripe',
			supports: {
				style: [ 'height', 'borderRadius' ],
			},
		},
		paypal: {
			name: 'paypal',
			title: 'PayPal',
			description: 'Pay with PayPal',
			gatewayId: 'paypal',
			supports: {
				style: [],
			},
		},
	};
	return {
		getPaymentMethods: jest.fn( () => paymentMethods ),
		getExpressPaymentMethods: jest.fn( () => expressPaymentMethods ),
	};
} );

describe( 'CheckoutEventsContext', () => {
	let mockSetRegisteredExpressPaymentMethods: jest.Mock;

	beforeEach( () => {
		jest.clearAllMocks();
		dispatch( checkoutStore ).__internalSetIdle();

		// Mock the payment store dispatch action
		mockSetRegisteredExpressPaymentMethods = jest.fn();
		jest.spyOn(
			dispatch( paymentStore ),
			'__internalSetRegisteredExpressPaymentMethods'
		).mockImplementation( mockSetRegisteredExpressPaymentMethods );
	} );

	it( '__internalSetRegisteredExpressPaymentMethods is called when component renders', () => {
		render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);

		expect( mockSetRegisteredExpressPaymentMethods ).toHaveBeenCalledWith( {
			stripe: {
				name: 'stripe',
				title: 'Stripe',
				description: 'Pay with Stripe',
				gatewayId: 'stripe',
				supportsStyle: [ 'height', 'borderRadius' ],
			},
			paypal: {
				name: 'paypal',
				title: 'PayPal',
				description: 'Pay with PayPal',
				gatewayId: 'paypal',
				supportsStyle: [],
			},
		} );
	} );

	it( 'onCheckoutValidation observers are called when the checkout is in the "beforeProcessing" state', async () => {
		const callback = jest.fn();
		const callback2 = jest.fn();
		checkoutEvents.onCheckoutValidation( callback );
		checkoutEvents.onCheckoutValidation( callback2 );
		const { rerender } = render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		await act( () =>
			dispatch( checkoutStore ).__internalSetBeforeProcessing()
		);
		rerender(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		expect( callback ).toHaveBeenCalled();
		expect( callback2 ).toHaveBeenCalled();
	} );
	it( 'onCheckoutSuccess observers are called when the checkout is in the "afterProcessing" state and no error exists, onCheckoutFail observers are not called', async () => {
		const successCallback = jest.fn();
		const successCallback2 = jest.fn();
		const failCallback = jest.fn();
		checkoutEvents.onCheckoutSuccess( successCallback );
		checkoutEvents.onCheckoutSuccess( successCallback2 );
		checkoutEvents.onCheckoutFail( failCallback );
		const { rerender } = render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		await act( () =>
			dispatch( checkoutStore ).__internalSetAfterProcessing()
		);
		rerender(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		expect( successCallback ).toHaveBeenCalled();
		expect( successCallback2 ).toHaveBeenCalled();
		expect( failCallback ).not.toHaveBeenCalled();
	} );
	it( 'onCheckoutSuccess observers are not called when the checkout is in the "afterProcessing" state and an error exists, onCheckoutFail observers are called', async () => {
		const successCallback = jest.fn();
		const successCallback2 = jest.fn();
		const failCallback = jest.fn();
		checkoutEvents.onCheckoutSuccess( successCallback );
		checkoutEvents.onCheckoutSuccess( successCallback2 );
		checkoutEvents.onCheckoutFail( failCallback );
		const { rerender } = render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		await act( () => {
			dispatch( checkoutStore ).__internalSetHasError( true );
			dispatch( checkoutStore ).__internalSetAfterProcessing();
		} );
		rerender(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);
		expect( successCallback ).not.toHaveBeenCalled();
		expect( successCallback2 ).not.toHaveBeenCalled();
		expect( failCallback ).toHaveBeenCalled();
	} );
} );

describe( 'CheckoutEventsProvider payment method checks', () => {
	let updateAvailablePaymentMethods: jest.SpyInstance;
	let releaseCart: () => void;

	/**
	 * Holds the cart request until releaseCart() is called. The cart store requests the cart as soon as its
	 * resolution is invalidated, so the handler is in place before that.
	 */
	const holdCartRequest = () => {
		const cartReleased = new Promise< void >( ( resolve ) => {
			releaseCart = resolve;
		} );
		server.use(
			http.get( '/wc/store/v1/cart', async () => {
				await cartReleased;
				return HttpResponse.json( previewCart );
			} )
		);
		act( () => {
			dispatch( cartStore ).invalidateResolutionForStore();
		} );
	};

	beforeAll( () => {
		// Tell the cart store the shopper has items in their cart, so it waits for the cart request instead of
		// treating the cart as empty and marking it loaded when the window load event fires.
		document.cookie = 'woocommerce_items_in_cart=1';
	} );

	afterAll( () => {
		document.cookie =
			'woocommerce_items_in_cart=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
	} );

	beforeEach( () => {
		releaseCart = () => undefined;
		// The cart store also runs this action once the cart loads, so tests count calls from the moment they render.
		updateAvailablePaymentMethods = jest
			.spyOn(
				dispatch( paymentStore ),
				'__internalUpdateAvailablePaymentMethods'
			)
			.mockImplementation( jest.fn() );
	} );

	afterEach( () => {
		releaseCart();
		updateAvailablePaymentMethods.mockRestore();
	} );

	it( 'waits for the cart before checking which payment methods the shopper can use', async () => {
		holdCartRequest();

		render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);

		expect( updateAvailablePaymentMethods ).not.toHaveBeenCalled();

		releaseCart();

		// The cart store runs the first check once the cart arrives. It may be debounced by a second.
		await waitFor(
			() => expect( updateAvailablePaymentMethods ).toHaveBeenCalled(),
			{ timeout: 2000 }
		);
	} );

	it( 'checks payment methods on mount when the cart has already loaded', () => {
		act( () => {
			dispatch( cartStore ).receiveCart( previewCart );
			dispatch( cartStore ).finishResolution( 'getCartData' );
		} );
		updateAvailablePaymentMethods.mockClear();

		render(
			<CheckoutEventsProvider redirectUrl="local">
				<div />
			</CheckoutEventsProvider>
		);

		expect( updateAvailablePaymentMethods ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'checks payment methods in the editor without waiting for the cart', () => {
		holdCartRequest();

		render(
			<EditorProvider currentPostId={ 1 }>
				<CheckoutEventsProvider redirectUrl="local">
					<div />
				</CheckoutEventsProvider>
			</EditorProvider>
		);

		expect( updateAvailablePaymentMethods ).toHaveBeenCalledTimes( 1 );
	} );
} );
