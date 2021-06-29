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

const displayWithTax = getSetting( 'displayCartPricesIncludingTax', false );
// Sample data for cart block.
// This closely resembles the data returned from the Store API /cart endpoint.
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/src/RestApi/StoreApi#cart-api
export const previewCart: CartResponse = {
	coupons: [],
	shipping_rates: getSetting( 'shippingMethodsExist', false )
		? previewShippingRates
		: [],
	items: [
		{
			key: '1',
			id: 1,
			quantity: 2,
			name: __( 'Beanie', 'woo-gutenberg-products-block' ),
			short_description: __(
				'Warm hat for winter',
				'woo-gutenberg-products-block'
			),
			description:
				'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
			sku: 'woo-beanie',
			permalink: 'https://example.org',
			low_stock_remaining: 2,
			backorders_allowed: false,
			show_backorder_badge: false,
			sold_individually: false,
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
					attribute: __( 'Color', 'woo-gutenberg-products-block' ),
					value: __( 'Yellow', 'woo-gutenberg-products-block' ),
				},
				{
					attribute: __( 'Size', 'woo-gutenberg-products-block' ),
					value: __( 'Small', 'woo-gutenberg-products-block' ),
				},
			],
			prices: {
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				price: displayWithTax ? '800' : '640',
				regular_price: displayWithTax ? '800' : '640',
				sale_price: displayWithTax ? '800' : '640',
				raw_prices: {
					precision: 6,
					price: displayWithTax ? '8000000' : '6400000',
					regular_price: displayWithTax ? '8000000' : '6400000',
					sale_price: displayWithTax ? '8000000' : '6400000',
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
				line_subtotal: displayWithTax ? '1600' : '1280',
				line_subtotal_tax: '0',
				line_total: '1600',
				line_total_tax: displayWithTax ? '0' : '320',
			},
			extensions: {},
		},
		{
			key: '2',
			id: 2,
			quantity: 1,
			name: __( 'Cap', 'woo-gutenberg-products-block' ),
			short_description: __(
				'Lightweight baseball cap',
				'woo-gutenberg-products-block'
			),
			description:
				'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
			sku: 'woo-cap',
			permalink: 'https://example.org',
			backorders_allowed: false,
			show_backorder_badge: false,
			sold_individually: false,
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
					attribute: __( 'Color', 'woo-gutenberg-products-block' ),
					value: __( 'Orange', 'woo-gutenberg-products-block' ),
				},
			],
			prices: {
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				price: displayWithTax ? '1400' : '1120',
				regular_price: displayWithTax ? '1600' : '1280',
				sale_price: displayWithTax ? '1400' : '1120',
				raw_prices: {
					precision: 6,
					price: displayWithTax ? '14000000' : '11200000',
					regular_price: displayWithTax ? '16000000' : '12800000',
					sale_price: displayWithTax ? '14000000' : '11200000',
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
				line_subtotal: displayWithTax ? '1400' : '1120',
				line_subtotal_tax: displayWithTax ? '0' : '280',
				line_total: '1400',
				line_total_tax: displayWithTax ? '0' : '280',
			},
			extensions: {},
		},
	],
	fees: [],
	items_count: 3,
	items_weight: 0,
	needs_payment: true,
	needs_shipping: getSetting( 'shippingEnabled', true ),
	has_calculated_shipping: true,
	extensions: {},
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
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		total_items: displayWithTax ? '3000' : '2400',
		total_items_tax: '0',
		total_fees: '0',
		total_fees_tax: '0',
		total_discount: '0',
		total_discount_tax: '0',
		total_shipping: '0',
		total_shipping_tax: '0',
		total_tax: '600',
		total_price: '3000',
		tax_lines: [
			{
				name: __( 'Sales tax', 'woo-gutenberg-products-block' ),
				rate: '20%',
				price: 600,
			},
		],
	},
};
