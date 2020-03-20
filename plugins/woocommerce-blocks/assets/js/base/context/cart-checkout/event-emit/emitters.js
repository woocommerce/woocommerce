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
 * @param {*}      data      Data passed along to the observer when it is
 *                           invoked.
 *
 * @return {Promise} A promise that resolves to true after all observers have
 *                   executed.
 */
export const emitEvent = async ( observers, eventType, data ) => {
	const observersByType = observers[ eventType ] || [];
	for ( let i = 0; i < observersByType.length; i++ ) {
		try {
			await Promise.resolve( observersByType[ i ]( data ) );
		} catch ( e ) {
			// we don't care about errors blocking execution, but will
			// console.error for troubleshooting.
			// eslint-disable-next-line no-console
			console.error( e );
		}
	}
	return true;
};

/**
 * Emits events on registered observers for the provided type and passes along
 * the provided data. This event emitter will abort and return any value from
 * observers that do not return true.
 *
 * @param {Object} observers The registered observers to omit to.
 * @param {string} eventType The event type being emitted.
 * @param {*}      data      Data passed along to the observer when it is
 *                           invoked.
 *
 * @return {Promise} Returns a promise that resolves to either boolean or the
 *                   return value of the aborted observer.
 */
export const emitEventWithAbort = async ( observers, eventType, data ) => {
	const observersByType = observers[ eventType ] || [];
	for ( let i = 0; i < observersByType.length; i++ ) {
		try {
			const response = await Promise.resolve(
				observersByType[ i ]( data )
			);
			if ( response !== true ) {
				return response;
			}
		} catch ( e ) {
			// we don't handle thrown errors but just console.log for
			// troubleshooting
			// eslint-disable-next-line no-console
			console.error( e );
		}
	}
	return true;
};
