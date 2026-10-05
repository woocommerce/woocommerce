import {
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
	type MockedFunction,
} from 'vitest';

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
vi.mock( '@woocommerce/block-data', () => {
	const mock = {
		checkoutStore: 'wc/store/checkout',
		paymentStore: 'wc/store/payment',
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
		useEditorContext: vi.fn(),
		noticeContexts: {
			EXPRESS_PAYMENTS: 'wc/express-payment',
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
vi.mock( '@woocommerce/settings', () => {
	const mock = {
		CURRENT_USER_IS_ADMIN: true,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockUseSelect = useSelect as MockedFunction< typeof useSelect >;
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
		( useEditorContext as Mock ).mockReturnValue( {
			isEditor: false,
		} );
		render( <CheckoutExpressPayment /> );
		expect( screen.getByTestId( 'notices' ) ).toBeInTheDocument();
	} );
	it( 'should render StoreNoticesContainer when in editor and user is admin', () => {
		( useEditorContext as Mock ).mockReturnValue( {
			isEditor: true,
		} );
		render( <CheckoutExpressPayment /> );
		expect( screen.getByTestId( 'notices' ) ).toBeInTheDocument();
	} );
} );
