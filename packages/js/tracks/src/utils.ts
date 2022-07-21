/**
 * Internal dependencies
 */
import { EVENT_NAME_REGEX, PROP_NAME_REGEX } from './constants';
import { ExtraProperties } from '.';

export const isDevelopmentMode = process.env.NODE_ENV === 'development';

export function validateEventNameAndProperties(
	eventName: string,
	props: ExtraProperties | undefined = {}
) {
	let isValid = true;
	if ( ! EVENT_NAME_REGEX.test( eventName ) ) {
		if ( isDevelopmentMode ) {
			/* eslint-disable no-console */
			console.error(
				`A valid event name must be specified. The event name: "${ eventName }" is not valid.`
			);
			/* eslint-enable no-console */
		}
		isValid = false;
	}
	for ( const prop of Object.keys( props ) ) {
		if ( ! PROP_NAME_REGEX.test( prop ) ) {
			if ( isDevelopmentMode ) {
				/* eslint-disable no-console */
				console.error(
					`A valid prop name must be specified. The property name: "${ prop }" is not valid.`
				);
				/* eslint-enable no-console */
			}
			isValid = false;
		}
	}
	return isValid;
}
