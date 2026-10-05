import { beforeEach, describe, expect, it, vi } from 'vitest';
const { mockPlaceOrderButton, mockUseCheckoutSubmit, mockUseSelect } =
	vi.hoisted( () => {
		const mockPlaceOrderButton = vi.fn(
			( { label, CustomButtonComponent } ) => {
				if ( CustomButtonComponent ) {
					return <CustomButtonComponent />;
				}
				return <button>{ label }</button>;
			}
		);
		const mockUseCheckoutSubmit = vi.fn();
		const mockUseSelect = vi.fn();
		return {
			mockPlaceOrderButton,
			mockUseCheckoutSubmit,
			mockUseSelect,
		};
	} );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';
vi.mock( '@woocommerce/base-components/cart-checkout', () => {
	const mock = {
		PlaceOrderButton: ( props ) => mockPlaceOrderButton( props ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/base-context/hooks', () => {
	const mock = {
		useCheckoutSubmit: () => mockUseCheckoutSubmit(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', () => {
	const mock = {
		useSelect: () => mockUseSelect(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/block-data', () => {
	const mock = {
		paymentStore: 'wc/store/payment',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/settings', () => {
	const mock = {
		getSetting: vi.fn( () => '' ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/base-context', () => {
	const mock = {
		noticeContexts: {
			CHECKOUT_ACTIONS: 'wc/checkout/checkout-actions',
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/blocks-components', () => {
	const mock = {
		StoreNoticesContainer: () => null,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/blocks-checkout', () => {
	const mock = {
		applyCheckoutFilter: ( { defaultValue } ) => defaultValue,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/block-settings', () => {
	const mock = {
		CART_URL: '/cart',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../checkout-order-summary-block/slotfills', () => {
	const mock = {
		CheckoutOrderSummarySlot: () => null,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../checkout-actions-block/constants', () => {
	const mock = {
		defaultPlaceOrderButtonLabel: 'Place Order',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const defaultProps = {
	cartPageId: 1,
	showReturnToCart: false,
	placeOrderButtonLabel: 'Place Order',
	priceSeparator: '·',
	returnToCartButtonLabel: 'Return to Cart',
};
const CustomPlaceOrderButton = () => <button>Custom Button</button>;
describe( 'Checkout Actions Block', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		mockPlaceOrderButton.mockClear();
	} );
	it( 'does not pass CustomButtonComponent to PlaceOrderButton when a saved token is active', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: '',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton,
		} );
		mockUseSelect.mockReturnValue( 'saved-token-123' );
		render( <Block { ...defaultProps } /> );
		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				CustomButtonComponent: undefined,
			} )
		);
		expect( screen.queryByText( 'Place Order' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Custom Button' ) ).not.toBeInTheDocument();
	} );
	it( 'passes CustomButtonComponent to PlaceOrderButton when no saved token is active', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: '',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton,
		} );
		mockUseSelect.mockReturnValue( null );
		render( <Block { ...defaultProps } /> );
		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				CustomButtonComponent: CustomPlaceOrderButton,
			} )
		);
		expect( screen.queryByText( 'Place Order' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Custom Button' ) ).toBeInTheDocument();
	} );
	it( 'passes undefined CustomButtonComponent when payment method does not provide one', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: '',
			paymentMethodPlaceOrderButton: undefined,
		} );
		mockUseSelect.mockReturnValue( null );
		render( <Block { ...defaultProps } /> );
		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				CustomButtonComponent: undefined,
			} )
		);
		expect( screen.queryByText( 'Place Order' ) ).toBeInTheDocument();
	} );
	it( 'uses payment method button label when provided', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Pay with Card',
			paymentMethodPlaceOrderButton: undefined,
		} );
		mockUseSelect.mockReturnValue( null );
		render( <Block { ...defaultProps } /> );
		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				label: 'Pay with Card',
			} )
		);
		expect( screen.queryByText( 'Pay with Card' ) ).toBeInTheDocument();
	} );
} );
