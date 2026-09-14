/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_enable_reviews',
	label: __( 'Enable product reviews', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
