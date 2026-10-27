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
	id: 'instructions',
	type: 'text',
	Edit: 'textarea',
	label: __( 'Instructions', 'woocommerce' ),
	description: __(
		'Instructions that will be added to the thank you page and emails.',
		'woocommerce'
	),
	getValue: ( { item } ) => {
		const setting = item.settings.instructions;
		return typeof setting === 'string' ? setting : ( setting?.value ?? '' );
	},
	setValue: ( { value } ) => ( {
		settings: { instructions: String( value ) },
	} ),
};
