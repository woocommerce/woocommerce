/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import React from 'react';

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn( () => '/new-path' ),
	navigateTo: jest.fn(),
	useQuery: jest.fn( () => ( {} ) ),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	ADMIN_URL: 'http://example.test/wp-admin/',
} ) );

jest.mock( '../../category-selector/category-selector', () => ( {
	__esModule: true,
	default: () => <div data-testid="category-selector" />,
} ) );

jest.mock( '../../quality-badge/quality-badge-filter', () => ( {
	__esModule: true,
	default: () => <div data-testid="quality-badge-filter" />,
} ) );

jest.mock( '../../product-list-content/product-list-content', () => ( {
	__esModule: true,
	default: () => <span>Product list content</span>,
} ) );

jest.mock( '../../product-list-content/no-results', () => ( {
	__esModule: true,
	default: () => <span>No results</span>,
} ) );

jest.mock( '../../product-loader/product-loader', () => ( {
	__esModule: true,
	default: () => <div data-testid="product-loader" />,
} ) );

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
		renderProducts( { searchTerm: 'shipping' } );

		expect(
			screen.getByRole( 'heading', { name: 'Results for “shipping”' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Product list content' )
		).toBeInTheDocument();
	} );

	it( 'keeps the heading when the search finds nothing', () => {
		renderProducts( { searchTerm: 'zzzz', products: [] } );

		expect(
			screen.getByRole( 'heading', { name: 'Results for “zzzz”' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'No results' ) ).toBeInTheDocument();
	} );

	it( 'shows no heading when browsing without a search term', () => {
		renderProducts( {} );

		expect( screen.queryByRole( 'heading' ) ).toBeNull();
	} );

	it( 'shows no heading for a blank search term', () => {
		renderProducts( { searchTerm: '   ' } );

		expect( screen.queryByRole( 'heading' ) ).toBeNull();
	} );
} );
