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
	id: 'enabled',
	type: 'boolean',
	Edit: 'toggle',
	label: __( 'Enable direct bank transfers', 'woocommerce' ),
};
