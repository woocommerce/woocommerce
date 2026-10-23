/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { SettingsField } from '../../types';

export const field: SettingsField = {
	id: 'enabled',
	type: 'boolean',
	Edit: 'toggle',
	label: __( 'Enable check payments', 'woocommerce' ),
};
