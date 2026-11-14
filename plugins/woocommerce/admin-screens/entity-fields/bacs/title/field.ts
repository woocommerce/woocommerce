/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import type { BacsSettings } from '../types';

export const field: Field< BacsSettings > = {
	id: 'title',
	type: 'text',
	label: __( 'Title', 'woocommerce' ),
	description: __(
		'Payment method name that the customer will see during checkout.',
		'woocommerce'
	),
	placeholder: __( 'Direct bank transfer payments', 'woocommerce' ),
};
