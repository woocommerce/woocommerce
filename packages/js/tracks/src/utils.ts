/**
 * Internal dependencies
 */
import { EVENT_NAME_REGEX, PROP_NAME_REGEX } from './constants';
import { ExtraProperties } from '.';

function validatePropertyName( propertyName: string ) {
	return PROP_NAME_REGEX.test( propertyName );
}

export function validateProperties( props: ExtraProperties | undefined = {} ) {
	for ( const prop of Object.keys( props ) ) {
		if ( ! validatePropertyName( prop ) ) {
			return false;
		}
	}
	return true;
}

export function validateEventName( eventName: string ) {
	return EVENT_NAME_REGEX.test( eventName );
}
