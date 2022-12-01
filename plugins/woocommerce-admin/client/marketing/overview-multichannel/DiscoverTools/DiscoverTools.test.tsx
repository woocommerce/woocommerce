/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useInstalledPlugins } from '../../hooks';
import { useRecommendedPlugins } from './useRecommendedPlugins';
import { DiscoverTools } from './DiscoverTools';

jest.mock( '@woocommerce/components', () => {
	const originalModule = jest.requireActual( '@woocommerce/components' );

	return {
		__esModule: true,
		...originalModule,
		Spinner: () => <div data-testid="spinner">Spinner</div>,
	};
} );

jest.mock( './useRecommendedPlugins', () => ( {
	useRecommendedPlugins: jest.fn(),
} ) );

jest.mock( '../../hooks', () => ( {
	useInstalledPlugins: jest.fn(),
} ) );

describe( 'DiscoverTools component', () => {
	it( 'should render a Spinner when loading is in progress', () => {
		( useRecommendedPlugins as jest.Mock ).mockReturnValue( {
			isInitializing: true,
			isLoading: true,
			plugins: [],
		} );
		( useInstalledPlugins as jest.Mock ).mockReturnValue( {
			loadInstalledPluginsAfterActivation: jest.fn(),
		} );
		render( <DiscoverTools /> );

		expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();
	} );

	it( 'should render message and link when loading is finish and there are no plugins', () => {
		( useRecommendedPlugins as jest.Mock ).mockReturnValue( {
			isInitializing: false,
			isLoading: false,
			plugins: [],
		} );
		( useInstalledPlugins as jest.Mock ).mockReturnValue( {
			loadInstalledPluginsAfterActivation: jest.fn(),
		} );
		render( <DiscoverTools /> );

		expect(
			screen.getByText(
				'Continue to reach the right audiences and promote your products in ways that matter to them with our range of marketing solutions.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Explore more marketing extensions' )
		).toBeInTheDocument();
	} );

	describe( 'With plugins loaded', () => {
		it( 'should render `direct_install: true` plugins with "Install plugin" button', () => {
			( useRecommendedPlugins as jest.Mock ).mockReturnValue( {
				isInitializing: false,
				isLoading: false,
				plugins: [
					{
						title: 'Google Listings and Ads',
						description:
							'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
						url: 'https://woocommerce.com/products/google-listings-and-ads/?utm_source=marketingtab&utm_medium=product&utm_campaign=wcaddons',
						direct_install: true,
						icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
						product: 'google-listings-and-ads',
						plugin: 'google-listings-and-ads/google-listings-and-ads.php',
						categories: [ 'marketing' ],
						subcategories: [
							{
								slug: 'sales-channels',
								name: 'Sales channels',
							},
						],
						tags: [
							{
								slug: 'built-by-woocommerce',
								name: 'Built by WooCommerce',
							},
						],
					},
				],
			} );
			( useInstalledPlugins as jest.Mock ).mockReturnValue( {
				loadInstalledPluginsAfterActivation: jest.fn(),
			} );
			render( <DiscoverTools /> );

			// Assert that we have the "Sales channels" tab, the plugin name, the "Built by WooCommerce" pill, and the "Install plugin" button.
			expect( screen.getByText( 'Sales channels' ) ).toBeInTheDocument();
			expect(
				screen.getByText( 'Google Listings and Ads' )
			).toBeInTheDocument();
			expect(
				screen.getByText( 'Built by WooCommerce' )
			).toBeInTheDocument();
			expect( screen.getByText( 'Install plugin' ) ).toBeInTheDocument();
		} );

		it( 'should render `direct_install: false` plugins with "View details" button', () => {
			( useRecommendedPlugins as jest.Mock ).mockReturnValue( {
				isInitializing: false,
				isLoading: false,
				plugins: [
					{
						title: 'WooCommerce Zapier',
						description:
							'Integrate your WooCommerce store with 5000+ cloud apps and services today. Trusted by 11,000+ users.',
						url: 'https://woocommerce.com/products/woocommerce-zapier/?utm_source=marketingtab&utm_medium=product&utm_campaign=wcaddons',
						direct_install: false,
						icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/zapier.png',
						product: 'woocommerce-zapier',
						plugin: 'woocommerce-zapier/woocommerce-zapier.php',
						categories: [ 'marketing' ],
						subcategories: [
							{
								slug: 'crm',
								name: 'CRM',
							},
						],
						tags: [],
					},
				],
			} );
			( useInstalledPlugins as jest.Mock ).mockReturnValue( {
				loadInstalledPluginsAfterActivation: jest.fn(),
			} );
			render( <DiscoverTools /> );

			// Assert that we have the CRM tab, plugin name, and "View details" button.
			expect( screen.getByText( 'CRM' ) ).toBeInTheDocument();
			expect(
				screen.getByText( 'WooCommerce Zapier' )
			).toBeInTheDocument();
			expect( screen.getByText( 'View details' ) ).toBeInTheDocument();
		} );
	} );
} );
