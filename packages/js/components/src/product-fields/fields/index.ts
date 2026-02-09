/**
 * Internal dependencies
 */
import { registerProductField } from '../api';
import { ProductFieldDefinition } from '../store/types';
import { basicSelectControlSettings } from './basic-select-control';
import { checkboxSettings } from './checkbox';
import { radioSettings } from './radio';
import { textSettings } from './text';
import { toggleSettings } from './toggle';

const getAllProductFields = (): ProductFieldDefinition[] =>
	[
		...[ 'number' ].map( ( type ) => ( {
			name: type,
			type,
		} ) ),
		textSettings,
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
