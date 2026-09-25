/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_hold_stock_minutes',
	label: __( 'Hold stock (minutes)', 'woocommerce' ),
	type: 'integer' as const,
	description: __(
		'Hold stock (for unpaid orders) for x minutes. When this limit is reached, the pending order will be cancelled. Leave blank to disable.',
		'woocommerce'
	),
	isRequired: true,
	isValid: { min: 2 },
};
