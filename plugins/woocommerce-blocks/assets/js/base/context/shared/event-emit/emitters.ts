/**
 * Internal dependencies
 */
import { getObserversByPriority } from './utils';
import type { EventObserversType } from './types';

/**
 * Emits events on registered observers for the provided type and passes along
 * the provided data.
 *
 * This event emitter will silently catch promise errors, but doesn't care
 * otherwise if any errors are caused by observers. So events that do care
 * should use `emitEventWithAbort` instead.
 *
 * @param {Object} observers The registered observers to omit to.
 * @param {string} eventType The event type being emitted.
 * @param {*}      data      Data passed along to the observer when it is invoked.
 *
 * @return {Promise} A promise that resolves to true after all observers have executed.
 */
export const emitEvent = async (
	observers: EventObserversType,
	eventType: string,
	data: unknown
): Promise< unknown > => {
	const observersByType = getObserversByPriority( observers, eventType );
	const observerResponses = [];
	for ( const observer of observersByType ) {
		try {
			const observerResponse = await Promise.resolve(
				observer.callback( data )
			);
			if ( typeof observerResponse === 'object' ) {
				observerResponses.push( observerResponse );
			}
		} catch ( e ) {
			// we don't care about errors blocking execution, but will console.error for troubleshooting.
			// eslint-disable-next-line no-console
			console.error( e );
		}
	}
	return observerResponses.length ? observerResponses : true;
};

/**
 * Emits events on registered observers for the provided type and passes along
 * the provided data. This event emitter will abort and return any value from
 * observers that return an object which should contain a type property.
 *
 * @param {Object} observers The registered observers to omit to.
 * @param {string} eventType The event type being emitted.
 * @param {*}      data      Data passed along to the observer when it is invoked.
 *
 * @return {Promise} Returns a promise that resolves to either boolean or the return value of the aborted observer.
 */
export const emitEventWithAbort = async (
	observers: EventObserversType,
	eventType: string,
	data: unknown
): Promise< unknown > => {
	const observersByType = getObserversByPriority( observers, eventType );
	for ( const observer of observersByType ) {
		try {
			const response = await Promise.resolve( observer.callback( data ) );
			if ( typeof response !== 'object' || response === null ) {
				continue;
			}
			if ( ! response.hasOwnProperty( 'type' ) ) {
				throw new Error(
					'If you want to abort event emitter processing, your observer must return an object with a type property'
				);
			}
			return response;
		} catch ( e ) {
			// We don't handle thrown errors but just console.log for troubleshooting.
			// eslint-disable-next-line no-console
			console.error( e );
			return { type: 'error' };
		}
	}
	return true;
};
