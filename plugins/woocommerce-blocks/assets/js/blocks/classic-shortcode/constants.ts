/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TemplateDetails } from './types';

export const TYPES = {
	cart: 'cart',
	checkout: 'checkout',
};
export const PLACEHOLDERS = {
	cart: 'cart',
	checkout: 'checkout',
};

export const TEMPLATES: TemplateDetails = {
	cart: {
		type: TYPES.cart,
		// Title shows up in the list view in the site editor.
		title: __( 'Cart Shortcode', 'woo-gutenberg-products-block' ),
		// Description in the site editor.
		description: __(
			'Renders the classic cart shortcode.',
			'woo-gutenberg-products-block'
		),
		placeholder: PLACEHOLDERS.cart,
	},
	checkout: {
		type: TYPES.checkout,
		title: __( 'Checkout Cart', 'woo-gutenberg-products-block' ),
		description: __(
			'Renders the classic checkout shortcode.',
			'woo-gutenberg-products-block'
		),
		placeholder: PLACEHOLDERS.checkout,
	},
};
