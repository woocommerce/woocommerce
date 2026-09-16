/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_stock_email_recipient',
	label: __( 'Notification recipient(s)', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		'Enter recipients (comma separated) that will receive this notification.',
		'woocommerce'
	),
};
