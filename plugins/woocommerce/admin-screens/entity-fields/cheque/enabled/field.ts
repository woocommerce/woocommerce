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
	id: 'enabled',
	type: 'boolean',
	Edit: 'toggle',
	label: __( 'Enable check payments', 'woocommerce' ),
};
