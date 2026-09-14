/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_notify_backorder',
	label: __( 'Enable backorder notifications', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
