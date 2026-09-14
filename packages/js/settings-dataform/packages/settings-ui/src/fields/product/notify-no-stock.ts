/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_notify_no_stock',
	label: __( 'Enable out of stock notifications', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
