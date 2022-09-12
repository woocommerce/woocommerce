/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
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

describe( 'DiscoverTools component', () => {
	it( 'should render a Spinner when loading is in progress', () => {
		( useRecommendedPlugins as jest.Mock ).mockReturnValue( {
			isLoading: true,
			plugins: [],
		} );
		render( <DiscoverTools /> );

		fireEvent.click( screen.getByLabelText( 'Expand' ) );

		expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();
	} );

	it( 'should render message and link when loading is finish and there are no plugins', () => {
		( useRecommendedPlugins as jest.Mock ).mockReturnValue( {
			isLoading: false,
			plugins: [],
		} );
		render( <DiscoverTools /> );

		fireEvent.click( screen.getByLabelText( 'Expand' ) );

		expect(
			screen.getByText(
				'Continue to reach the right audiences and promote your products in ways that matter to them with our range of marketing solutions.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Explore more marketing extensions' )
		).toBeInTheDocument();
	} );

	it( 'should render tabs with plugins', () => {
		( useRecommendedPlugins as jest.Mock ).mockReturnValue( {
			isLoading: false,
			plugins: [
				{
					title: 'Google Listings and Ads',
					description:
						'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
					url: 'https://woocommerce.com/products/google-listings-and-ads/?utm_source=marketingtab&utm_medium=product&utm_campaign=wcaddons',
					icon: 'https://woocommerce.test/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/google.svg',
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
		render( <DiscoverTools /> );

		fireEvent.click( screen.getByLabelText( 'Expand' ) );

		// Assert that we have the "Sales channels" tab.
		expect( screen.getByText( 'Sales channels' ) ).toBeInTheDocument();

		// Assert that we have the plugin displayed.
		expect(
			screen.getByText( 'Google Listings and Ads' )
		).toBeInTheDocument();

		// Assert that we can see the "Built by WooCommerce" pill.
		expect(
			screen.getByText( 'Built by WooCommerce' )
		).toBeInTheDocument();
	} );
} );
