/**
 * Internal dependencies
 */
import { EVENT_NAME_REGEX, PROP_NAME_REGEX } from './constants';
import { ExtraProperties } from '.';

function validatePropertyName( propertyName: string ) {
	return PROP_NAME_REGEX.test( propertyName );
}

function validateProperties( props: ExtraProperties ) {
	for ( const prop of Object.keys( props ) ) {
		if ( ! validatePropertyName( prop ) ) {
			return false;
		}
	}
	return true;
}

function validateEventName( eventName: string ) {
	return EVENT_NAME_REGEX.test( eventName );
}

export function validateEventNameAndProperties(
	eventName: string,
	props: ExtraProperties | undefined = {}
) {
	if ( ! validateEventName( eventName ) ) {
		console.error( 'An invalid event name has been sent.' ); // eslint-disable-line no-console
		return false;
	}
	if ( ! validateProperties( props ) ) {
		console.error( 'An invalid prop name has been sent.' ); // eslint-disable-line no-console
		return false;
	}
	return true;
}
