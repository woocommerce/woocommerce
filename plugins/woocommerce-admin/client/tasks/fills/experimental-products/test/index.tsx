/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useProductTaskExperiment } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { Products } from '../';
import { defaultSurfacedProductTypes, productTypes } from '../constants';
import { getAdminSetting } from '~/utils/admin-settings';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

jest.mock( '@woocommerce/onboarding', () => ( {
	useProductTaskExperiment: jest.fn().mockReturnValue( [ false, 'stacked' ] ),
} ) );

global.fetch = jest.fn().mockImplementation( () =>
	Promise.resolve( {
		json: () => Promise.resolve( {} ),
		status: 200,
	} )
);

describe( 'Products', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render default products types when onboardingData.profile.productType is null', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: null,
			},
		} ) );
		const { queryByText } = render( <Products /> );

		productTypes.forEach( ( { key, title } ) => {
			if ( defaultSurfacedProductTypes.includes( key ) ) {
				expect( queryByText( title ) ).toBeInTheDocument();
			}
		} );
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

	it( 'should send a request to load sample products when the link is clicked', async () => {
		const fetchMock = jest.spyOn( global, 'fetch' );
		const { queryByText, getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'button', { name: 'View more product types' } )
		);

		expect( queryByText( 'Load Sample Products' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'link', { name: 'Load Sample Products' } )
		);

		await waitFor( () =>
			expect( fetchMock ).toHaveBeenCalledWith(
				'/wc-admin/onboarding/tasks/import_sample_products?_locale=user',
				{
					body: undefined,
					credentials: 'include',
					headers: { Accept: 'application/json, */*;q=0.1' },
					method: 'POST',
				}
			)
		);
	} );

	it( 'should show spinner when layout experiment is loading', async () => {
		( useProductTaskExperiment as jest.Mock ).mockImplementation( () => [
			true,
			'card',
		] );
		const { container } = render( <Products /> );
		expect(
			container.getElementsByClassName( 'components-spinner' )
		).toHaveLength( 1 );
	} );

	it( 'should render card layout when experiment is assigned', async () => {
		( useProductTaskExperiment as jest.Mock ).mockImplementation( () => [
			false,
			'card',
		] );
		const { container } = render( <Products /> );
		expect(
			container.getElementsByClassName(
				'woocommerce-products-card-layout'
			)
		).toHaveLength( 1 );
	} );

	it( 'should render stacked layout when experiment is assigned', async () => {
		( useProductTaskExperiment as jest.Mock ).mockImplementation( () => [
			false,
			'stacked',
		] );
		const { container } = render( <Products /> );
		expect(
			container.getElementsByClassName( 'woocommerce-products-stack' )
		).toHaveLength( 1 );
	} );
} );
