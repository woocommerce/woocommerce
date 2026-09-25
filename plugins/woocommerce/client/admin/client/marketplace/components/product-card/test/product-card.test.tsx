/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import React from 'react';
import { queueRecordEvent } from '@woocommerce/tracks';

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn( () => '/new-path' ),
	navigateTo: jest.fn(),
	useQuery: jest.fn( () => ( {} ) ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
	queueRecordEvent: jest.fn(),
} ) );

// The preview modal loads its own data; these tests only care about the card.
jest.mock(
	'../../product-preview-modal/product-preview-modal',
	() => () => null
);

jest.mock( '@woocommerce/data', () => ( {
	useUser: jest.fn( () => ( {
		user: null,
		currentUserCan: jest.fn( () => false ),
	} ) ),
} ) );

/**
 * Internal dependencies
 */
import ProductCard from '../product-card';
import { MarketplaceContext } from '../../../contexts/marketplace-context';
import { MarketplaceContextType } from '../../../contexts/types';
import {
	Product,
	ProductCardType,
	ProductType,
} from '../../product-list/types';

const context = {
	selectedTab: 'discover',
	isProductInstalled: () => false,
	iamSettings: {
		quality_badge: {
			enabled: true,
			label: 'Excellence Verified',
			tooltip: 'Verified against WooCommerce standards.',
		},
	},
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
	averageRating: 4.5,
	reviewsCount: 10,
	hasQualityBadge: true,
};

function renderCard(
	cardType: ProductCardType,
	productOverrides: Partial< Product > = {},
	contextOverrides: Partial< MarketplaceContextType > = {}
) {
	return render(
		<MarketplaceContext.Provider
			value={ { ...context, ...contextOverrides } }
		>
			<ProductCard
				type={ ProductType.extension }
				product={ { ...product, ...productOverrides } }
				cardType={ cardType }
				tracksData={ { position: 1 } }
			/>
		</MarketplaceContext.Provider>
	);
}

function getBadge( container: HTMLElement ) {
	return container.querySelector( '.woocommerce-marketplace__quality-badge' );
}

describe( 'ProductCard quality badge placement', () => {
	it( 'renders the badge between the title and the price on compact cards', () => {
		const { container } = renderCard( ProductCardType.compact );
		const badge = getBadge( container );
		const meta = container.querySelector(
			'.woocommerce-marketplace__product-card__meta'
		);
		const footer = container.querySelector(
			'.woocommerce-marketplace__product-card__footer'
		);

		expect( badge ).not.toBeNull();
		expect( badge?.parentElement ).toBe( meta );
		expect( footer?.contains( badge ) ).toBe( false );

		const title = container.querySelector(
			'.woocommerce-marketplace__product-card__title'
		);
		expect( title?.nextElementSibling ).toBe( badge );
		expect( badge?.nextElementSibling ).toBe( footer );
	} );

	it( 'renders the badge in the footer above the price on regular cards', () => {
		const { container } = renderCard( ProductCardType.regular );
		const badge = getBadge( container );
		const footer = container.querySelector(
			'.woocommerce-marketplace__product-card__footer'
		);
		const price = container.querySelector(
			'.woocommerce-marketplace__product-card__price'
		);

		expect( badge ).not.toBeNull();
		expect( badge?.parentElement ).toBe( footer );
		expect( footer?.firstElementChild ).toBe( badge );
		expect( badge?.nextElementSibling ).toBe( price );
	} );

	it( 'renders no badge when the product does not have one', () => {
		const { container } = renderCard( ProductCardType.compact, {
			hasQualityBadge: false,
		} );

		expect( getBadge( container ) ).toBeNull();
	} );
} );

