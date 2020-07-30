const getObserversByPriority = ( observers, eventType ) => {
	return observers[ eventType ]
		? Array.from( observers[ eventType ].values() ).sort( ( a, b ) => {
				return a.priority - b.priority;
		  } )
		: [];
};

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
			// we don't care about errors blocking execution, but will
			// console.error for troubleshooting.
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
 * @param {*}      data      Data passed along to the observer when it is
 *                           invoked.
 *
 * @return {Promise} Returns a promise that resolves to either boolean or the
 *                   return value of the aborted observer.
 */
export const emitEventWithAbort = async ( observers, eventType, data ) => {
	const observersByType = getObserversByPriority( observers, eventType );
	let emitterResponse = true;
	for ( const observer of observersByType ) {
		try {
			const response = await Promise.resolve( observer.callback( data ) );
			if (
				typeof response === 'object' &&
				typeof response.type === 'undefined'
			) {
				throw new Error(
					'If you want to abort event emitter processing, your observer must return an object with a type property'
				);
			}
			emitterResponse = typeof response === 'object' ? response : true;
			if ( emitterResponse !== true ) {
				return emitterResponse;
			}
		} catch ( e ) {
			// We don't handle thrown errors but just console.log for troubleshooting.
			// eslint-disable-next-line no-console
			console.error( e );
			return { type: 'error' };
		}
	}
	return emitterResponse;
};
