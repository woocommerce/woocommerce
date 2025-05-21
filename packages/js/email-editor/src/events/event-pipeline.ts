/**
 * External dependencies
 */
import { debounce } from 'lodash';
import { applyFilters } from '@wordpress/hooks';

const isEventTrackingEnabled = applyFilters(
	'woocommerce_email_editor_events_tracking_enabled',
	false
);

const EMAIL_STRING = 'email_editor_events';

const dispatcher = new EventTarget();

/**
 * Record event tracking information
 *
 * @param {string} name - event name, in format `this_is_an_event`
 * @param          data - extra properties - please use a valid JSON object
 */
const recordEvent = ( name: string, data = {} ) => {
	if ( ! isEventTrackingEnabled ) {
		return;
	}

	const recordedData = typeof data !== 'object' ? { data } : data;

	const eventData = {
		name: `${ EMAIL_STRING }_${ name }`,
		...recordedData,
	};

	dispatcher.dispatchEvent(
		new CustomEvent( EMAIL_STRING, { detail: eventData } )
	);
};

/**
 * Generally used for when we want to ensure the event is tracked once
 * e.g., on page render or something similar
 * Takes the exact same parameter as `recordEvent`
 */
const recordEventOnce = ( function () {
	const cachedEventName = {};
	return ( name: string, data = {} ) => {
		if ( ! isEventTrackingEnabled ) {
			return;
		}

		const cacheKey = `${ name }_${ JSON.stringify( data ).length }`; // ensure each entry is unique by name and data
		if ( cachedEventName[ cacheKey ] ) {
			return; // do not execute again
		}
		recordEvent( name, data );
		cachedEventName[ cacheKey ] = true;
	};
} )();

const debouncedRecordEvent = debounce( recordEvent, 700 ); // wait 700 milliseconds. The average human reaction speed time is around 250 ms, added some delay for mouse move and keyboard press

export {
	recordEvent,
	recordEventOnce,
	debouncedRecordEvent,
	EMAIL_STRING,
	dispatcher,
	isEventTrackingEnabled,
};
