/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CheckoutRecoveryRecommendations } from '../checkout-recovery-recommendations-wrapper';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	Suspense: () => <div>Checkout recovery recommendations</div>,
} ) );

const eligibleSelectReturn = {
	getOption: () => 'yes',
	getCurrentUser: () => ( {
		is_super_admin: true,
	} ),
	hasStartedResolution: () => true,
	hasFinishedResolution: () => true,
};

describe( 'CheckoutRecoveryRecommendations wrapper', () => {
	beforeEach( () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => eligibleSelectReturn )
		);
	} );

	it( 'should not render outside wc-settings', () => {
		const { queryByText } = render(
			<CheckoutRecoveryRecommendations
				page="wc-admin"
				tab="email"
				section="wc_email_customer_checkout_recovery"
			/>
		);

		expect(
			queryByText( 'Checkout recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render on a non-email settings tab', () => {
		const { queryByText } = render(
			<CheckoutRecoveryRecommendations
				page="wc-settings"
				tab="shipping"
				section="wc_email_customer_checkout_recovery"
			/>
		);

		expect(
			queryByText( 'Checkout recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render on the email list page (no section)', () => {
		const { queryByText } = render(
			<CheckoutRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section={ undefined }
			/>
		);

		expect(
			queryByText( 'Checkout recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render on a different email section', () => {
		const { queryByText } = render(
			<CheckoutRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_completed_order"
			/>
		);

		expect(
			queryByText( 'Checkout recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when marketplace suggestions are disabled', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				...eligibleSelectReturn,
				getOption: () => 'no',
			} ) )
		);

		const { queryByText } = render(
			<CheckoutRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_checkout_recovery"
			/>
		);

		expect(
			queryByText( 'Checkout recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when the user lacks install_plugins capability', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				...eligibleSelectReturn,
				getCurrentUser: () => ( {
					is_super_admin: false,
					capabilities: {},
				} ),
			} ) )
		);

		const { queryByText } = render(
			<CheckoutRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_checkout_recovery"
			/>
		);

		expect(
			queryByText( 'Checkout recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should render on the checkout-recovery email section page when eligible', () => {
		const { getByText } = render(
			<CheckoutRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_checkout_recovery"
			/>
		);

		expect(
			getByText( 'Checkout recovery recommendations' )
		).toBeInTheDocument();
	} );
} );
