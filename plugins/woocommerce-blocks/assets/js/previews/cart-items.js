/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import productPicture from './product-image';

// Sample data for cart block line items.
// This closely resembles the data returned from the Store API cart/items endpoint.
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi#cart-items-api
export const previewCartItems = [
	{
		key: '1',
		id: 1,
		quantity: 2,
		name: __( 'Beanie', 'woo-gutenberg-products-block' ),
		summary: __( 'Warm hat for winter', 'woo-gutenberg-products-block' ),
		short_description: __(
			'Warm hat for winter',
			'woo-gutenberg-products-block'
		),
		description:
			'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
		sku: 'woo-beanie',
		permalink: 'https://example.org',
		low_stock_remaining: 2,
		images: [
			{
				id: 10,
				src: productPicture,
				thumbnail: productPicture,
				srcset: '',
				sizes: '',
				name: '',
				alt: '',
			},
		],
		variation: [
			{
				attribute: 'Color',
				value: 'Yellow',
			},
			{
				attribute: 'Size',
				value: 'Small',
			},
		],
		totals: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			line_subtotal: '1299',
			line_subtotal_tax: '0',
			line_total: '1299',
			line_total_tax: '0',
		},
	},
	{
		key: '2',
		id: 2,
		quantity: 1,
		name: __( 'Cap', 'woo-gutenberg-products-block' ),
		summary: __(
			'Lightweight baseball cap',
			'woo-gutenberg-products-block'
		),
		short_description: __(
			'Lightweight baseball cap',
			'woo-gutenberg-products-block'
		),
		description:
			'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
		sku: 'woo-cap',
		permalink: 'https://example.org',
		images: [
			{
				id: 11,
				src: productPicture,
				thumbnail: productPicture,
				srcset: '',
				sizes: '',
				name: '',
				alt: '',
			},
		],
		variation: [
			{
				attribute: 'Color',
				value: 'Orange',
			},
		],
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
			line_total: '1400',
			line_total_tax: '0',
		},
	},
];
