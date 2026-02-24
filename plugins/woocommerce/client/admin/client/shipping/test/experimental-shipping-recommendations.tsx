/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ShippingRecommendations from '../experimental-shipping-recommendations';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );
jest.mock( '../../settings-recommendations/dismissable-list', () => ( {
	DismissableList: ( ( { children } ) => children ) as React.FC,
	DismissableListHeading: ( ( { children } ) => children ) as React.FC,
} ) );
jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: () => null,
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
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );
jest.mock( '~/utils/features', () => ( {
	isFeatureEnabled: jest.fn(),
} ) );

const defaultSelectReturn = {
	getActivePlugins: () => [],
	getInstalledPlugins: () => [],
	getSettings: () => ( {
		general: {
			woocommerce_default_country: 'US',
		},
	} ),
	getProfileItems: () => ( {} ),
	hasFinishedResolution: jest.fn(),
	getOption: jest.fn(),
};

const mockSelectForCountry = (
	countryCode: string,
	activePlugins: string[] = [],
	overrides: Record< string, unknown > = {}
) => {
	( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
		fn( () => ( {
			...defaultSelectReturn,
			getActivePlugins: () => activePlugins,
			getSettings: () => ( {
				general: {
					woocommerce_default_country: countryCode,
				},
			} ),
			...overrides,
		} ) )
	);
};

