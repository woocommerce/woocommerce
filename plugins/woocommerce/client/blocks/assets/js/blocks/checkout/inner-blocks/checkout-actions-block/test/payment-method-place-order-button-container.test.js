/**
 * Tests for the PaymentMethodPlaceOrderButtonContainer component.
 */

import { render, screen } from '@testing-library/react';
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
	PlaceOrderButton: () => <button data-testid="place-order-button">Place Order</button>,
	ReturnToCartButton: () => <a data-testid="return-to-cart-button">Return to Cart</a>,
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	StoreNoticesContainer: () => <div data-testid="store-notices-container" />,
} ) );

jest.mock( '@woocommerce/blocks-checkout', () => ( {
	applyCheckoutFilter: jest.fn( ( { defaultValue } ) => defaultValue ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( () => false ),
} ) );

// Mock the slotfill
jest.mock( '../../checkout-order-summary-block/slotfills', () => ( {
	CheckoutOrderSummarySlot: () => <div data-testid="checkout-order-summary-slot" />,
} ) );

import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { usePaymentMethodInterface } from '@woocommerce/base-context';

describe( 'PaymentMethodPlaceOrderButtonContainer', () => {
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

	it( 'renders container with correct CSS classes', () => {
		const CustomPlaceOrderButton = ( { onClick, ...props } ) => (
			<button data-testid="custom-place-order-button" onClick={ onClick }>
				Custom Place Order
			</button>
		);

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
	} );

	it( 'renders container with correct accessibility attributes', () => {
		const CustomPlaceOrderButton = ( { onClick, ...props } ) => (
			<button data-testid="custom-place-order-button" onClick={ onClick }>
				Custom Place Order
			</button>
		);

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
		
		expect( container ).toHaveAttribute( 'role', 'button' );
		expect( container ).toHaveAttribute( 'tabIndex', '0' );
	} );

	it( 'renders children inside the container', () => {
		const CustomPlaceOrderButton = ( { onClick, ...props } ) => (
			<button data-testid="custom-place-order-button" onClick={ onClick }>
				Custom Place Order
			</button>
		);

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
	} );

	it( 'does not render container when no custom payment method button is provided', () => {
		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: null,
			paymentMethodPlaceOrderButton: null,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const container = document.querySelector( '.wc-block-checkout__payment-method-button' );
		expect( container ).not.toBeInTheDocument();
	} );

	it( 'renders container with multiple children', () => {
		const CustomPlaceOrderButton = ( { onClick, ...props } ) => (
			<div data-testid="custom-place-order-button">
				<button onClick={ onClick }>Custom Place Order</button>
				<span>Additional content</span>
			</div>
		);

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
		
		const customButton = screen.getByTestId( 'custom-place-order-button' );
		expect( customButton ).toBeInTheDocument();
		expect( customButton ).toHaveTextContent( 'Custom Place Order' );
		expect( customButton ).toHaveTextContent( 'Additional content' );
	} );

	it( 'maintains container structure when payment method button changes', () => {
		const CustomPlaceOrderButton1 = ( { onClick, ...props } ) => (
			<button data-testid="custom-place-order-button-1" onClick={ onClick }>
				Custom Place Order 1
			</button>
		);

		const CustomPlaceOrderButton2 = ( { onClick, ...props } ) => (
			<button data-testid="custom-place-order-button-2" onClick={ onClick }>
				Custom Place Order 2
			</button>
		);

		// First render with first custom button
		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Custom Label 1',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton1,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		const { rerender } = render( <CheckoutActionsBlock { ...defaultProps } /> );

		let container = screen.getByTestId( 'custom-place-order-button-1' ).closest( '.wc-block-checkout__payment-method-button' );
		expect( container ).toBeInTheDocument();

		// Second render with second custom button
		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Custom Label 2',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton2,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		rerender( <CheckoutActionsBlock { ...defaultProps } /> );

		container = screen.getByTestId( 'custom-place-order-button-2' ).closest( '.wc-block-checkout__payment-method-button' );
		expect( container ).toBeInTheDocument();
		expect( container ).toHaveClass( 'wc-block-checkout__actions_row' );
		expect( container ).toHaveClass( 'wc-block-checkout__payment-method-button' );
		expect( container ).toHaveAttribute( 'role', 'button' );
		expect( container ).toHaveAttribute( 'tabIndex', '0' );
	} );

	it( 'handles container with complex nested structure', () => {
		const ComplexCustomButton = ( { onClick, ...props } ) => (
			<div data-testid="complex-custom-button">
				<div className="button-wrapper">
					<button onClick={ onClick }>Complex Custom Button</button>
				</div>
				<div className="additional-info">
					<span>Payment info</span>
					<span>Security badge</span>
				</div>
			</div>
		);

		useCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Complex Custom Label',
			paymentMethodPlaceOrderButton: ComplexCustomButton,
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );

		render( <CheckoutActionsBlock { ...defaultProps } /> );

		const container = screen.getByTestId( 'complex-custom-button' ).closest( '.wc-block-checkout__payment-method-button' );
		expect( container ).toBeInTheDocument();
		
		const complexButton = screen.getByTestId( 'complex-custom-button' );
		expect( complexButton ).toBeInTheDocument();
		expect( complexButton ).toHaveTextContent( 'Complex Custom Button' );
		expect( complexButton ).toHaveTextContent( 'Payment info' );
		expect( complexButton ).toHaveTextContent( 'Security badge' );
	} );
} );
