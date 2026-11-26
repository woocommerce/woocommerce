/**
 * Internal dependencies
 */
import type { FieldsScriptParts } from '../../types';
import type { ChequeSettings } from '../types';

export const fieldExtensions: FieldsScriptParts< ChequeSettings >[ string ] = {
	getValue: ( { item } ) => {
		const setting = item.settings.instructions;
		return typeof setting === 'string' ? setting : ( setting?.value ?? '' );
	},
	setValue: ( { value } ) => ( {
		settings: { instructions: String( value ) },
	} ),
};
