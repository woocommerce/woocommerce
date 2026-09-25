/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_weight_unit',
	label: __( 'Weight unit', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		'This controls what unit you will define weights in.',
		'woocommerce'
	),
	Edit: 'select',
	elements: [
		{ value: 'kg', label: 'kg' },
		{ value: 'g', label: 'g' },
		{ value: 'lbs', label: 'lbs' },
		{ value: 'oz', label: 'oz' },
	],
};
