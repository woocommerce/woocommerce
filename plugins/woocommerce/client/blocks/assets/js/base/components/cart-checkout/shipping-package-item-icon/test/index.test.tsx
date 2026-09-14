/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import ShippingPackageItemIcon from '../index';
import type { PackageItem } from '../../shipping-rates-control-package/types';

// Mock the ProductImage component
jest.mock( '../../product-image', () => {
	return function ProductImage( {
		image,
		fallbackAlt,
		width,
		height,
	}: {
		image: { alt?: string; thumbnail?: string };
		fallbackAlt: string;
		width?: number;
		height?: number;
	} ) {
		return (
			<img
				data-testid="product-image"
				src={ image.thumbnail || '' }
				alt={ image.alt || fallbackAlt }
				width={ width }
				height={ height }
			/>
		);
	};
} );

const mockPackageItem: PackageItem = {
	key: 'test-item-key',
	name: 'Test Product',
	quantity: 1,
};

const mockCartItemWithImages: CartItem = {
	key: 'test-item-key',
	name: 'Test Product',
	images: [
		{
			id: 1,
			src: 'https://example.com/image1.jpg',
			thumbnail: 'https://example.com/image1-thumb.jpg',
			srcset: '',
			sizes: '',
			name: 'image1',
			alt: 'Test Product Image',
		},
		{
			id: 2,
			src: 'https://example.com/image2.jpg',
			thumbnail: 'https://example.com/image2-thumb.jpg',
			srcset: '',
			sizes: '',
			name: 'image2',
			alt: 'Second Image',
		},
	],
	id: 1,
	quantity: 1,
	quantity_limits: {
		minimum: 1,
		maximum: 10,
		multiple_of: 1,
		editable: true,
	},
	catalog_visibility: 'visible',
	prices: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		price: '1000',
		regular_price: '1000',
		sale_price: '1000',
		price_range: null,
		raw_prices: {
			precision: 2,
			price: '1000',
			regular_price: '1000',
			sale_price: '1000',
		},
	},
	totals: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		line_subtotal: '1000',
		line_subtotal_tax: '0',
		line_total: '1000',
		line_total_tax: '0',
	},
	variation: [],
	item_data: [],
	low_stock_remaining: null,
	show_backorder_badge: false,
	sold_individually: false,
	permalink: 'https://example.com/product',
	short_description: '',
	description: '',
	sku: '',
	backorders_allowed: false,
	type: 'simple',
	summary: '',
	extensions: {},
};

const mockCartItemWithoutImages: CartItem = {
	...mockCartItemWithImages,
	images: [],
};

describe( 'ShippingPackageItemIcon', () => {
	it( 'renders ProductImage with the first image and correct props when cart item has images', () => {
		render(
			<ShippingPackageItemIcon
				packageItem={ mockPackageItem }
				cartItems={ [ mockCartItemWithImages ] }
			/>
		);

		const image = screen.getByTestId( 'product-image' );
		expect( image ).toBeInTheDocument();
		expect( image ).toHaveAttribute(
			'src',
			'https://example.com/image1-thumb.jpg'
		);
		expect( image ).toHaveAttribute( 'alt', 'Test Product Image' );
	} );

	it.each( [
		[
			'cart item has no images',
			mockPackageItem,
			[ mockCartItemWithoutImages ],
			'Test Product',
		],
		[
			'cart item is not found',
			{ ...mockPackageItem, key: 'non-existent' },
			[ mockCartItemWithImages ],
			'',
		],
		[ 'cartItems is empty', mockPackageItem, [], '' ],
		[
			'cartItems is undefined',
			mockPackageItem,
			undefined as unknown as CartItem[],
			'',
		],
	] )(
		'renders placeholder when %s',
		( _, packageItem, cartItems, expectedAlt ) => {
			render(
				<ShippingPackageItemIcon
					packageItem={ packageItem }
					cartItems={ cartItems }
				/>
			);

			const image = screen.getByTestId( 'product-image' );
			expect( image ).toBeInTheDocument();
			expect( image ).toHaveAttribute( 'src', '' );
			expect( image ).toHaveAttribute( 'alt', expectedAlt );
		}
	);

	it( 'uses cart item name as fallback alt text', () => {
		const cartItemWithImageNoAlt: CartItem = {
			...mockCartItemWithImages,
			images: [
				{
					id: 1,
					src: 'https://example.com/image.jpg',
					thumbnail: 'https://example.com/image-thumb.jpg',
					srcset: '',
					sizes: '',
					name: 'image',
					alt: '',
				},
			],
		};

		render(
			<ShippingPackageItemIcon
				packageItem={ mockPackageItem }
				cartItems={ [ cartItemWithImageNoAlt ] }
			/>
		);

		const image = screen.getByTestId( 'product-image' );
		expect( image ).toHaveAttribute( 'alt', 'Test Product' );
	} );

	it( 'correctly matches cart item by key', () => {
		const cartItems: CartItem[] = [
			{ ...mockCartItemWithoutImages, key: 'item-1' },
			{ ...mockCartItemWithImages, key: 'test-item-key' },
			{ ...mockCartItemWithoutImages, key: 'item-3' },
		];

		render(
			<ShippingPackageItemIcon
				packageItem={ mockPackageItem }
				cartItems={ cartItems }
			/>
		);

		const image = screen.getByTestId( 'product-image' );
		expect( image ).toHaveAttribute(
			'src',
			'https://example.com/image1-thumb.jpg'
		);
	} );
} );
