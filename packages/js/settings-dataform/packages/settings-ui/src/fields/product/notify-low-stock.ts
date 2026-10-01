/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_notify_low_stock',
	label: __( 'Enable low stock notifications', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
