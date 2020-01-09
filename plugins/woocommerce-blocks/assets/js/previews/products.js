/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import productPicture from './product-image';

export const previewProducts = [
	{
		id: 1,
		name: 'WordPress Pennant',
		variation: '',
		permalink: 'https://example.org',
		sku: 'wp-pennant',
		description: __(
			'Fly your WordPress banner with this beauty! Deck out your office space or add it to your kids walls. This banner will spruce up any space it’s hung!',
			'woo-gutenberg-products-block'
		),
		price: '7.99',
		price_html:
			'<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>7.99</span>',
		images: [
			{
				id: 1,
				src: productPicture,
				thumbnail: productPicture,
				name: 'pennant-1.jpg',
				alt: 'WordPress Pennant',
				srcset: '',
				sizes: '',
			},
		],
		average_rating: 5,
		review_count: 1,
		prices: {
			currency_code: 'GBP',
			decimal_separator: '.',
			thousand_separator: ',',
			decimals: 2,
			price_prefix: '£',
			price_suffix: '',
			price: '7.99',
			regular_price: '9.99',
			sale_price: '7.99',
			price_range: null,
		},
		add_to_cart: {
			text: __( 'Add to cart', 'woo-gutenberg-products-block' ),
			description: __( 'Add to cart', 'woo-gutenberg-products-block' ),
		},
		has_options: false,
		is_purchasable: true,
		is_in_stock: true,
		on_sale: true,
	},
];
