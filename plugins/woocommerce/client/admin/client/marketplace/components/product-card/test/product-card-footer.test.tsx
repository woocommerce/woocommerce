/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import React from 'react';

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn( () => '/new-path' ),
	navigateTo: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	useUser: jest.fn( () => ( {
		user: null,
		currentUserCan: jest.fn( () => false ),
	} ) ),
} ) );

/**
 * Internal dependencies
 */
import ProductCardFooter from '../product-card-footer';
import { MarketplaceContext } from '../../../contexts/marketplace-context';
import { MarketplaceContextType } from '../../../contexts/types';
import { Product, ProductType } from '../../product-list/types';

const context = {
	selectedTab: 'extensions',
	isProductInstalled: () => false,
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
	reviewsCount: 10,
};

function renderFooter( averageRating: number | null | undefined ) {
	return render(
		<MarketplaceContext.Provider value={ context }>
			<ProductCardFooter product={ { ...product, averageRating } } />
		</MarketplaceContext.Provider>
	);
}

describe( 'ProductCardFooter rating', () => {
	it( 'renders the rating when the product has one', () => {
		const { getByText } = renderFooter( 4.5 );

		expect( getByText( '4.5' ) ).toBeInTheDocument();
	} );

	it( 'renders no rating when the product has none', () => {
		const { container } = renderFooter( null );

		expect(
			container.querySelector(
				'.woocommerce-marketplace__product-card__rating'
			)?.textContent
		).toBe( '' );
		expect(
			container.querySelector(
				'.woocommerce-marketplace__product-card__rating-icon'
			)
		).toBeNull();
	} );

	it( 'renders no rating when the rating is zero', () => {
		const { container } = renderFooter( 0 );

		expect(
			container.querySelector(
				'.woocommerce-marketplace__product-card__rating'
			)?.textContent
		).toBe( '' );
		expect(
			container.querySelector(
				'.woocommerce-marketplace__product-card__rating-icon'
			)
		).toBeNull();
	} );

	it( 'renders no rating when the API omits the rating', () => {
		const { container } = renderFooter( undefined );

		expect(
			container.querySelector(
				'.woocommerce-marketplace__product-card__rating'
			)?.textContent
		).toBe( '' );
		expect(
			container.querySelector(
				'.woocommerce-marketplace__product-card__rating-icon'
			)
		).toBeNull();
	} );
} );
