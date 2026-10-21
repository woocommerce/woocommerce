/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { SettingsField } from '../../types';

export const field: SettingsField = {
	id: 'title',
	type: 'text',
	label: __( 'Title', 'woocommerce' ),
	description: __(
		'Payment method name that the customer will see during checkout.',
		'woocommerce'
	),
};
