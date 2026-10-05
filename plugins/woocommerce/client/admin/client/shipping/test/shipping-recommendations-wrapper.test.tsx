import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ShippingRecommendations } from '../shipping-recommendations-wrapper';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
		useDispatch: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/element', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/element' ) ),
		Suspense: () => <div>WooCommerce Shipping</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
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
		( useSelect as Mock ).mockImplementation( ( fn ) =>
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
		( useSelect as Mock ).mockImplementation( ( fn ) =>
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
	it( 'should not render when user is not allowed', () => {
		( useSelect as Mock ).mockImplementation( ( fn ) =>
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
