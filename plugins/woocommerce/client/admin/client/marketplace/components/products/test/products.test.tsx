import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import React from 'react';
vi.mock( '@woocommerce/navigation', () => {
	const mock = {
		getNewPath: vi.fn( () => '/new-path' ),
		navigateTo: vi.fn(),
		useQuery: vi.fn( () => ( {} ) ),
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
		ADMIN_URL: 'http://example.test/wp-admin/',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../category-selector/category-selector', () => {
	const mock = {
		__esModule: true,
		default: () => <div data-testid="category-selector" />,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../quality-badge/quality-badge-filter', () => {
	const mock = {
		__esModule: true,
		default: () => <div data-testid="quality-badge-filter" />,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../product-list-content/product-list-content', () => {
	const mock = {
		__esModule: true,
		default: () => <span>Product list content</span>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../product-list-content/no-results', () => {
	const mock = {
		__esModule: true,
		default: () => <span>No results</span>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../product-loader/product-loader', () => {
	const mock = {
		__esModule: true,
		default: () => <div data-testid="product-loader" />,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

/**
 * Internal dependencies
 */
import Products from '../products';
import { MarketplaceContext } from '../../../contexts/marketplace-context';
import { MarketplaceContextType } from '../../../contexts/types';
import { Product, ProductType } from '../../product-list/types';
const context = {
	isLoading: false,
} as unknown as MarketplaceContextType;
const product: Product = {
	id: 1,
	title: 'Test extension',
	image: '',
	type: ProductType.extension,
	description: '',
	vendorName: '',
	vendorUrl: '',
	icon: '',
	url: '',
	price: 0,
	isInstallable: false,
	currency: 'USD',
	isOnSale: false,
	regularPrice: 0,
	averageRating: null,
	reviewsCount: null,
};
function renderProducts( props: {
	searchTerm?: string;
	products?: Product[];
} ) {
	return render(
		<MarketplaceContext.Provider value={ context }>
			<Products
				type={ ProductType.extension }
				categorySelector={ true }
				products={ props.products ?? [ product ] }
				searchTerm={ props.searchTerm }
			/>
		</MarketplaceContext.Provider>
	);
}
describe( 'Products search results heading', () => {
	it( 'names the search term above the results', () => {
		renderProducts( {
			searchTerm: 'shipping',
		} );
		expect(
			screen.getByRole( 'heading', {
				name: 'Results for “shipping”',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Product list content' )
		).toBeInTheDocument();
	} );
	it( 'keeps the heading when the search finds nothing', () => {
		renderProducts( {
			searchTerm: 'zzzz',
			products: [],
		} );
		expect(
			screen.getByRole( 'heading', {
				name: 'Results for “zzzz”',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'No results' ) ).toBeInTheDocument();
	} );
	it( 'shows no heading when browsing without a search term', () => {
		renderProducts( {} );
		expect( screen.queryByRole( 'heading' ) ).toBeNull();
	} );
	it( 'shows no heading for a blank search term', () => {
		renderProducts( {
			searchTerm: '   ',
		} );
		expect( screen.queryByRole( 'heading' ) ).toBeNull();
	} );
} );
