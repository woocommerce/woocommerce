/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import type { ChequeSettings } from '../types';

export const field: Field< ChequeSettings > = {
	id: 'description',
	type: 'text',
	Edit: 'textarea',
	label: __( 'Description', 'woocommerce' ),
	description: __(
		'Payment method description that the customer will see during checkout.',
		'woocommerce'
	),
};
