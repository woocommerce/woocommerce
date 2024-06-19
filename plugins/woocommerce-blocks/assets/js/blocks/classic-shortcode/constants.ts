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
		title: __( 'Cart Shortcode', 'woocommerce' ),
		// Description in the site editor.
		description: __( 'Renders the classic cart shortcode.', 'woocommerce' ),
		placeholder: PLACEHOLDERS.cart,
	},
	checkout: {
		type: TYPES.checkout,
		title: __( 'Checkout Cart', 'woocommerce' ),
		description: __(
			'Renders the classic checkout shortcode.',
			'woocommerce'
		),
		placeholder: PLACEHOLDERS.checkout,
	},
};
