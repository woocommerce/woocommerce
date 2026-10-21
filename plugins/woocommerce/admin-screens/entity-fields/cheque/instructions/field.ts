/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { SettingsField, SettingsRecord } from '../../types';

/**
 * Read the instructions from the v3 endpoint, which returns each setting as a field description
 * with a `value` but takes the plain value back, so an edited record holds the plain value.
 */
function getInstructions( item: SettingsRecord ): string {
	const settings = item[ 'settings' ] as
		Record< string, unknown > | undefined;
	const setting = settings?.[ 'instructions' ];
	if ( typeof setting === 'string' ) {
		return setting;
	}
	const value = ( setting as { value?: unknown } | undefined )?.value;
	return typeof value === 'string' ? value : '';
}

export const field: SettingsField = {
	id: 'instructions',
	type: 'text',
	Edit: 'textarea',
	label: __( 'Instructions', 'woocommerce' ),
	description: __(
		'Instructions that will be added to the thank you page and emails.',
		'woocommerce'
	),
	getValue: ( { item } ) => getInstructions( item ),
	setValue: ( { value }: { value: unknown } ) => ( {
		settings: { instructions: value },
	} ),
};
