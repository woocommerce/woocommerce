/**
 * Tests for the enhanced checkout actions block with custom payment method place order buttons.
 */

import { render, screen } from '@testing-library/react';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { usePaymentMethodInterface } from '@woocommerce/base-context';
import CheckoutActionsBlock from '../block';

// Mock the hooks
jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useCheckoutSubmit: jest.fn(),
} ) );

jest.mock( '@woocommerce/base-context', () => ( {
	usePaymentMethodInterface: jest.fn(),
	noticeContexts: {
		CHECKOUT_ACTIONS: 'checkout-actions',
	},
} ) );

// Mock the components
jest.mock( '@woocommerce/base-components/cart-checkout', () => ( {
	PlaceOrderButton: ( { label, fullWidth, showPrice, priceSeparator } ) => (
		<button
			data-testid="place-order-button"
			data-full-width={ fullWidth }
			data-show-price={ showPrice }
			data-price-separator={ priceSeparator }
		>
			{ label }
		</button>
	),
	ReturnToCartButton: ( { href, children } ) => (
		<a href={ href } data-testid="return-to-cart-button">
			{ children }
		</a>
	),
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	StoreNoticesContainer: ( { context } ) => (
		<div data-testid="store-notices-container" data-context={ context } />
	),
} ) );

jest.mock( '@woocommerce/blocks-checkout', () => ( {
	applyCheckoutFilter: jest.fn( ( { defaultValue } ) => defaultValue ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( ( key ) => {
		if ( key === 'page-123' ) return '/cart';
		return false;
	} ),
} ) );

// Mock the slotfill
jest.mock( '../../checkout-order-summary-block/slotfills', () => ( {
	CheckoutOrderSummarySlot: () => <div data-testid="checkout-order-summary-slot" />,
} ) );

describe( 'CheckoutActionsBlock', () => {
	const defaultProps = {
		cartPageId: 123,
		showReturnToCart: false,
		className: '',
		placeOrderButtonLabel: 'Place Order',
		priceSeparator: '·',
		returnToCartButtonLabel: 'Return to Cart',
	};

	const mockPaymentMethodInterface = {
		activePaymentMethod: 'test-payment-method',
		billing: {},
		cartData: {},
		checkoutStatus: {},
		components: {},
		emitResponse: {},
		eventRegistration: {},
		onSubmit: jest.fn(),
		paymentStatus: {},
		setExpressPaymentError: jest.fn(),
		shippingData: {},
		shippingStatus: {},
		shouldSavePayment: false,
	};

	beforeEach( () => {
		jest.clearAllMocks();
		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: null,
			paymentMethodPlaceOrderButton: null,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );
		usePaymentMethodInterface.mockReturnValue( mockPaymentMethodInterface );
	} );

	it( 'renders default place order button when no custom payment method button is provided', () => {
		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const placeOrderButton = screen.getByTestId( 'place-order-button' );
		expect( placeOrderButton ).toBeInTheDocument();
		expect( placeOrderButton ).toHaveTextContent( 'Place Order' );
		expect( placeOrderButton ).toHaveAttribute( 'data-full-width', 'true' );
		expect( placeOrderButton ).toHaveAttribute( 'data-show-price', 'false' );
		expect( placeOrderButton ).toHaveAttribute( 'data-price-separator', '·' );
	} );

	it( 'renders custom payment method place order button when provided', () => {
		const CustomPlaceOrderButton = ( props ) => {
			// Extract only the props we need for the button
			const { onSubmit, ...buttonProps } = props;
			return (
				<button data-testid="custom-place-order-button" onClick={ onSubmit }>
					Custom Place Order
				</button>
			);
		};

		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Custom Label',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const customButton = screen.getByTestId( 'custom-place-order-button' );
		expect( customButton ).toBeInTheDocument();
		expect( customButton ).toHaveTextContent( 'Custom Place Order' );

		// Should not render the default place order button
		expect( screen.queryByTestId( 'place-order-button' ) ).not.toBeInTheDocument();
	} );

	it( 'passes payment method interface props to custom place order button', () => {
		const CustomPlaceOrderButton = jest.fn( ( props ) => {
			// Extract only the props we need for the button
			const { onSubmit, ...buttonProps } = props;
			return (
				<button data-testid="custom-place-order-button" onClick={ onSubmit }>
					Custom Place Order
				</button>
			);
		} );

		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Custom Label',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		render( <CheckoutActionsBlock { ...defaultProps } /> );

		expect( CustomPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( mockPaymentMethodInterface ),
			expect.anything()
		);
	} );

	it( 'uses payment method button label when available', () => {
		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Pay with Test Method',
			paymentMethodPlaceOrderButton: null,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const placeOrderButton = screen.getByTestId( 'place-order-button' );
		expect( placeOrderButton ).toHaveTextContent( 'Pay with Test Method' );
	} );

	it( 'falls back to block attribute label when payment method label is not available', () => {
		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: null,
			paymentMethodPlaceOrderButton: null,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		render( <CheckoutActionsBlock { ...defaultProps } placeOrderButtonLabel="Custom Block Label" /> );

		const placeOrderButton = screen.getByTestId( 'place-order-button' );
		expect( placeOrderButton ).toHaveTextContent( 'Custom Block Label' );
	} );

	it( 'renders return to cart button when showReturnToCart is true', () => {
		render( <CheckoutActionsBlock { ...defaultProps } showReturnToCart={ true } /> );

		const returnToCartButton = screen.getByTestId( 'return-to-cart-button' );
		expect( returnToCartButton ).toBeInTheDocument();
		expect( returnToCartButton ).toHaveAttribute( 'href', '/cart' );
		expect( returnToCartButton ).toHaveTextContent( 'Return to Cart' );
	} );

	it( 'applies correct fullWidth prop based on showReturnToCart', () => {
		render( <CheckoutActionsBlock { ...defaultProps } showReturnToCart={ true } /> );

		const placeOrderButton = screen.getByTestId( 'place-order-button' );
		expect( placeOrderButton ).toHaveAttribute( 'data-full-width', 'false' );
	} );

	it( 'renders with price style when className includes is-style-with-price', () => {
		// Mock console.error to suppress CSS parsing warnings
		const originalError = console.error;
		console.error = jest.fn();
		
		render( <CheckoutActionsBlock { ...defaultProps } className="is-style-with-price" /> );

		const placeOrderButton = screen.getByTestId( 'place-order-button' );
		expect( placeOrderButton ).toHaveAttribute( 'data-show-price', 'true' );
		
		// Restore console.error
		console.error = originalError;
	} );

	it( 'renders PaymentMethodPlaceOrderButtonContainer with correct attributes', () => {
		const CustomPlaceOrderButton = ( props ) => {
			// Extract only the props we need for the button
			const { onSubmit, ...buttonProps } = props;
			return (
				<button data-testid="custom-place-order-button" onClick={ onSubmit }>
					Custom Place Order
				</button>
			);
		};

		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Custom Label',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const container = screen.getByTestId( 'custom-place-order-button' ).closest( '.wc-block-checkout__payment-method-button' );
		expect( container ).toBeInTheDocument();
		expect( container ).toHaveClass( 'wc-block-checkout__actions_row' );
		expect( container ).toHaveClass( 'wc-block-checkout__payment-method-button' );
		expect( container ).toHaveAttribute( 'role', 'button' );
		expect( container ).toHaveAttribute( 'tabIndex', '0' );
	} );

	it( 'renders store notices container with correct context', () => {
		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const noticesContainer = screen.getByTestId( 'store-notices-container' );
		expect( noticesContainer ).toBeInTheDocument();
		expect( noticesContainer ).toHaveAttribute( 'data-context', 'checkout-actions' );
	} );

	it( 'renders checkout order summary slot', () => {
		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const orderSummarySlot = screen.getByTestId( 'checkout-order-summary-slot' );
		expect( orderSummarySlot ).toBeInTheDocument();
	} );
} );
