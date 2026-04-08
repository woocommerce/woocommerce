/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import { server, http, HttpResponse } from '@woocommerce/test-utils/msw';
import {
	ExperimentalOrderMeta,
	ExperimentalDiscountsMeta,
} from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { defaultCartState } from '../../../data/cart/default-state';
import Checkout from '../block';
import Totals from '../inner-blocks/checkout-totals-block/frontend';
import OrderSummary from '../inner-blocks/checkout-order-summary-block/frontend';
import Discount from '../inner-blocks/checkout-order-summary-discount/frontend';

jest.mock( '@woocommerce/editor-components/switch-to-classic-shortcode-button', () => ( {
	__esModule: true,
	default: () => null,
} ) );

jest.mock( '@wordpress/data', () =>
	// eslint-disable-next-line @typescript-eslint/no-var-requires -- Must use require due to Jest mock hoisting
	require( '@woocommerce/blocks-test-utils/mock-editor-store' ).mockWordPressDataWithEditorStore()
);

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useResizeObserver: jest.fn().mockReturnValue( [ null, { width: 0 } ] ),
} ) );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useId: () => 'mock-id',
} ) );

jest.mock( '../context', () => ( {
	...jest.requireActual( '../context' ),
	useCheckoutBlockContext: jest.fn().mockReturnValue( {
		showFormStepNumbers: false,
		cartPageId: 0,
		requireCompanyField: false,
		requirePhoneField: false,
		showApartmentField: false,
		showCompanyField: false,
		showOrderNotes: false,
		showPhoneField: false,
		showPolicyLinks: false,
		showRateAfterTaxName: false,
		showReturnToCart: false,
	} ),
} ) );

global.ResizeObserver = jest.fn().mockImplementation( () => ( {
	observe: jest.fn(),
	unobserve: jest.fn(),
	disconnect: jest.fn(),
} ) );

global.IntersectionObserver = jest.fn().mockImplementation( () => ( {
	observe: jest.fn(),
	unobserve: jest.fn(),
	disconnect: jest.fn(),
} ) );

const OrderMetaConsumer = ( { cart, extensions, context } ) => (
	<div data-testid="order-meta-consumer">
		<span data-testid="order-meta-context">{ context }</span>
		<span data-testid="order-meta-extensions">
			{ JSON.stringify( extensions ) }
		</span>
		{ cart && (
			<span data-testid="order-meta-has-cart">true</span>
		) }
		{ cart && ! ( 'receiveCart' in cart ) && (
			<span data-testid="order-meta-no-receive-cart">true</span>
		) }
	</div>
);

const DiscountsMetaConsumer = ( { cart, extensions, context } ) => (
	<div data-testid="discounts-meta-consumer">
		<span data-testid="discounts-meta-context">{ context }</span>
		<span data-testid="discounts-meta-extensions">
			{ JSON.stringify( extensions ) }
		</span>
		{ cart && ! ( 'receiveCart' in cart ) && (
			<span data-testid="discounts-meta-no-receive-cart">true</span>
		) }
	</div>
);

const CheckoutBlock = () => (
	<Checkout attributes={ {} }>
		<Totals>
			<OrderSummary>
				<Discount />
			</OrderSummary>
		</Totals>
	</Checkout>
);

describe( 'Checkout SlotFills', () => {
	beforeAll( () => {
		registerPlugin( 'checkout-slot-fills-test', {
			render: () => (
				<>
					<ExperimentalOrderMeta>
						<OrderMetaConsumer />
					</ExperimentalOrderMeta>
					<ExperimentalDiscountsMeta>
						<DiscountsMetaConsumer />
					</ExperimentalDiscountsMeta>
				</>
			),
			scope: 'woocommerce-checkout',
		} );
	} );

	beforeEach( () => {
		server.use(
			http.get( '/wc/store/v1/cart', () => {
				return HttpResponse.json( previewCart );
			} )
		);

		act( () => {
			dispatch( cartStore ).invalidateResolutionForStore();
			dispatch( cartStore ).receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( () => {
		server.resetHandlers();
	} );

	it( 'passes cart data and checkout context to ExperimentalOrderMeta fills', async () => {
		render( <CheckoutBlock /> );

		await waitFor( () => {
			expect(
				screen.getByTestId( 'order-meta-consumer' )
			).toBeInTheDocument();
		} );

		expect( screen.getByTestId( 'order-meta-context' ) ).toHaveTextContent(
			'woocommerce/checkout'
		);
		expect(
			screen.getByTestId( 'order-meta-has-cart' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'order-meta-no-receive-cart' )
		).toBeInTheDocument();
	} );

	it( 'passes cart data and checkout context to ExperimentalDiscountsMeta fills', async () => {
		render( <CheckoutBlock /> );

		await waitFor( () => {
			expect(
				screen.getByTestId( 'discounts-meta-consumer' )
			).toBeInTheDocument();
		} );

		expect(
			screen.getByTestId( 'discounts-meta-context' )
		).toHaveTextContent( 'woocommerce/checkout' );
		expect(
			screen.getByTestId( 'discounts-meta-no-receive-cart' )
		).toBeInTheDocument();
	} );

	it( 'passes extension data to slot fill consumers', async () => {
		server.use(
			http.get( '/wc/store/v1/cart', () => {
				return HttpResponse.json( {
					...previewCart,
					extensions: { 'my-extension': { key: 'value' } },
				} );
			} )
		);

		render( <CheckoutBlock /> );

		await waitFor( () => {
			expect(
				screen.getByTestId( 'order-meta-extensions' )
			).toHaveTextContent( '{"my-extension":{"key":"value"}}' );
		} );

		expect(
			screen.getByTestId( 'discounts-meta-extensions' )
		).toHaveTextContent( '{"my-extension":{"key":"value"}}' );
	} );
} );
