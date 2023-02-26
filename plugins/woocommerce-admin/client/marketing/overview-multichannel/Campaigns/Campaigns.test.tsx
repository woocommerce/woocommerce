/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { useCampaigns } from './useCampaigns';
import { Campaigns } from './Campaigns';

jest.mock( './useCampaigns', () => ( {
	useCampaigns: jest.fn(),
} ) );

describe( 'Campaigns component', () => {
	it( 'renders a TablePlaceholder when loading is in progress', () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: true,
			error: undefined,
			data: undefined,
			meta: undefined,
		} );

		const { container } = render( <Campaigns /> );
		const tablePlaceholder = container.querySelector(
			'div.woocommerce-table__table.is-loading'
		);

		expect( tablePlaceholder ).toBeInTheDocument();
	} );

	it( 'renders an error message when loading is done and data is undefined', () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: false,
			error: {},
			data: undefined,
			meta: undefined,
		} );

		render( <Campaigns /> );

		expect(
			screen.getByText( 'An unexpected error occurred.' )
		).toBeInTheDocument();
	} );

	it( 'renders an info message when loading is done and data is an empty array', () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: false,
			error: undefined,
			data: [],
			meta: {
				total: 0,
			},
		} );

		render( <Campaigns /> );

		expect(
			screen.getByText( 'Advertise with marketing campaigns' )
		).toBeInTheDocument();
	} );

	it( 'renders a table with campaign info and without pagination when loading is done and data is an array with length no more than page size', () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: false,
			error: undefined,
			data: [
				{
					id: `google-listings-and-ads|111`,
					title: 'Campaign 111',
					description: '',
					cost: `USD 111`,
					manageUrl:
						'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=111',
					icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
					channelName: 'Google Listings and Ads',
					channelSlug: 'google-listings-and-ads',
				},
			],
			meta: {
				total: 1,
			},
		} );

		const { container } = render( <Campaigns /> );

		expect( screen.getByText( 'Campaign 111' ) ).toBeInTheDocument();

		expect( screen.getByText( 'USD 111' ) ).toBeInTheDocument();

		const pagination = container.querySelector( '.woocommerce-pagination' );
		expect( pagination ).not.toBeInTheDocument();
	} );

	it( 'renders a table with campaign info and with pagination when loading is done and data is an array with length more than page size', async () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: false,
			error: undefined,
			data: [
				{
					id: `google-listings-and-ads|111`,
					title: 'Campaign 111',
					description: '',
					cost: `USD 111`,
					manageUrl:
						'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=111',
					icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
					channelName: 'Google Listings and Ads',
					channelSlug: 'google-listings-and-ads',
				},
				{
					id: `google-listings-and-ads|222`,
					title: 'Campaign 222',
					description: '',
					cost: `USD 222`,
					manageUrl:
						'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=222',
					icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
					channelName: 'Google Listings and Ads',
					channelSlug: 'google-listings-and-ads',
				},
				{
					id: `google-listings-and-ads|333`,
					title: 'Campaign 333',
					description: '',
					cost: `USD 333`,
					manageUrl:
						'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=333',
					icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
					channelName: 'Google Listings and Ads',
					channelSlug: 'google-listings-and-ads',
				},
				{
					id: `google-listings-and-ads|444`,
					title: 'Campaign 444',
					description: '',
					cost: `USD 444`,
					manageUrl:
						'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=444',
					icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
					channelName: 'Google Listings and Ads',
					channelSlug: 'google-listings-and-ads',
				},
				{
					id: `google-listings-and-ads|555`,
					title: 'Campaign 555',
					description: '',
					cost: `USD 555`,
					manageUrl:
						'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=555',
					icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
					channelName: 'Google Listings and Ads',
					channelSlug: 'google-listings-and-ads',
				},
				{
					id: `google-listings-and-ads|666`,
					title: 'Campaign 666',
					description: '',
					cost: `USD 666`,
					manageUrl:
						'https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=666',
					icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
					channelName: 'Google Listings and Ads',
					channelSlug: 'google-listings-and-ads',
				},
			],
			meta: {
				total: 6,
			},
		} );

		render( <Campaigns /> );

		// Campaign info.
		expect( screen.getByText( 'Campaign 111' ) ).toBeInTheDocument();
		expect( screen.getByText( 'USD 111' ) ).toBeInTheDocument();

		// Pagination.
		expect( screen.getByText( 'Page 1 of 2' ) ).toBeInTheDocument();

		// Click on the next button to go to next page.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Next Page' } )
		);

		// Campaign info in the second page.
		expect( screen.getByText( 'Campaign 666' ) ).toBeInTheDocument();
		expect( screen.getByText( 'USD 666' ) ).toBeInTheDocument();
	} );
} );
