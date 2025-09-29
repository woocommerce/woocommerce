/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { useEditorContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import CheckoutExpressPayment from '../checkout-express-payment';

jest.mock( '@woocommerce/block-data', () => ( {
	checkoutStore: 'wc/store/checkout',
	paymentStore: 'wc/store/payment',
} ) );

jest.mock( '@woocommerce/base-context', () => ( {
	useEditorContext: jest.fn(),
	noticeContexts: {
		EXPRESS_PAYMENTS: 'wc/express-payment',
	},
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	Title: jest.fn( ( { children, className, headingLevel } ) => (
		<div
			data-testid="title"
			className={ className }
			data-heading-level={ headingLevel }
		>
			{ children }
		</div>
	) ),
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

jest.mock( '@woocommerce/settings', () => ( {
	CURRENT_USER_IS_ADMIN: false,
} ) );

const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;

describe( 'CheckoutExpressPayment', () => {
	describe( 'No registered express payment methods', () => {
		beforeEach( () => {
			( useEditorContext as jest.Mock ).mockReturnValue( {
				isEditor: false,
			} );

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

		it( 'should render null when not in editor and user is not admin', () => {
			const { container } = render( <CheckoutExpressPayment /> );

			expect( container ).toBeEmptyDOMElement();
		} );

		it( 'should render StoreNoticesContainer when in editor and user is not admin', () => {
			( useEditorContext as jest.Mock ).mockReturnValue( {
				isEditor: true,
			} );

			render( <CheckoutExpressPayment /> );

			expect( screen.getByTestId( 'notices' ) ).toBeInTheDocument();
			expect( screen.getByTestId( 'notices' ) ).toHaveAttribute(
				'data-context',
				'wc/express-payment'
			);
		} );
	} );

	describe( 'Registered but no valid express payment methods', () => {
		beforeEach( () => {
			( useEditorContext as jest.Mock ).mockReturnValue( {
				isEditor: false,
			} );

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
			const { container } = render( <CheckoutExpressPayment /> );
			expect( container ).toBeEmptyDOMElement();
		} );

		it( 'should render StoreNoticesContainer when in editor', () => {
			( useEditorContext as jest.Mock ).mockReturnValue( {
				isEditor: true,
			} );

			render( <CheckoutExpressPayment /> );

			expect( screen.getByTestId( 'notices' ) ).toBeInTheDocument();
			expect( screen.getByTestId( 'notices' ) ).toHaveAttribute(
				'data-context',
				'wc/express-payment'
			);
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

		it( 'should render Express Checkout title', () => {
			render( <CheckoutExpressPayment /> );

			expect(
				screen.getByText( /Express Checkout/ )
			).toBeInTheDocument();
		} );

		it( 'should render ExpressPaymentMethods component', () => {
			render( <CheckoutExpressPayment /> );

			expect(
				screen.getByTestId( 'express-payment-methods' )
			).toBeInTheDocument();
		} );

		it( 'should render continue rule', () => {
			render( <CheckoutExpressPayment /> );

			expect(
				screen.getByText( 'Or continue below' )
			).toBeInTheDocument();
		} );

		it( 'should render StoreNoticesContainer for express payments', () => {
			render( <CheckoutExpressPayment /> );

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
			render( <CheckoutExpressPayment /> );

			const expressPaymentContainer = document.querySelector(
				'.wc-block-components-express-payment--checkout'
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

			render( <CheckoutExpressPayment /> );

			const expressPaymentContainer = document.querySelector(
				'.wc-block-components-express-payment--checkout'
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

			render( <CheckoutExpressPayment /> );

			const expressPaymentContainer = document.querySelector(
				'.wc-block-components-express-payment--checkout'
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
		it( 'should render skeleton loading state for title when not initialized', () => {
			mockUseSelect.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: false,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {
					stripe: { name: 'stripe' },
					paypal: { name: 'paypal' },
				},
			} );

			render( <CheckoutExpressPayment /> );

			const titleContainer = screen.getByTestId( 'title' );
			const titleSkeleton = screen.getAllByLabelText(
				'Loading express payment area…'
			);

			expect( titleContainer ).toBeInTheDocument();
			expect( titleSkeleton ).toHaveLength( 1 );
		} );

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

			render( <CheckoutExpressPayment /> );

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

			render( <CheckoutExpressPayment /> );

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

			render( <CheckoutExpressPayment /> );

			const buttonSkeletons = screen.getAllByLabelText(
				'Loading express payment method…'
			);

			expect( buttonSkeletons ).toHaveLength( 3 ); // 3 skeleton buttons
		} );
	} );
} );
