/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ShippingRecommendations from '../experimental-shipping-recommendations';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );
jest.mock( '../../settings-recommendations/dismissable-list', () => ( {
	DismissableList: ( ( { children } ) => children ) as React.FC,
	DismissableListHeading: ( ( { children } ) => children ) as React.FC,
} ) );
jest.mock( '@woocommerce/admin-layout', () => {
	const mockContext = {
		layoutPath: [ 'home' ],
		layoutString: 'home',
		extendLayout: () => {},
		isDescendantOf: () => false,
	};
	return {
		...jest.requireActual( '@woocommerce/admin-layout' ),
		useLayoutContext: jest.fn().mockReturnValue( mockContext ),
		useExtendLayout: jest.fn().mockReturnValue( mockContext ),
	};
} );

const defaultSelectReturn = {
	getActivePlugins: () => [],
	getInstalledPlugins: () => [],
	isJetpackConnected: () => false,
	getSettings: () => ( {
		general: {
			woocommerce_default_country: 'US',
		},
	} ),
	getProfileItems: () => ( {} ),
	hasFinishedResolution: jest.fn(),
	getOption: jest.fn(),
};

describe( 'ShippingRecommendations', () => {
	beforeEach( () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( { ...defaultSelectReturn } ) )
		);
	} );

	it( 'should not render when WCS is already installed and Jetpack is connected', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				...defaultSelectReturn,
				getActivePlugins: () => [ 'woocommerce-services' ],
				isJetpackConnected: () => true,
			} ) )
		);
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
	} );

	[
		[ 'woocommerce-shipping' ],
		[ 'woocommerce-tax' ],
		[ 'woocommerce-shipping', 'woocommerce-tax' ],
	].forEach( ( activePlugins ) => {
		it( `should not render if the following plugins are active: ${ JSON.stringify(
			activePlugins
		) }`, () => {
			( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
				fn( () => ( {
					...defaultSelectReturn,
					getActivePlugins: () => activePlugins,
				} ) )
			);

			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).not.toBeInTheDocument();
		} );
	} );

	it( 'should not render when store location is not US', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				...defaultSelectReturn,
				getSettings: () => ( {
					general: {
						woocommerce_default_country: 'JP',
					},
				} ),
			} ) )
		);
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when store sells digital products only', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				...defaultSelectReturn,
				getProfileItems: () => ( {
					product_types: [ 'downloads' ],
				} ),
			} ) )
		);
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
	} );

	it( 'should render WCS when not installed', () => {
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();
	} );
} );
