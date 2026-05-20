/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AbandonedCartRecoveryRecommendations } from '../abandoned-cart-recovery-recommendations-wrapper';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	useUser: jest.fn(),
} ) );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	Suspense: () => <div>Abandoned cart recovery recommendations</div>,
} ) );

const eligibleSelectReturn = {
	getOption: () => 'yes',
	hasStartedResolution: () => true,
	hasFinishedResolution: () => true,
};

describe( 'AbandonedCartRecoveryRecommendations wrapper', () => {
	beforeEach( () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => eligibleSelectReturn )
		);
		( useUser as jest.Mock ).mockReturnValue( {
			currentUserCan: () => true,
		} );
	} );

	it( 'should not render outside wc-settings', () => {
		const { queryByText } = render(
			<AbandonedCartRecoveryRecommendations
				page="wc-admin"
				tab="email"
				section="wc_email_customer_abandoned_cart_recovery"
			/>
		);

		expect(
			queryByText( 'Abandoned cart recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render on a non-email settings tab', () => {
		const { queryByText } = render(
			<AbandonedCartRecoveryRecommendations
				page="wc-settings"
				tab="shipping"
				section="wc_email_customer_abandoned_cart_recovery"
			/>
		);

		expect(
			queryByText( 'Abandoned cart recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render on the email list page (no section)', () => {
		const { queryByText } = render(
			<AbandonedCartRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section={ undefined }
			/>
		);

		expect(
			queryByText( 'Abandoned cart recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render on a different email section', () => {
		const { queryByText } = render(
			<AbandonedCartRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_completed_order"
			/>
		);

		expect(
			queryByText( 'Abandoned cart recovery recommendations' )
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
			<AbandonedCartRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_abandoned_cart_recovery"
			/>
		);

		expect(
			queryByText( 'Abandoned cart recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when the user lacks install_plugins capability', () => {
		( useUser as jest.Mock ).mockReturnValue( {
			currentUserCan: () => false,
		} );

		const { queryByText } = render(
			<AbandonedCartRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_abandoned_cart_recovery"
			/>
		);

		expect(
			queryByText( 'Abandoned cart recovery recommendations' )
		).not.toBeInTheDocument();
	} );

	it( 'should render on the abandoned-cart-recovery email section page when eligible', () => {
		const { getByText } = render(
			<AbandonedCartRecoveryRecommendations
				page="wc-settings"
				tab="email"
				section="wc_email_customer_abandoned_cart_recovery"
			/>
		);

		expect(
			getByText( 'Abandoned cart recovery recommendations' )
		).toBeInTheDocument();
	} );
} );
