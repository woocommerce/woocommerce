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
	StoreNoticesContainer: jest.fn( ( { context } ) => (
		<div data-testid="notices" data-context={ context }>
			Store Notices
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
	CURRENT_USER_IS_ADMIN: true,
} ) );

const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;

describe( 'CheckoutExpressPayment for Admin', () => {
	beforeEach( () => {
		mockUseSelect
			.mockReturnValueOnce( {
				isCalculating: false,
				isProcessing: false,
				isAfterProcessing: false,
				isBeforeProcessing: false,
				isComplete: false,
				hasError: false,
			} )
			.mockReturnValueOnce( {
				availableExpressPaymentMethods: {},
				expressPaymentMethodsInitialized: true,
				isExpressPaymentMethodActive: false,
				registeredExpressPaymentMethods: {},
			} );
	} );

	it( 'should render StoreNoticesContainer when not in editor and user is admin', () => {
		( useEditorContext as jest.Mock ).mockReturnValue( {
			isEditor: false,
		} );
		render( <CheckoutExpressPayment /> );

		expect( screen.getByTestId( 'notices' ) ).toBeInTheDocument();
	} );

	it( 'should render StoreNoticesContainer when in editor and user is admin', () => {
		( useEditorContext as jest.Mock ).mockReturnValue( {
			isEditor: true,
		} );

		render( <CheckoutExpressPayment /> );

		expect( screen.getByTestId( 'notices' ) ).toBeInTheDocument();
	} );
} );
