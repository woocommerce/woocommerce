/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_cart_redirect_after_add',
	label: __(
		'Redirect to the cart page after successful addition',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
