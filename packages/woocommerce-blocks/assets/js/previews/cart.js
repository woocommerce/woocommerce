/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	SHIPPING_METHODS_EXIST,
	WC_BLOCKS_ASSET_URL,
	SHIPPING_ENABLED,
} from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { previewShippingRates } from './shipping-rates';

// Sample data for cart block.
// This closely resembles the data returned from the Store API /cart endpoint.
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi#cart-api
export const previewCart = {
	coupons: [],
	shipping_rates: SHIPPING_METHODS_EXIST ? previewShippingRates : [],
	items: [
		{
			key: '1',
			id: 1,
			quantity: 2,
			name: __( 'Beanie', 'woocommerce' ),
			short_description: __(
				'Warm hat for winter',
				'woocommerce'
			),
			description:
				'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
			sku: 'woo-beanie',
			permalink: 'https://example.org',
			low_stock_remaining: 2,
			backorders_allowed: false,
			sold_individually: false,
			images: [
				{
					id: 10,
					src: WC_BLOCKS_ASSET_URL + 'img/beanie.jpg',
					thumbnail: WC_BLOCKS_ASSET_URL + 'img/beanie.jpg',
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
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				price: '800',
				regular_price: '800',
				sale_price: '800',
				raw_prices: {
					precision: 6,
					price: '8000000',
					regular_price: '8000000',
					sale_price: '8000000',
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
				line_subtotal: '1600',
				line_subtotal_tax: '0',
				line_total: '1600',
				line_total_tax: '0',
			},
		},
		{
			key: '2',
			id: 2,
			quantity: 1,
			name: __( 'Cap', 'woocommerce' ),
			short_description: __(
				'Lightweight baseball cap',
				'woocommerce'
			),
			description:
				'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
			sku: 'woo-cap',
			permalink: 'https://example.org',
			backorders_allowed: false,
			sold_individually: false,
			images: [
				{
					id: 11,
					src: WC_BLOCKS_ASSET_URL + 'img/cap.jpg',
					thumbnail: WC_BLOCKS_ASSET_URL + 'img/cap.jpg',
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
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
				price: '1400',
				regular_price: '1600',
				sale_price: '1400',
				raw_prices: {
					precision: 6,
					price: '14000000',
					regular_price: '16000000',
					sale_price: '14000000',
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
				line_subtotal: '1400',
				line_subtotal_tax: '0',
				line_total: '1400',
				line_total_tax: '0',
			},
		},
	],
	items_count: 3,
	items_weight: 0,
	needs_payment: true,
	needs_shipping: SHIPPING_ENABLED,
	totals: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		total_items: '3000',
		total_items_tax: '0',
		total_fees: '0',
		total_fees_tax: '0',
		total_discount: '0',
		total_discount_tax: '0',
		total_shipping: '200',
		total_shipping_tax: '0',
		total_tax: '0',
		total_price: '3200',
		tax_lines: [],
	},
};
