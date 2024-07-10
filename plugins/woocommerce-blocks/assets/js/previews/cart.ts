/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { CartResponse } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { previewShippingRates } from './shipping-rates';
import { API_SITE_CURRENCY, displayForMinorUnit } from './utils';

/**
 * Prices from the API may change because of this display setting. This makes the response use either
 * wc_get_price_including_tax or wc_get_price_excluding_tax. It is correct that this setting changes the cart preview
 * data.
 *
 * WooCommerce core has 2 settings which control this, one for cart (displayCartPricesIncludingTax), and one for the
 * rest of the store (displayProductPricesIncludingTax). Because of this, Cart endpoints use displayCartPricesIncludingTax
 * which is the most appropriate.
 *
 * Handling the display settings server-side helps work around rounding/display issues that can arise from manually
 * adding tax to a price.
 */
const displayWithTax = getSetting( 'displayCartPricesIncludingTax', false );

// Sample data for cart block.
// This closely resembles the data returned from the Store API /cart endpoint.
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/StoreApi/docs/cart.md#cart-response
export const previewCart: CartResponse = {
	coupons: [],
	shipping_rates:
		getSetting( 'shippingMethodsExist', false ) ||
		getSetting( 'localPickupEnabled', false )
			? previewShippingRates
			: [],
	items: [
		{
			key: '1',
			id: 1,
			type: 'simple',
			quantity: 2,
			catalog_visibility: 'visible',
			name: __( 'Beanie', 'woocommerce' ),
			summary: __( 'Beanie', 'woocommerce' ),
			short_description: __( 'Warm hat for winter', 'woocommerce' ),
			description:
				'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
			sku: 'woo-beanie',
			permalink: 'https://example.org',
			low_stock_remaining: 2,
			backorders_allowed: false,
			show_backorder_badge: false,
			sold_individually: false,
			quantity_limits: {
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
				editable: true,
			},
			images: [
				{
					id: 10,
					src: WC_BLOCKS_IMAGE_URL + 'previews/beanie.jpg',
					thumbnail: WC_BLOCKS_IMAGE_URL + 'previews/beanie.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			variation: [
				{
					attribute: __( 'Color', 'woocommerce' ),
					value: __( 'Yellow', 'woocommerce' ),
				},
				{
					attribute: __( 'Size', 'woocommerce' ),
					value: __( 'Small', 'woocommerce' ),
				},
			],
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit(
					displayWithTax ? '12000' : '10000'
				),
				regular_price: displayForMinorUnit(
					displayWithTax ? '120' : '100'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '12000' : '10000'
				),
				price_range: null,
				raw_prices: {
					precision: 6,
					price: displayWithTax ? '12000000' : '10000000',
					regular_price: displayWithTax ? '12000000' : '10000000',
					sale_price: displayWithTax ? '12000000' : '10000000',
				},
			},
			totals: {
				...API_SITE_CURRENCY,
				line_subtotal: displayForMinorUnit( '2000' ),
				line_subtotal_tax: displayForMinorUnit( '400' ),
				line_total: displayForMinorUnit( '2000' ),
				line_total_tax: displayForMinorUnit( '400' ),
			},
			extensions: {},
			item_data: [],
		},
		{
			key: '2',
			id: 2,
			type: 'simple',
			quantity: 1,
			catalog_visibility: 'visible',
			name: __( 'Cap', 'woocommerce' ),
			summary: __( 'Cap', 'woocommerce' ),
			short_description: __( 'Lightweight baseball cap', 'woocommerce' ),
			description:
				'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
			sku: 'woo-cap',
			low_stock_remaining: null,
			permalink: 'https://example.org',
			backorders_allowed: false,
			show_backorder_badge: false,
			sold_individually: false,
			quantity_limits: {
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
				editable: true,
			},
			images: [
				{
					id: 11,
					src: WC_BLOCKS_IMAGE_URL + 'previews/cap.jpg',
					thumbnail: WC_BLOCKS_IMAGE_URL + 'previews/cap.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			variation: [
				{
					attribute: __( 'Color', 'woocommerce' ),
					value: __( 'Orange', 'woocommerce' ),
				},
			],
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit( displayWithTax ? '2400' : '2000' ),
				regular_price: displayForMinorUnit(
					displayWithTax ? '2400' : '2000'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '2400' : '2000'
				),
				price_range: null,
				raw_prices: {
					precision: 6,
					price: displayWithTax ? '24000000' : '20000000',
					regular_price: displayWithTax ? '24000000' : '20000000',
					sale_price: displayWithTax ? '24000000' : '20000000',
				},
			},
			totals: {
				...API_SITE_CURRENCY,
				line_subtotal: displayForMinorUnit( '2000' ),
				line_subtotal_tax: displayForMinorUnit( '400' ),
				line_total: displayForMinorUnit( '2000' ),
				line_total_tax: displayForMinorUnit( '400' ),
			},
			extensions: {},
			item_data: [],
		},
	],
	cross_sells: [
		{
			id: 1,
			name: __( 'Polo', 'woocommerce' ),
			slug: 'polo',
			parent: 0,
			type: 'simple',
			variation: '',
			permalink: 'https://example.org',
			sku: 'woo-polo',
			short_description: __( 'Polo', 'woocommerce' ),
			description: __( 'Polo', 'woocommerce' ),
			on_sale: false,
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit(
					displayWithTax ? '24000' : '20000'
				),
				regular_price: displayForMinorUnit(
					displayWithTax ? '24000' : '20000'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '12000' : '10000'
				),
				price_range: null,
			},
			price_html: '',
			average_rating: '4.5',
			review_count: 2,
			images: [
				{
					id: 17,
					src: WC_BLOCKS_IMAGE_URL + 'previews/polo.jpg',
					thumbnail: WC_BLOCKS_IMAGE_URL + 'previews/polo.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			categories: [],
			tags: [],
			attributes: [],
			variations: [],
			has_options: false,
			is_purchasable: true,
			is_in_stock: true,
			is_on_backorder: false,
			low_stock_remaining: null,
			sold_individually: false,
			add_to_cart: {
				text: '',
				description: '',
				url: '',
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
			},
		},
		{
			id: 2,
			name: __( 'Long Sleeve Tee', 'woocommerce' ),
			slug: 'long-sleeve-tee',
			parent: 0,
			type: 'simple',
			variation: '',
			permalink: 'https://example.org',
			sku: 'woo-long-sleeve-tee',
			short_description: __( 'Long Sleeve Tee', 'woocommerce' ),
			description: __( 'Long Sleeve Tee', 'woocommerce' ),
			on_sale: false,
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit(
					displayWithTax ? '30000' : '25000'
				),
				regular_price: displayForMinorUnit(
					displayWithTax ? '30000' : '25000'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '30000' : '25000'
				),
				price_range: null,
			},
			price_html: '',
			average_rating: '4',
			review_count: 2,
			images: [
				{
					id: 17,
					src: WC_BLOCKS_IMAGE_URL + 'previews/long-sleeve-tee.jpg',
					thumbnail:
						WC_BLOCKS_IMAGE_URL + 'previews/long-sleeve-tee.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			categories: [],
			tags: [],
			attributes: [],
			variations: [],
			has_options: false,
			is_purchasable: true,
			is_in_stock: true,
			is_on_backorder: false,
			low_stock_remaining: null,
			sold_individually: false,
			add_to_cart: {
				text: '',
				description: '',
				url: '',
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
			},
		},
		{
			id: 3,
			name: __( 'Hoodie with Zipper', 'woocommerce' ),
			slug: 'hoodie-with-zipper',
			parent: 0,
			type: 'simple',
			variation: '',
			permalink: 'https://example.org',
			sku: 'woo-hoodie-with-zipper',
			short_description: __( 'Hoodie with Zipper', 'woocommerce' ),
			description: __( 'Hoodie with Zipper', 'woocommerce' ),
			on_sale: true,
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit(
					displayWithTax ? '15000' : '12500'
				),
				regular_price: displayForMinorUnit(
					displayWithTax ? '30000' : '25000'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '15000' : '12500'
				),
				price_range: null,
			},
			price_html: '',
			average_rating: '1',
			review_count: 2,
			images: [
				{
					id: 17,
					src:
						WC_BLOCKS_IMAGE_URL + 'previews/hoodie-with-zipper.jpg',
					thumbnail:
						WC_BLOCKS_IMAGE_URL + 'previews/hoodie-with-zipper.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			categories: [],
			tags: [],
			attributes: [],
			variations: [],
			has_options: false,
			is_purchasable: true,
			is_in_stock: true,
			is_on_backorder: false,
			low_stock_remaining: null,
			sold_individually: false,
			add_to_cart: {
				text: '',
				description: '',
				url: '',
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
			},
		},
		{
			id: 4,
			name: __( 'Hoodie with Logo', 'woocommerce' ),
			slug: 'hoodie-with-logo',
			parent: 0,
			type: 'simple',
			variation: '',
			permalink: 'https://example.org',
			sku: 'woo-hoodie-with-logo',
			short_description: __( 'Polo', 'woocommerce' ),
			description: __( 'Polo', 'woocommerce' ),
			on_sale: false,
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit( displayWithTax ? '4500' : '4250' ),
				regular_price: displayForMinorUnit(
					displayWithTax ? '4500' : '4250'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '4500' : '4250'
				),
				price_range: null,
			},
			price_html: '',
			average_rating: '5',
			review_count: 2,
			images: [
				{
					id: 17,
					src: WC_BLOCKS_IMAGE_URL + 'previews/hoodie-with-logo.jpg',
					thumbnail:
						WC_BLOCKS_IMAGE_URL + 'previews/hoodie-with-logo.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			categories: [],
			tags: [],
			attributes: [],
			variations: [],
			has_options: false,
			is_purchasable: true,
			is_in_stock: true,
			is_on_backorder: false,
			low_stock_remaining: null,
			sold_individually: false,
			add_to_cart: {
				text: '',
				description: '',
				url: '',
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
			},
		},
		{
			id: 5,
			name: __( 'Hoodie with Pocket', 'woocommerce' ),
			slug: 'hoodie-with-pocket',
			parent: 0,
			type: 'simple',
			variation: '',
			permalink: 'https://example.org',
			sku: 'woo-hoodie-with-pocket',
			short_description: __( 'Hoodie with Pocket', 'woocommerce' ),
			description: __( 'Hoodie with Pocket', 'woocommerce' ),
			on_sale: true,
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit( displayWithTax ? '3500' : '3250' ),
				regular_price: displayForMinorUnit(
					displayWithTax ? '4500' : '4250'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '3500' : '3250'
				),
				price_range: null,
			},
			price_html: '',
			average_rating: '3.75',
			review_count: 4,
			images: [
				{
					id: 17,
					src:
						WC_BLOCKS_IMAGE_URL + 'previews/hoodie-with-pocket.jpg',
					thumbnail:
						WC_BLOCKS_IMAGE_URL + 'previews/hoodie-with-pocket.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			categories: [],
			tags: [],
			attributes: [],
			variations: [],
			has_options: false,
			is_purchasable: true,
			is_in_stock: true,
			is_on_backorder: false,
			low_stock_remaining: null,
			sold_individually: false,
			add_to_cart: {
				text: '',
				description: '',
				url: '',
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
			},
		},
		{
			id: 6,
			name: __( 'T-Shirt', 'woocommerce' ),
			slug: 't-shirt',
			parent: 0,
			type: 'simple',
			variation: '',
			permalink: 'https://example.org',
			sku: 'woo-t-shirt',
			short_description: __( 'T-Shirt', 'woocommerce' ),
			description: __( 'T-Shirt', 'woocommerce' ),
			on_sale: false,
			prices: {
				...API_SITE_CURRENCY,
				price: displayForMinorUnit( displayWithTax ? '1800' : '1500' ),
				regular_price: displayForMinorUnit(
					displayWithTax ? '1800' : '1500'
				),
				sale_price: displayForMinorUnit(
					displayWithTax ? '1800' : '1500'
				),
				price_range: null,
			},
			price_html: '',
			average_rating: '3',
			review_count: 2,
			images: [
				{
					id: 17,
					src: WC_BLOCKS_IMAGE_URL + 'previews/tshirt.jpg',
					thumbnail: WC_BLOCKS_IMAGE_URL + 'previews/tshirt.jpg',
					srcset: '',
					sizes: '',
					name: '',
					alt: '',
				},
			],
			categories: [],
			tags: [],
			attributes: [],
			variations: [],
			has_options: false,
			is_purchasable: true,
			is_in_stock: true,
			is_on_backorder: false,
			low_stock_remaining: null,
			sold_individually: false,
			add_to_cart: {
				text: '',
				description: '',
				url: '',
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
			},
		},
	],
	fees: [
		{
			id: 'fee',
			name: __( 'Fee', 'woocommerce' ),
			totals: {
				...API_SITE_CURRENCY,
				total: displayForMinorUnit( '100' ),
				total_tax: displayForMinorUnit( '20' ),
			},
		},
	],
	items_count: 3,
	items_weight: 0,
	needs_payment: true,
	needs_shipping: getSetting( 'shippingEnabled', true ),
	has_calculated_shipping: true,
	shipping_address: {
		first_name: '',
		last_name: '',
		company: '',
		address_1: '',
		address_2: '',
		city: '',
		state: '',
		postcode: '',
		country: '',
		phone: '',
	},
	billing_address: {
		first_name: '',
		last_name: '',
		company: '',
		address_1: '',
		address_2: '',
		city: '',
		state: '',
		postcode: '',
		country: '',
		email: '',
		phone: '',
	},
	totals: {
		...API_SITE_CURRENCY,
		total_items: displayForMinorUnit( '4000' ),
		total_items_tax: displayForMinorUnit( '800' ),
		total_fees: displayForMinorUnit( '100' ),
		total_fees_tax: displayForMinorUnit( '20' ),
		total_discount: '0',
		total_discount_tax: '0',
		total_shipping: '0',
		total_shipping_tax: '0',
		total_tax: displayForMinorUnit( '820' ),
		total_price: displayForMinorUnit( '4920' ),
		tax_lines: [
			{
				name: __( 'Sales tax', 'woocommerce' ),
				rate: '20%',
				price: displayForMinorUnit( '820' ),
			},
		],
	},
	errors: [],
	payment_methods: [ 'cod', 'bacs', 'cheque' ],
	payment_requirements: [ 'products' ],
	extensions: {},
};
