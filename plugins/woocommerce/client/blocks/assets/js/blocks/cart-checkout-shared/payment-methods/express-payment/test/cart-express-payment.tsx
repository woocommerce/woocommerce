/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CartExpressPayment from '../cart-express-payment';

jest.mock( '@woocommerce/block-data', () => ( {
	checkoutStore: 'wc/store/checkout',
	paymentStore: 'wc/store/payment',
} ) );

jest.mock( '@woocommerce/base-context', () => ( {
	noticeContexts: {
		EXPRESS_PAYMENTS: 'wc/express-payment',
	},
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	StoreNoticesContainer: jest.fn( ( { context } ) => (
		<div data-testid="notices" data-context={ context }>
			Store Notices
		</div>
	) ),
} ) );

jest.mock( '@woocommerce/base-components/skeleton', () => ( {
	Skeleton: jest.fn( ( { width, height, ariaMessage } ) => (
		<div
			data-testid="skeleton"
			data-width={ width }
			data-height={ height }
			{ ...( ariaMessage ? { 'aria-label': ariaMessage } : {} ) }
		>
			{ ariaMessage || 'Loading...' }
		</div>
	) ),
} ) );

jest.mock( '../../express-payment-methods', () =>
	jest.fn( () => (
		<div data-testid="express-payment-methods">Express Payment Methods</div>
	) )
);

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	dispatch: jest.fn(),
} ) );

const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;

describe( 'CartExpressPayment', () => {
	describe( 'No registered express payment methods', () => {
		beforeEach( () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {},
			} );
		} );

		it( 'should render null', () => {
			const { container } = render( <CartExpressPayment /> );

			expect( container ).toBeEmptyDOMElement();
		} );
	} );

	describe( 'Registered but no valid express payment methods', () => {
		beforeEach( () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {}, // No available methods
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' }, // Has registered methods
					paypal: { name: 'paypal' },
				},
			} );
		} );

		it( 'should render null when not in editor and user is not admin', () => {
			const { container } = render( <CartExpressPayment /> );
			expect( container ).toBeEmptyDOMElement();
		} );
	} );

	describe( 'Express payment methods available and initialized', () => {
		beforeEach( () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
					paypal: { name: 'paypal' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
					paypal: { name: 'paypal' },
				},
			} );
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

	describe( 'Processing states', () => {
		it( 'should add conditional accessibility attributes when isProcessing', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: true,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
			} );
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

			// Conditional attributes (only present when processing)
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-busy',
				'true'
			);
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-label',
				expect.stringContaining( 'Processing express checkout' )
			);
		} );

		it( 'should add conditional accessibility attributes when isAfterProcessing', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: true,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
			} );
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

			// Conditional attributes (only present when processing)
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-busy',
				'true'
			);
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-label',
				expect.stringContaining( 'Processing express checkout' )
			);
		} );

		it( 'should add conditional accessibility attributes when isBeforeProcessing', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: true,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
			} );
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

			// Conditional attributes (only present when processing)
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-busy',
				'true'
			);
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-label',
				expect.stringContaining( 'Processing express checkout' )
			);
		} );

		it( 'should add conditional accessibility attributes when isComplete without error', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: true,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
			} );
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

			// Conditional attributes (only present when processing)
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-busy',
				'true'
			);
			expect( expressPaymentContainer ).toHaveAttribute(
				'aria-label',
				expect.stringContaining( 'Processing express checkout' )
			);
		} );

		it( 'should not add conditional accessibility attributes when isComplete with error', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: true,
				hasError: true,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
			} );
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

			// Conditional attributes should NOT be present when complete with error
			expect( expressPaymentContainer ).not.toHaveAttribute(
				'aria-busy'
			);
			expect( expressPaymentContainer ).not.toHaveAttribute(
				'aria-label'
			);
		} );

		it( 'should add conditional accessibility attributes when express payment method is active', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: true,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
			} );

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

		it( 'should not have conditional accessibility attributes when not processing', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
			} );

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

			// Conditional attributes should NOT be present when not processing
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
		it( 'should render 1 skeleton button when calculating a partial update if express payment method is not active', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: true,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
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

		it( 'should not render skeleton buttons when calculating a partial update and express payment method is active', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: true,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: true,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
				},
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
				isCalculating: true,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {
					stripe: { name: 'stripe' },
					paypal: { name: 'paypal' },
					applepay: { name: 'applepay' },
				},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
					paypal: { name: 'paypal' },
					applepay: { name: 'applepay' },
				},
			} );

			render( <CartExpressPayment /> );

			const buttonSkeletons = screen.getAllByLabelText(
				'Loading express payment method…'
			);

			expect( buttonSkeletons ).toHaveLength( 3 ); // 3 skeleton buttons
		} );
	} );
} );
