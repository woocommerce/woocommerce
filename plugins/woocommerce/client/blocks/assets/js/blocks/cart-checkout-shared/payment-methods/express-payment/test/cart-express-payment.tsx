import {
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type MockedFunction,
} from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { useStoreCart } from '@woocommerce/base-context';
import { previewCart } from '@woocommerce/resource-previews';
/**
 * Internal dependencies
 */
import CartExpressPayment from '../cart-express-payment';
vi.mock( '@woocommerce/block-data', () => {
	const mock = {
		checkoutStore: 'wc/store/checkout',
		paymentStore: 'wc/store/payment',
		cartStore: 'wc/store/cart',
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
			EXPRESS_PAYMENTS: 'wc/express-payment',
		},
		useStoreCart: vi.fn( () => ( {
			hasPendingItemsOperations: false,
			cartIsLoading: false,
			isLoadingRates: false,
		} ) ),
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
		StoreNoticesContainer: vi.fn( ( { context } ) => (
			<div data-testid="notices" data-context={ context }>
				Store Notices
			</div>
		) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/base-components/skeleton', () => {
	const mock = {
		Skeleton: vi.fn( ( { width, height, ariaMessage } ) => (
			<div
				data-testid="skeleton"
				data-width={ width }
				data-height={ height }
				{ ...( ariaMessage
					? {
							'aria-label': ariaMessage,
					  }
					: {} ) }
			>
				{ ariaMessage || 'Loading...' }
			</div>
		) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../express-payment-methods', () => {
	const mock = vi.fn( () => (
		<div data-testid="express-payment-methods">Express Payment Methods</div>
	) );
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '@wordpress/data', () => {
	const mock = {
		useSelect: vi.fn(),
		dispatch: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockUseSelect = useSelect as MockedFunction< typeof useSelect >;
const mockUseStoreCart = useStoreCart as MockedFunction< typeof useStoreCart >;
const baseMockUseStoreCart = {
	billingAddress: previewCart.billing_address,
	billingData: previewCart.billing_address,
	cartCoupons: [],
	cartErrors: previewCart.errors,
	cartFees: previewCart.fees,
	cartHasCalculatedShipping: false,
	cartIsLoading: false,
	cartItemErrors: [],
	cartItems: [],
	cartItemsCount: 0,
	cartItemsWeight: 0,
	cartNeedsPayment: true,
	cartNeedsShipping: previewCart.needs_shipping,
	cartTotals: previewCart.totals,
	crossSellsProducts: [],
	extensions: {},
	hasPendingItemsOperations: false,
	isLoadingRates: false,
	paymentMethods: [],
	paymentRequirements: [],
	receiveCart: vi.fn(),
	receiveCartContents: vi.fn(),
	shippingAddress: previewCart.shipping_address,
	shippingRates: previewCart.shipping_rates,
};
describe( 'CartExpressPayment', () => {
	describe( 'No registered express payment methods', () => {
		beforeEach( () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {},
			} );
			mockUseStoreCart.mockReturnValue( baseMockUseStoreCart );
		} );
		it( 'should render null', () => {
			const { container } = render( <CartExpressPayment /> );
			expect( container ).toBeEmptyDOMElement();
		} );
	} );
	describe( 'Registered but no valid express payment methods', () => {
		beforeEach( () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {},
				// No available methods
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
					// Has registered methods
					paypal: {
						name: 'paypal',
					},
				},
			} );
			mockUseStoreCart.mockReturnValue( baseMockUseStoreCart );
		} );
		it( 'should render null', () => {
			const { container } = render( <CartExpressPayment /> );
			expect( container ).toBeEmptyDOMElement();
		} );
	} );
	describe( 'Express payment methods available and initialized', () => {
		beforeEach( () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
					paypal: {
						name: 'paypal',
					},
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
					paypal: {
						name: 'paypal',
					},
				},
			} );
			mockUseStoreCart.mockReturnValue( baseMockUseStoreCart );
		} );
		it( 'should render ExpressPaymentMethods component', () => {
			render( <CartExpressPayment /> );
			expect(
				screen.getByTestId( 'express-payment-methods' )
			).toBeInTheDocument();
		} );
		it( 'should render StoreNoticesContainer for express payments', () => {
			render( <CartExpressPayment /> );
			expect( screen.getByTestId( 'notices' ) ).toBeInTheDocument();
			expect( screen.getByTestId( 'notices' ) ).toHaveAttribute(
				'data-context',
				'wc/express-payment'
			);
		} );
	} );
	describe( 'Accessibility states', () => {
		it( 'should add conditional accessibility attributes when express payment method is active', () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: true,
				registeredExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
			} );
			mockUseStoreCart.mockReturnValue( baseMockUseStoreCart );
			render( <CartExpressPayment /> );
			const expressPaymentContainer = document.querySelector(
				'.wc-block-components-express-payment--cart'
			);

			// Always present attributes
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-disabled',
				'true'
			);
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-live',
				'polite'
			);

			// Conditional attributes (only present when express payment method is active)
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-busy',
				'true'
			);
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-label',
				expect.stringContaining( 'Processing express checkout' )
			);

			// Should have disabled class
			expect( expressPaymentContainer ).toHaveClass(
				'wc-block-components-express-payment--disabled'
			);
		} );
		it( 'should not have conditional accessibility attributes when an express payment method is not active', () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
			} );
			mockUseStoreCart.mockReturnValue( baseMockUseStoreCart );
			render( <CartExpressPayment /> );
			const expressPaymentContainer = document.querySelector(
				'.wc-block-components-express-payment--cart'
			);

			// Always present attributes
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-disabled',
				'false'
			);
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-live',
				'polite'
			);

			// Conditional attributes should NOT be present when an express payment method is not active
			expect( expressPaymentContainer ).not.toHaveAttribute(
				'aria-busy'
			);
			expect( expressPaymentContainer ).not.toHaveAttribute(
				'aria-label'
			);

			// Should not have disabled class
			expect( expressPaymentContainer ).not.toHaveClass(
				'wc-block-components-express-payment--disabled'
			);
		} );
	} );
	describe( 'Loading states', () => {
		it( 'should render 1 skeleton button when there are pending cart operations if express payment method is not active', () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
			} );
			mockUseStoreCart.mockReturnValue( {
				...baseMockUseStoreCart,
				hasPendingItemsOperations: true,
				cartIsLoading: false,
			} );
			render( <CartExpressPayment /> );
			const buttonSkeletons = screen.getAllByLabelText(
				'Loading express payment method…'
			);
			expect( buttonSkeletons ).toHaveLength( 1 ); // 1 skeleton buttons
			expect(
				screen.queryByTestId( 'express-payment-methods' )
			).not.toBeInTheDocument();
		} );
		it( 'should not render skeleton buttons when there are pending cart operations and express payment method is active', () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: true,
				registeredExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
				},
			} );
			mockUseStoreCart.mockReturnValue( {
				...baseMockUseStoreCart,
				hasPendingItemsOperations: true,
				cartIsLoading: false,
			} );
			render( <CartExpressPayment /> );
			const buttonSkeletons = screen.queryAllByLabelText(
				'Loading express payment method…'
			);
			expect( buttonSkeletons ).toHaveLength( 0 ); // No skeleton buttons should be rendered when express payment method is active
			expect(
				screen.queryByTestId( 'express-payment-methods' )
			).toBeInTheDocument();
		} );
		it( 'should render 3 skeleton buttons when 3 buttons are available', () => {
			mockUseSelect.mockReturnValueOnce( {
				availableExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
					paypal: {
						name: 'paypal',
					},
					applepay: {
						name: 'applepay',
					},
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: {
						name: 'stripe',
					},
					paypal: {
						name: 'paypal',
					},
					applepay: {
						name: 'applepay',
					},
				},
			} );
			// Pending operations trigger skeleton buttons
			mockUseStoreCart.mockReturnValue( {
				...baseMockUseStoreCart,
				hasPendingItemsOperations: true,
				cartIsLoading: false,
			} );
			render( <CartExpressPayment /> );
			const buttonSkeletons = screen.getAllByLabelText(
				'Loading express payment method…'
			);
			expect( buttonSkeletons ).toHaveLength( 3 ); // 3 skeleton buttons
		} );
	} );
} );
