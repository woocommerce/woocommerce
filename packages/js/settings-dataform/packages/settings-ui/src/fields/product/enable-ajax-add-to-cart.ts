/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_enable_ajax_add_to_cart',
	label: __( 'Enable AJAX add to cart buttons on archives', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
