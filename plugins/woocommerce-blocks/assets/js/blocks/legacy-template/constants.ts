/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const TEMPLATES: Record< string, Record< string, string > > = {
	'single-product': {
		title: __(
			'WooCommerce Single Product Template',
			'woo-gutenberg-products-block'
		),
		placeholder: 'single-product',
	},
	'archive-product': {
		title: __(
			'WooCommerce Product Archive Template',
			'woo-gutenberg-products-block'
		),
		placeholder: 'archive-product',
	},
	'taxonomy-product_cat': {
		title: __(
			'WooCommerce Product Taxonomy Template',
			'woo-gutenberg-products-block'
		),
		placeholder: 'archive-product',
	},
	'taxonomy-product_tag': {
		title: __(
			'WooCommerce Product Tag Template',
			'woo-gutenberg-products-block'
		),
		placeholder: 'archive-product',
	},
};
