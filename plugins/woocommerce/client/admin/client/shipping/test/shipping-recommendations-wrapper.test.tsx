/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ShippingRecommendations } from '../shipping-recommendations-wrapper';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	Suspense: () => <div>WooCommerce Shipping</div>,
} ) );

const eligibleSelectReturn = {
	getOption: () => 'yes',
	getCurrentUser: () => ( {
		is_super_admin: true,
	} ),
	hasStartedResolution: () => true,
	hasFinishedResolution: () => true,
};

describe( 'ShippingRecommendations', () => {
	beforeEach( () => {
		window.history.pushState( {}, '', '/' );
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => eligibleSelectReturn )
		);
	} );

	it( 'should not render when section is not empty', () => {
		const { queryByText } = render(
			<ShippingRecommendations
				page="wc-settings"
				tab="shipping"
				section={ 'section' }
				zone_id={ undefined }
			/>
		);

		expect( queryByText( 'WooCommerce Shipping' ) ).not.toBeInTheDocument();
	} );

	it( 'should not render when zone_id is not empty', () => {
		const { queryByText } = render(
			<ShippingRecommendations
				page="wc-settings"
				tab="shipping"
				section={ undefined }
				zone_id={ 'zone_id' }
			/>
		);

		expect( queryByText( 'WooCommerce Shipping' ) ).not.toBeInTheDocument();
	} );

	it( 'should not render when woocommerce_show_marketplace_suggestions is "no"', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				...eligibleSelectReturn,
				getOption: () => 'no',
			} ) )
		);
		const { queryByText } = render(
			<ShippingRecommendations
				page="wc-settings"
				tab="shipping"
				section={ undefined }
				zone_id={ undefined }
			/>
		);
		expect( queryByText( 'WooCommerce Shipping' ) ).not.toBeInTheDocument();
	} );

	it( 'should render the shipping native spike even when marketplace suggestions are disabled', () => {
		window.history.pushState( {}, '', '/?_shipping_native_spike=1' );
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				...eligibleSelectReturn,
				getOption: () => 'no',
			} ) )
		);
		const { getByText } = render(
			<ShippingRecommendations
				page="wc-settings"
				tab="shipping"
				section={ undefined }
				zone_id={ undefined }
			/>
		);

		expect( getByText( 'WooCommerce Shipping' ) ).toBeInTheDocument();
	} );

	it( 'should not render when user is not allowed', () => {
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
			<ShippingRecommendations
				page="wc-settings"
				tab="shipping"
				section={ undefined }
				zone_id={ undefined }
			/>
		);
		expect( queryByText( 'WooCommerce Shipping' ) ).not.toBeInTheDocument();
	} );

	it( 'should render WooCommerce Shipping', async () => {
		const { getByText } = render(
			<ShippingRecommendations
				page="wc-settings"
				tab="shipping"
				section={ undefined }
				zone_id={ undefined }
			/>
		);

		expect( getByText( 'WooCommerce Shipping' ) ).toBeInTheDocument();
	} );
} );
