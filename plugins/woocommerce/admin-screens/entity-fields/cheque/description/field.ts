/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { SettingsField } from '../../types';

export const field: SettingsField = {
	id: 'description',
	type: 'text',
	Edit: 'textarea',
	label: __( 'Description', 'woocommerce' ),
	description: __(
		'Payment method description that the customer will see during checkout.',
		'woocommerce'
	),
};