describe( 'ProductCard sponsored label', () => {
	const sponsoredProduct: Product = {
		...product,
		vendorName: 'Test vendor',
		vendorUrl: 'https://example.com',
		label: 'promoted',
		primary_color: '#720eec',
	};

	function renderSponsoredCard(
		cardType: ProductCardType,
		overrides: Partial< Product > = {}
	) {
		return renderCard( cardType, { ...sponsoredProduct, ...overrides } );
	}

	function getSponsoredLabel( view: ReturnType< typeof render > ) {
		return view.queryByText( 'Sponsored' );
	}

	// The vendor line renders "By <name>" across separate elements, and the
	// vendor link carries screen-reader text of its own. Match the phrase at
	// its shallowest element so ancestors and the link itself don't match.
	const VENDOR_PHRASE = /^By Test vendor/;

	function matchesVendorPhrase( content: string, element: Element | null ) {
		return (
			VENDOR_PHRASE.test( element?.textContent ?? '' ) &&
			! Array.from( element?.children ?? [] ).some( ( child ) =>
				VENDOR_PHRASE.test( child.textContent ?? '' )
			)
		);
	}

	it( 'renders the label on compact cards, without the vendor', () => {
		const view = renderSponsoredCard( ProductCardType.compact );

		expect( view.getByText( 'Sponsored' ) ).toBeVisible();
		expect( view.queryByText( matchesVendorPhrase ) ).toBeNull();
		expect(
			view.container.querySelector(
				'.woocommerce-marketplace__product-card__vendor-details__separator'
			)
		).toBeNull();
	} );

	it( 'renders the vendor, separator and label on regular cards', () => {
		const view = renderSponsoredCard( ProductCardType.regular );

		// The space in "By Test vendor" is the regression guarded against.
		expect( view.getByText( matchesVendorPhrase ) ).toBeVisible();
		expect(
			view.container.querySelector(
				'.woocommerce-marketplace__product-card__vendor-details__separator'
			)
		).not.toBeNull();
		expect( view.getByText( 'Sponsored' ) ).toBeVisible();
	} );

	it( 'renders no label or vendor line on compact cards that are not sponsored', () => {
		const view = renderSponsoredCard( ProductCardType.compact, {
			label: undefined,
		} );

		expect( getSponsoredLabel( view ) ).toBeNull();
		expect(
			view.container.querySelector(
				'.woocommerce-marketplace__product-card__vendor-details'
			)
		).toBeNull();
	} );
} );

describe( 'ProductCard click tracking', () => {
	beforeEach( () => {
		jest.mocked( queueRecordEvent ).mockClear();
	} );

	function clickCard(
		productOverrides: Partial< Product > = {},
		contextOverrides: Partial< MarketplaceContextType > = {}
	) {
		const view = renderCard(
			ProductCardType.regular,
			productOverrides,
			contextOverrides
		);

		fireEvent.click( view.getByRole( 'link' ) );
	}

	// A click records exactly one event, and the mock is cleared before each test.
	function cardClickEvents() {
		return jest.mocked( queueRecordEvent ).mock.calls;
	}

	it( 'reports the badge when the card shows one', () => {
		clickCard();

		expect( cardClickEvents() ).toEqual( [
			[
				'marketplace_product_card_clicked',
				expect.objectContaining( {
					product_id: 1,
					has_quality_badge: true,
				} ),
			],
		] );
	} );

	it( 'reports no badge when the product does not have one', () => {
		clickCard( { hasQualityBadge: false } );

		expect( cardClickEvents()[ 0 ][ 1 ] ).toMatchObject( {
			has_quality_badge: false,
		} );
	} );

	it( 'reports no badge when the badge is disabled for the store', () => {
		clickCard(
			{},
			{
				iamSettings: {
					...context.iamSettings,
					quality_badge: {
						...context.iamSettings.quality_badge,
						enabled: false,
					},
				},
			}
		);

		expect( cardClickEvents()[ 0 ][ 1 ] ).toMatchObject( {
			has_quality_badge: false,
		} );
	} );

	it( 'reports no badge on business service cards, which cannot show one', () => {
		clickCard( { type: ProductType.businessService } );

		expect( cardClickEvents()[ 0 ][ 1 ] ).toMatchObject( {
			product_type: ProductType.businessService,
			has_quality_badge: false,
		} );
	} );

	it( 'records the click once when the card opens a preview modal', () => {
		clickCard(
			{},
			{
				iamSettings: {
					...context.iamSettings,
					product_previews: 'modal',
				},
			}
		);

		expect( cardClickEvents() ).toHaveLength( 1 );
	} );
} );
