/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { useCampaignTypes, useRecommendedChannels } from '~/marketing/hooks';
import { CreateNewCampaignModal } from './CreateNewCampaignModal';

jest.mock( '@woocommerce/components', () => {
	const originalModule = jest.requireActual( '@woocommerce/components' );

	return {
		__esModule: true,
		...originalModule,
		Spinner: () => <div data-testid="spinner">Spinner</div>,
	};
} );

jest.mock( '~/marketing/hooks', () => ( {
	useCampaignTypes: jest.fn(),
	useRecommendedChannels: jest.fn(),
	useRegisteredChannels: jest.fn( () => ( {} ) ),
	useInstalledPluginsWithoutChannels: jest.fn( () => ( {} ) ),
} ) );

const google = {
	id: 'google-ads',
	icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
	name: 'Google Ads',
	description:
		'Boost your product listings with a campaign that is automatically optimized to meet your goals.',
	createUrl:
		'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/create',
	channelName: 'Google for WooCommerce',
	channelSlug: 'google-listings-and-ads',
};

const pinterest = {
	title: 'Pinterest for WooCommerce',
	description:
		'Grow your business on Pinterest! Use this official plugin to allow shoppers to Pin products while browsing your store, track conversions, and advertise on Pinterest.',
	url: 'https://woocommerce.com/products/pinterest-for-woocommerce/?utm_source=marketingtab&utm_medium=product&utm_campaign=wcaddons',
	direct_install: true,
	icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/pinterest.svg',
	product: 'pinterest-for-woocommerce',
	plugin: 'pinterest-for-woocommerce/pinterest-for-woocommerce.php',
	categories: [ 'marketing' ],
	subcategories: [ { slug: 'sales-channels', name: 'Sales channels' } ],
	tags: [
		{
			slug: 'built-by-woocommerce',
			name: 'Built by WooCommerce',
		},
	],
	show_extension_promotions: true,
};

describe( 'CreateNewCampaignModal component', () => {
	it( 'renders new campaign types with recommended channels', async () => {
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			data: [ google ],
		} );
		( useRecommendedChannels as jest.Mock ).mockReturnValue( {
			data: [ pinterest ],
		} );
		render( <CreateNewCampaignModal onRequestClose={ () => {} } /> );

		expect( screen.getByText( 'Google Ads' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				'Boost your product listings with a campaign that is automatically optimized to meet your goals.'
			)
		).toBeInTheDocument();

		// Click button to expand recommended channels section.
		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Add channels for other campaign types',
			} )
		);

		expect(
			screen.getByText( 'Pinterest for WooCommerce' )
		).toBeInTheDocument();
	} );

	it( 'does not render recommended channels section when there are no recommended channels', async () => {
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			data: [ google ],
		} );
		( useRecommendedChannels as jest.Mock ).mockReturnValue( {
			data: [],
		} );
		render( <CreateNewCampaignModal onRequestClose={ () => {} } /> );

		// The expand button should not be there.
		expect(
			screen.queryByRole( 'button', {
				name: 'Add channels for other campaign types',
			} )
		).not.toBeInTheDocument();
	} );
} );