describe( 'ShippingRecommendations', () => {
	beforeEach( () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( { ...defaultSelectReturn } ) )
		);
		( useDispatch as jest.Mock ).mockReturnValue( {
			installAndActivatePlugins: () => Promise.resolve(),
			installPlugins: () => Promise.resolve(),
			activatePlugins: () => Promise.resolve(),
			createSuccessNotice: () => null,
		} );
	} );

	describe( 'country-based filtering', () => {
		it( 'should show WooCommerce Shipping and ShipStation for US', () => {
			mockSelectForCountry( 'US' );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).toBeInTheDocument();
			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
			expect(
				screen.queryByText( 'Packlink PRO' )
			).not.toBeInTheDocument();
		} );

		it( 'should show only ShipStation for CA', () => {
			mockSelectForCountry( 'CA' );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).not.toBeInTheDocument();
			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
		} );

		it( 'should show only Packlink PRO for FR', () => {
			mockSelectForCountry( 'FR' );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'ShipStation' )
			).not.toBeInTheDocument();
			expect( screen.queryByText( 'Packlink PRO' ) ).toBeInTheDocument();
		} );

		it( 'should show ShipStation and Packlink PRO for DE', () => {
			mockSelectForCountry( 'DE' );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).not.toBeInTheDocument();
			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
			expect( screen.queryByText( 'Packlink PRO' ) ).toBeInTheDocument();
		} );

		it( 'should show ShipStation and Packlink PRO for GB', () => {
			mockSelectForCountry( 'GB' );
			render( <ShippingRecommendations /> );

			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
			expect( screen.queryByText( 'Packlink PRO' ) ).toBeInTheDocument();
		} );

		it( 'should show only ShipStation for AU', () => {
			mockSelectForCountry( 'AU' );
			render( <ShippingRecommendations /> );

			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
			expect(
				screen.queryByText( 'Packlink PRO' )
			).not.toBeInTheDocument();
		} );

		it( 'should show only ShipStation for NZ', () => {
			mockSelectForCountry( 'NZ' );
			render( <ShippingRecommendations /> );

			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
		} );

		it.each( [ 'ES', 'IT', 'NL', 'AT', 'BE' ] )(
			'should show only Packlink PRO for %s',
			( country ) => {
				mockSelectForCountry( country );
				render( <ShippingRecommendations /> );

				expect(
					screen.queryByText( 'Packlink PRO' )
				).toBeInTheDocument();
				expect(
					screen.queryByText( 'ShipStation' )
				).not.toBeInTheDocument();
				expect(
					screen.queryByText( 'WooCommerce Shipping' )
				).not.toBeInTheDocument();
			}
		);

		it( 'should not render recommendations for unsupported countries', () => {
			mockSelectForCountry( 'JP' );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).not.toBeInTheDocument();
			expect(
				screen.queryByText( 'ShipStation' )
			).not.toBeInTheDocument();
			expect(
				screen.queryByText( 'Packlink PRO' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'active plugin filtering', () => {
		it( 'should not show WooCommerce Shipping when it is already active', () => {
			mockSelectForCountry( 'US', [ 'woocommerce-shipping' ] );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).not.toBeInTheDocument();
			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
		} );

		it( 'should not show ShipStation when it is already active', () => {
			mockSelectForCountry( 'US', [
				'woocommerce-shipstation-integration',
			] );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'ShipStation' )
			).not.toBeInTheDocument();
		} );

		it( 'should not show Packlink PRO when it is already active', () => {
			mockSelectForCountry( 'DE', [ 'packlink-pro-shipping' ] );
			render( <ShippingRecommendations /> );

			expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
			expect(
				screen.queryByText( 'Packlink PRO' )
			).not.toBeInTheDocument();
		} );

		it( 'should not render recommendations when all extensions for a country are active', () => {
			mockSelectForCountry( 'US', [
				'woocommerce-shipping',
				'woocommerce-shipstation-integration',
			] );
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).not.toBeInTheDocument();
			expect(
				screen.queryByText( 'ShipStation' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'digital products only', () => {
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
	} );

	describe( 'WooCommerce Shipping item', () => {
		it( 'should render WC Shipping when not installed', () => {
			render( <ShippingRecommendations /> );

			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).toBeInTheDocument();
		} );

		it( 'should trigger event settings_shipping_recommendation_visit_marketplace_click when clicking the WooCommerce Marketplace link', () => {
			render( <ShippingRecommendations /> );

			fireEvent.click(
				screen.getByText( 'the WooCommerce Marketplace' )
			);

			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_shipping_recommendation_visit_marketplace_click',
				{}
			);
		} );

		it( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', async () => {
			const { isFeatureEnabled } = jest.requireMock( '~/utils/features' );
			const originalLocation = global.window.location;
			( isFeatureEnabled as jest.Mock ).mockReturnValue( true );

			const mockLocation = {
				href: 'test',
			} as Location;

			mockLocation.href = 'test';
			Object.defineProperty( global.window, 'location', {
				value: mockLocation,
			} );

			render( <ShippingRecommendations /> );

			fireEvent.click(
				screen.getByText( 'the WooCommerce Marketplace' )
			);

			expect( mockLocation.href ).toContain(
				'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=shipping'
			);

			Object.defineProperty( global.window, 'location', {
				value: originalLocation,
			} );
		} );
	} );

	describe( 'plugin installation', () => {
		it( 'allows to install WooCommerce Shipping', async () => {
			const installPluginsMock = jest.fn().mockResolvedValue( undefined );
			const successNoticeMock = jest.fn();
			( useDispatch as jest.Mock ).mockReturnValue( {
				installAndActivatePlugins: jest
					.fn()
					.mockResolvedValue( undefined ),
				installPlugins: installPluginsMock,
				activatePlugins: jest.fn().mockResolvedValue( undefined ),
				createSuccessNotice: successNoticeMock,
			} );
			mockSelectForCountry( 'US', [
				'woocommerce-shipstation-integration',
			] );
			render( <ShippingRecommendations /> );

			userEvent.click( screen.getByText( 'Install' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_shipping_recommendation_setup_click',
				{
					plugin: 'woocommerce-shipping',
					action: 'install',
				}
			);
			expect( installPluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-shipping',
			] );
			await waitFor( () => {
				expect( successNoticeMock ).toHaveBeenCalledWith(
					'WooCommerce Shipping is installed!',
					expect.anything()
				);
			} );
		} );

		it( 'allows to install ShipStation', async () => {
			const installPluginsMock = jest.fn().mockResolvedValue( undefined );
			const successNoticeMock = jest.fn();
			( useDispatch as jest.Mock ).mockReturnValue( {
				installAndActivatePlugins: jest
					.fn()
					.mockResolvedValue( undefined ),
				installPlugins: installPluginsMock,
				activatePlugins: jest.fn().mockResolvedValue( undefined ),
				createSuccessNotice: successNoticeMock,
			} );
			mockSelectForCountry( 'CA' );
			render( <ShippingRecommendations /> );

			userEvent.click( screen.getByText( 'Install' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_shipping_recommendation_setup_click',
				{
					plugin: 'woocommerce-shipstation-integration',
					action: 'install',
				}
			);
			expect( installPluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-shipstation-integration',
			] );
			await waitFor( () => {
				expect( successNoticeMock ).toHaveBeenCalledWith(
					'ShipStation is installed!',
					expect.anything()
				);
			} );
		} );

		it( 'allows to install Packlink PRO', async () => {
			const installPluginsMock = jest.fn().mockResolvedValue( undefined );
			const successNoticeMock = jest.fn();
			( useDispatch as jest.Mock ).mockReturnValue( {
				installAndActivatePlugins: jest
					.fn()
					.mockResolvedValue( undefined ),
				installPlugins: installPluginsMock,
				activatePlugins: jest.fn().mockResolvedValue( undefined ),
				createSuccessNotice: successNoticeMock,
			} );
			mockSelectForCountry( 'FR' );
			render( <ShippingRecommendations /> );

			userEvent.click( screen.getByText( 'Install' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_shipping_recommendation_setup_click',
				{
					plugin: 'packlink-pro-shipping',
					action: 'install',
				}
			);
			expect( installPluginsMock ).toHaveBeenCalledWith( [
				'packlink-pro-shipping',
			] );
			await waitFor( () => {
				expect( successNoticeMock ).toHaveBeenCalledWith(
					'Packlink PRO is installed!',
					expect.anything()
				);
			} );
		} );
	} );

	describe( 'plugin activation (installed but not active)', () => {
		it( 'shows Activate button for WooCommerce Shipping when installed but not active', () => {
			mockSelectForCountry( 'US', [], {
				getInstalledPlugins: () => [ 'woocommerce-shipping' ],
			} );
			render( <ShippingRecommendations /> );

			const buttons = screen.getAllByText( 'Activate' );
			expect( buttons ).toHaveLength( 1 );
			expect(
				screen.queryByText( 'WooCommerce Shipping' )
			).toBeInTheDocument();
		} );

		it( 'shows Activate button for ShipStation when installed but not active', () => {
			mockSelectForCountry( 'CA', [], {
				getInstalledPlugins: () => [
					'woocommerce-shipstation-integration',
				],
			} );
			render( <ShippingRecommendations /> );

			expect( screen.getByText( 'Activate' ) ).toBeInTheDocument();
			expect( screen.queryByText( 'Install' ) ).not.toBeInTheDocument();
		} );

		it( 'shows Activate button for Packlink PRO when installed but not active', () => {
			mockSelectForCountry( 'FR', [], {
				getInstalledPlugins: () => [ 'packlink-pro-shipping' ],
			} );
			render( <ShippingRecommendations /> );

			expect( screen.getByText( 'Activate' ) ).toBeInTheDocument();
			expect( screen.queryByText( 'Install' ) ).not.toBeInTheDocument();
		} );

		it( 'shows activated notice for WooCommerce Shipping when activating installed plugin', async () => {
			const activatePluginsMock = jest
				.fn()
				.mockResolvedValue( undefined );
			const successNoticeMock = jest.fn();
			( useDispatch as jest.Mock ).mockReturnValue( {
				installAndActivatePlugins: jest
					.fn()
					.mockResolvedValue( undefined ),
				installPlugins: jest.fn().mockResolvedValue( undefined ),
				activatePlugins: activatePluginsMock,
				createSuccessNotice: successNoticeMock,
			} );
			mockSelectForCountry(
				'US',
				[ 'woocommerce-shipstation-integration' ],
				{
					getInstalledPlugins: () => [ 'woocommerce-shipping' ],
				}
			);
			render( <ShippingRecommendations /> );

			userEvent.click( screen.getByText( 'Activate' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_shipping_recommendation_setup_click',
				{
					plugin: 'woocommerce-shipping',
					action: 'activate',
				}
			);
			expect( activatePluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-shipping',
			] );
			await waitFor( () => {
				expect( successNoticeMock ).toHaveBeenCalledWith(
					'WooCommerce Shipping activated!',
					expect.anything()
				);
			} );
		} );

		it( 'shows activated notice for ShipStation when activating installed plugin', async () => {
			const activatePluginsMock = jest
				.fn()
				.mockResolvedValue( undefined );
			const successNoticeMock = jest.fn();
			( useDispatch as jest.Mock ).mockReturnValue( {
				installAndActivatePlugins: jest
					.fn()
					.mockResolvedValue( undefined ),
				installPlugins: jest.fn().mockResolvedValue( undefined ),
				activatePlugins: activatePluginsMock,
				createSuccessNotice: successNoticeMock,
			} );
			mockSelectForCountry( 'CA', [], {
				getInstalledPlugins: () => [
					'woocommerce-shipstation-integration',
				],
			} );
			render( <ShippingRecommendations /> );

			userEvent.click( screen.getByText( 'Activate' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_shipping_recommendation_setup_click',
				{
					plugin: 'woocommerce-shipstation-integration',
					action: 'activate',
				}
			);
			expect( activatePluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-shipstation-integration',
			] );
			await waitFor( () => {
				expect( successNoticeMock ).toHaveBeenCalledWith(
					'ShipStation activated!',
					expect.anything()
				);
			} );
		} );

		it( 'shows activated notice for Packlink PRO when activating installed plugin', async () => {
			const activatePluginsMock = jest
				.fn()
				.mockResolvedValue( undefined );
			const successNoticeMock = jest.fn();
			( useDispatch as jest.Mock ).mockReturnValue( {
				installAndActivatePlugins: jest
					.fn()
					.mockResolvedValue( undefined ),
				installPlugins: jest.fn().mockResolvedValue( undefined ),
				activatePlugins: activatePluginsMock,
				createSuccessNotice: successNoticeMock,
			} );
			mockSelectForCountry( 'FR', [], {
				getInstalledPlugins: () => [ 'packlink-pro-shipping' ],
			} );
			render( <ShippingRecommendations /> );

			userEvent.click( screen.getByText( 'Activate' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_shipping_recommendation_setup_click',
				{
					plugin: 'packlink-pro-shipping',
					action: 'activate',
				}
			);
			expect( activatePluginsMock ).toHaveBeenCalledWith( [
				'packlink-pro-shipping',
			] );
			await waitFor( () => {
				expect( successNoticeMock ).toHaveBeenCalledWith(
					'Packlink PRO activated!',
					expect.anything()
				);
			} );
		} );
	} );
} );
