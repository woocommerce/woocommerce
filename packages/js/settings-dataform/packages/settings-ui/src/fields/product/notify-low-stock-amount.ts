/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_notify_low_stock_amount',
	label: __( 'Low stock threshold', 'woocommerce' ),
	type: 'integer' as const,
	description: __(
		'When product stock reaches this amount you will be notified via email.',
		'woocommerce'
	),
	isValid: { min: 0 },
};
