import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';
import { removeAllFilters } from '@wordpress/hooks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Products } from '..';
import {
	SETUP_TASKLIST_PRODUCTS_AFTER_FILTER,
	defaultSurfacedProductTypes,
	productTypes,
} from '../constants';
import { getAdminSetting } from '~/utils/admin-settings';

// Mock window.location
const mockLocation = {
	href: '',
	assign: vi.fn(),
};
Object.defineProperty( window, 'location', {
	value: mockLocation,
	writable: true,
} );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useDispatch: vi.fn().mockReturnValue( {
			createNotice: vi.fn(),
		} ),
		useSelect: vi.fn().mockImplementation( ( callback ) =>
			callback( () => ( {
				getInstalledPlugins: () => [],
				isPluginsRequesting: () => false,
			} ) )
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/utils/admin-settings', () => {
	const mock = {
		getAdminSetting: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../use-create-product-by-type', () => {
	const mock = {
		useCreateProductByType: vi.fn().mockReturnValue( {
			createProductByType: vi.fn(),
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
global.fetch = vi.fn().mockImplementation( () =>
	Promise.resolve( {
		json: () => Promise.resolve( {} ),
		status: 200,
	} )
);
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const confirmModalText =
	'We’ll import images from WooCommerce.com to set up your sample products.';
describe( 'Products', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		// @ts-expect-error -- outdated type definition
		removeAllFilters( SETUP_TASKLIST_PRODUCTS_AFTER_FILTER );

		// Reset location.href
		mockLocation.href = '';
	} );
	it( 'should render default products types when onboardingData.profile.productType is null', () => {
		( getAdminSetting as Mock ).mockImplementation( () => ( {
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
		( getAdminSetting as Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, queryAllByRole } = render( <Products /> );
		const productTypeList = queryAllByRole( 'menu' )?.[ 0 ];
		expect( queryByText( 'Digital product' ) ).toBeInTheDocument();
		expect( productTypeList?.childElementCount ).toBe( 1 );
		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();
	} );
	it( 'clicking on suggested product should fire event tasklist_add_product with method: product_template, tasklist_product_template_selection with is_suggested:true and task_completion_time', async () => {
		( getAdminSetting as Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { getByRole, getByText } = render( <Products /> );
		await act( async () => {
			userEvent.click(
				getByRole( 'menuitem', {
					name: /Digital product\s*A digital product like service, downloadable book, music or video\./,
				} )
			);
		} );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'tasklist_add_product',
			{
				method: 'product_template',
			}
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'tasklist_product_template_selection',
			{
				is_suggested: true,
				product_type: 'digital',
			}
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			3,
			'task_completion_time',
			{
				task_name: 'products',
				time: '0-2s',
			}
		);
	} );
	it( 'clicking on not-suggested product should fire event tasklist_add_product with method: product_template, tasklist_product_template_selection with is_suggested:false and task_completion_time', async () => {
		( getAdminSetting as Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, getByRole, queryAllByRole } = render(
			<Products />
		);
		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'View more product types',
				} )
			);
		} );
		await waitFor( () => {
			const productTypeList = queryAllByRole( 'menu' )?.[ 0 ];
			expect( productTypeList?.childElementCount ).toBe(
				productTypes.length
			);
		} );
		await act( async () => {
			userEvent.click(
				getByRole( 'menuitem', {
					name: /Grouped product\s*A collection of related products\./,
				} )
			);
		} );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'tasklist_view_more_product_types_click'
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'tasklist_add_product',
			{
				method: 'product_template',
			}
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			3,
			'tasklist_product_template_selection',
			{
				is_suggested: false,
				product_type: 'grouped',
			}
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			4,
			'task_completion_time',
			{
				task_name: 'products',
				time: '0-2s',
			}
		);
	} );
	it( 'should render all products type when clicking view more button', async () => {
		( getAdminSetting as Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, getByRole, queryAllByRole } = render(
			<Products />
		);
		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'View more product types',
				} )
			);
		} );
		await waitFor( () => {
			const productTypeList = queryAllByRole( 'menu' )?.[ 0 ];
			expect( productTypeList?.childElementCount ).toBe(
				productTypes.length
			);
		} );
		expect( queryByText( 'View less product types' ) ).toBeInTheDocument();
	} );
	it( 'should send a request to load sample products when the "Import sample products" button is clicked', async () => {
		const fetchMock = vi.spyOn( global, 'fetch' );
		const { queryByText, getByRole, getByText } = render( <Products /> );
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'View more product types',
				} )
			);
		} );
		expect( queryByText( 'Load Sample Products' ) ).toBeInTheDocument();
		await act( async () => {
			userEvent.click( getByText( 'Load Sample Products' ) );
		} );
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).toBeInTheDocument()
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'Import sample products',
				} )
			);
		} );
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).not.toBeInTheDocument()
		);
		expect( fetchMock ).toHaveBeenCalledWith(
			'/wc-admin/onboarding/tasks/import_sample_products?_locale=user',
			{
				body: undefined,
				credentials: 'include',
				headers: {
					Accept: 'application/json, */*;q=0.1',
				},
				method: 'POST',
			}
		);
	} );
	it( 'should close the confirmation modal when the cancel button is clicked', async () => {
		const { queryByText, getByRole, getByText } = render( <Products /> );
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'View more product types',
				} )
			);
		} );
		expect( queryByText( 'Load Sample Products' ) ).toBeInTheDocument();
		await act( async () => {
			userEvent.click( getByText( 'Load Sample Products' ) );
		} );
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).toBeInTheDocument()
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'Cancel',
				} )
			);
		} );
		expect( queryByText( confirmModalText ) ).not.toBeInTheDocument();
		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_cancel_load_sample_products_click'
		);
	} );
	it( 'should render stacked layout', async () => {
		const { container } = render( <Products /> );
		expect(
			container.getElementsByClassName( 'woocommerce-products-stack' )
				.length
		).toBeGreaterThanOrEqual( 1 );
	} );
	it( 'should trigger event tasklist_add_product_visit_marketplace_click when clicking the WooCommerce Marketplace link', async () => {
		const { getByText } = render( <Products /> );
		await act( async () => {
			userEvent.click( getByText( 'the WooCommerce Marketplace' ) );
		} );
		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_add_product_visit_marketplace_click',
			{}
		);
	} );
	it( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', async () => {
		mockLocation.href = 'test';
		Object.defineProperty( global.window, 'location', {
			value: mockLocation,
		} );
		const { getByText } = render( <Products /> );
		await act( async () => {
			userEvent.click( getByText( 'the WooCommerce Marketplace' ) );
		} );
		expect( mockLocation.href ).toContain(
			'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=merchandising'
		);
	} );
	describe( 'Printful banner visibility', () => {
		it( 'should show Printful banner when feature is enabled and plugin is not installed', async () => {
			( useSelect as Mock ).mockImplementation( ( callback ) =>
				callback( () => ( {
					getInstalledPlugins: () => [],
					isPluginsRequesting: () => false,
				} ) )
			);
			const { getByText } = render( <Products /> );
			await waitFor( () => {
				expect(
					getByText( 'Print-on-demand products' )
				).toBeInTheDocument();
			} );
		} );
		it( 'should hide Printful banner when plugin is installed', async () => {
			( useSelect as Mock ).mockImplementation( ( callback ) =>
				callback( () => ( {
					getInstalledPlugins: () => [
						'printful-shipping-for-woocommerce',
					],
					isPluginsRequesting: () => false,
				} ) )
			);
			const { queryByText } = render( <Products /> );
			await waitFor( () => {
				expect(
					queryByText( 'Print-on-demand products' )
				).not.toBeInTheDocument();
			} );
		} );
		it( 'should hide Printful banner while plugins are being requested', async () => {
			( useSelect as Mock ).mockImplementation( ( callback ) =>
				callback( () => ( {
					getInstalledPlugins: () => [],
					isPluginsRequesting: () => true,
				} ) )
			);
			const { queryByText } = render( <Products /> );
			await waitFor( () => {
				expect(
					queryByText( 'Print-on-demand products' )
				).not.toBeInTheDocument();
			} );
		} );
	} );
} );
