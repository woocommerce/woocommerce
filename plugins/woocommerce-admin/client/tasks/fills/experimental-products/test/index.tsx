/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { Products } from '../';
import { productTypes } from '../constants';
import { getAdminSetting } from '~/utils/admin-settings';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

describe( 'Products', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render all products types without view less button when onboardingData.profile.productType is null', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: null,
			},
		} ) );
		const { queryByText } = render( <Products /> );

		productTypes.forEach( ( { title } ) => {
			expect( queryByText( title ) ).toBeInTheDocument();
		} );

		expect(
			queryByText( 'View more product types' )
		).not.toBeInTheDocument();
	} );

	it( 'should render digital products type with view more button', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, queryByRole } = render( <Products /> );

		expect( queryByText( 'Digital product' ) ).toBeInTheDocument();
		expect( queryByRole( 'menu' )?.childElementCount ).toBe( 1 );
		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();
	} );

	it( 'should render all products type when clicking view more button', async () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, getByRole, queryByRole } = render( <Products /> );

		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'button', { name: 'View more product types' } )
		);

		await waitFor( () =>
			expect( queryByRole( 'menu' )?.childElementCount ).toBe(
				productTypes.length
			)
		);

		expect( queryByText( 'View less product types' ) ).toBeInTheDocument();
	} );
} );
