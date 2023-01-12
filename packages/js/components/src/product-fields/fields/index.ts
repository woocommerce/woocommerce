/**
 * Internal dependencies
 */
import { registerProductField } from '../api';
import { ProductFieldDefinition } from '../store/types';
import { basicSelectControlSettings } from './basic-select-control';
import { checkboxSettings } from './checkbox';
import { radioSettings } from './radio';
import { toggleSettings } from './toggle';

const getAllProductFields = (): ProductFieldDefinition[] =>
	[
		...[ 'text', 'number' ].map( ( type ) => ( {
			name: type,
			type,
		} ) ),
		toggleSettings,
		radioSettings,
		basicSelectControlSettings,
		checkboxSettings,
	].filter( Boolean );

export const registerCoreProductFields = ( fields = getAllProductFields() ) => {
	fields.forEach( ( field ) => {
		registerProductField( field.name, field );
	} );
};
