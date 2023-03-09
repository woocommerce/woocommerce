/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { useCampaigns } from './useCampaigns';
import { useCampaignTypes } from '~/marketing/hooks';
import { Campaigns } from './Campaigns';

jest.mock( './useCampaigns', () => ( {
	useCampaigns: jest.fn(),
} ) );

jest.mock( '~/marketing/hooks', () => ( {
	useCampaignTypes: jest.fn(),
} ) );

jest.mock( './CreateNewCampaignModal', () => ( {
	CreateNewCampaignModal: () => <div>Create a new campaign</div>,
} ) );

/**
 * Create a test campaign data object.
 */
const createTestCampaign = ( programId: string ) => {
	return {
		id: `google-listings-and-ads|${ programId }`,
		title: `Campaign ${ programId }`,
		description: '',
		cost: `USD 30`,
		manageUrl: `https://wc1.test/wp-admin/admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=${ programId }`,
		icon: 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png',
		channelName: 'Google Listings and Ads',
		channelSlug: 'google-listings-and-ads',
	};
};

describe( 'Campaigns component', () => {
	it( 'renders a TablePlaceholder when loading is in progress', () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: true,
			error: undefined,
			data: undefined,
			meta: undefined,
		} );
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			loading: true,
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
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			loading: false,
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
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			loading: false,
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
			data: [ createTestCampaign( '1' ) ],
			meta: {
				total: 1,
			},
		} );
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			loading: false,
		} );

		const { container } = render( <Campaigns /> );

		expect( screen.getByText( 'Campaign 1' ) ).toBeInTheDocument();
		expect( screen.getByText( 'USD 30' ) ).toBeInTheDocument();

		const pagination = container.querySelector( '.woocommerce-pagination' );
		expect( pagination ).not.toBeInTheDocument();
	} );

	it( 'renders a table with campaign info and with pagination when loading is done and data is an array with length more than page size', async () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: false,
			error: undefined,
			data: [
				createTestCampaign( '1' ),
				createTestCampaign( '2' ),
				createTestCampaign( '3' ),
				createTestCampaign( '4' ),
				createTestCampaign( '5' ),
				createTestCampaign( '6' ),
			],
			meta: {
				total: 6,
			},
		} );
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			loading: false,
		} );

		render( <Campaigns /> );

		// Campaign info.
		expect( screen.getByText( 'Campaign 1' ) ).toBeInTheDocument();

		// Pagination.
		expect( screen.getByText( 'Page 1 of 2' ) ).toBeInTheDocument();

		// Click on the next button to go to next page.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Next Page' } )
		);

		// Campaign info in the second page.
		expect( screen.getByText( 'Campaign 6' ) ).toBeInTheDocument();
	} );

	it( 'renders a "Create new campaign" button in the card header, and upon clicking, displays the "Create a new campaign" modal', async () => {
		( useCampaigns as jest.Mock ).mockReturnValue( {
			loading: false,
			error: undefined,
			data: [ createTestCampaign( '1' ) ],
			meta: {
				total: 1,
			},
		} );
		( useCampaignTypes as jest.Mock ).mockReturnValue( {
			loading: false,
		} );

		render( <Campaigns /> );

		expect( screen.getByText( 'Create new campaign' ) ).toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Create new campaign' } )
		);

		expect(
			screen.getByText( 'Create a new campaign' )
		).toBeInTheDocument();
	} );
} );
