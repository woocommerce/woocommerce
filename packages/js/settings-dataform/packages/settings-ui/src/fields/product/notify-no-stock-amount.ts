/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_notify_no_stock_amount',
	label: __( 'Out of stock threshold', 'woocommerce' ),
	type: 'integer' as const,
	description: __(
		'When product stock reaches this amount the stock status will change to "out of stock" and you will be notified via email. This setting does not affect existing "in stock" products.',
		'woocommerce'
	),
	isValid: { min: 0 },
};
