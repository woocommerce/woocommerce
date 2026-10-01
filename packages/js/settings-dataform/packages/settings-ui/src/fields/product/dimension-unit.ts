/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_dimension_unit',
	label: __( 'Dimensions unit', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		'This controls what unit you will define lengths in.',
		'woocommerce'
	),
	Edit: 'select',
	elements: [
		{ value: 'm', label: 'm' },
		{ value: 'cm', label: 'cm' },
		{ value: 'mm', label: 'mm' },
		{ value: 'in', label: 'in' },
		{ value: 'yd', label: 'yd' },
	],
};
