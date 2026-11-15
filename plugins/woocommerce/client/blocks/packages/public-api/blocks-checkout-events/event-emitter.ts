/**
 * External dependencies
 */
import {
	isErrorResponse,
	isFailResponse,
	isObserverResponse,
	type ObserverResponse,
	responseTypes,
} from '@woocommerce/types';

export type EventListener = (
	data: unknown
) => Promise< ObserverResponse | boolean > | ObserverResponse | boolean;

export type EventListenerRegistrationFunction = (
	listener: EventListener,
	priority?: number
) => VoidFunction;

export interface EventEmitter {
	emit: (
		eventName: string,
		data?: unknown
	) => Promise< ObserverResponse[] >;
	emitWithAbort: (
		eventName: string,
		data?: unknown
	) => Promise< ObserverResponse[] >;
	subscribe: (
		listener: EventListener,
		priority: number,
		eventName: string
	) => VoidFunction;
	createSubscribeFunction: (
		eventName: string
	) => ( listener: EventListener, priority?: number ) => VoidFunction;
}
export interface EventListenerWithPriority {
	listener: EventListener;
	priority: number;
}
/**
 * Create an event emitter.
 *
 * @return The event emitter.
 */
export function createEmitter(): EventEmitter {
	const listeners = new Map< string, EventListenerWithPriority[] >();

	/**
	 * Notify listeners for an event. All subscribed observers will be run.
	 *
	 * @param eventName The event to emit.
	 * @param data      Optional data to pass to the event listeners.
	 */
	const notifyListeners = async ( eventName: string, data: unknown ) => {
		const listenersForEvent = listeners.get( eventName ) || [];
		// We use Array.from to clone the listeners Set. This ensures that we don't run a listener that was added as a
		// response to another listener.
		const clonedListenersByPriority = Array.from( listenersForEvent );
		const responses = [];
		for ( const { listener } of clonedListenersByPriority ) {
			try {
				const observerResponse = await listener( data );
				if ( isObserverResponse( observerResponse ) ) {
					responses.push( observerResponse );
				}
			} catch ( e ) {
				// We don't care about errors blocking execution, but will console.error for troubleshooting.
				// eslint-disable-next-line no-console
				console.error( e );
			}
		}
		return responses;
	};
	/**
	 * Notify listeners with abort, stopping processing if any observer returns an error or fail type.
	 *
	 * @param eventName The event to emit.
	 * @param data      Optional data to pass to the event listeners.
	 */
	const notifyListenersWithAbort = async (
		eventName: string,
		data: unknown
	) => {
		const listenersForEvent = listeners.get( eventName ) || [];
		// We use Array.from to clone the listeners Set. This ensures that we don't run a listener that was added as a
		// response to another listener.
		const clonedListenersByPriority = Array.from( listenersForEvent );
		const responses: ObserverResponse[] = [];
		try {
			for ( const { listener } of clonedListenersByPriority ) {
				const observerResponse = await listener( data );
				if ( isObserverResponse( observerResponse ) ) {
					responses.push( observerResponse );
				}
				if (
					isErrorResponse( observerResponse ) ||
					isFailResponse( observerResponse )
				) {
					return responses;
				}
			}
		} catch ( e ) {
			// We don't care about errors blocking execution, but will console.error for troubleshooting.
			// eslint-disable-next-line no-console
			console.error( e );
			responses.push( { type: responseTypes.ERROR } );
			return responses;
		}
		return responses;
	};

	return {
		/**
		 * Subscribes a listener to an event.
		 *
		 * @param listener  The listener/observer function to subscribe.
		 * @param priority  The priority of the listener. Listeners with lower priority are called first.
		 * @param eventName The event to subscribe to.
		 */
		subscribe( listener, priority = 10, eventName: string ) {
			let listenersForEvent = listeners.get( eventName ) || [];
			// Keep listenerObject here so it can be used to delete the entry from the listeners array later.
			const listenerObject = { listener, priority };

			// Find the correct insertion index to maintain a sorted insert. The alternative is to sort after every
			// insert, which is less efficient.
			const insertIndex = listenersForEvent.findIndex(
				( existing ) => existing.priority > priority
			);

			if ( insertIndex === -1 ) {
				// If no higher priority found, append to end.
				listenersForEvent.push( listenerObject );
			} else {
				// Insert at the correct position, 1 before the next highest priority. This means listeners added with
				// the same priority will be called in the order they were added.
				listenersForEvent.splice( insertIndex, 0, listenerObject );
			}

			listeners.set( eventName, listenersForEvent );
			return () => {
				// Re-get the listeners for the event in case the list was updated before unsubscribe was called.
				listenersForEvent = listeners.get( eventName ) || [];
				listenersForEvent = listenersForEvent.filter(
					( l ) => l !== listenerObject
				);
				listeners.set( eventName, listenersForEvent );
			};
		},

		/**
		 * Emits events on registered observers for the provided event name.
		 *
		 * @param eventName The event to emit.
		 * @param data      Optional data to pass to the event listeners.
		 */
		emit: async ( eventName: string, data: unknown ) => {
			return await notifyListeners( eventName, data );
		},

		/**
		 * Emits events on registered observers for the provided event name. It stops processing
		 * if any observers return an error or fail type.
		 *
		 * @param eventName The event to emit.
		 * @param data      Optional data to pass to the event listeners.
		 */
		emitWithAbort: async ( eventName: string, data: unknown ) => {
			return await notifyListenersWithAbort( eventName, data );
		},

		/**
		 * Creates a wrapper function for subscribing to a specific event.
		 *
		 * This simplifies the plugin API by providing focused subscription methods.
		 * Instead of plugins using the raw `subscribe` method with event names, they
		 * get dedicated functions like `onCheckoutSuccess(callback, priority)`.
		 *
		 * @example
		 * const onCheckoutSuccess = createSubscribeFunction( 'checkout_success' );
		 * // Plugin usage:
		 * onCheckoutSuccess( ( data ) => { ... }, 10 );
		 *
		 * @param eventName - The event to create a subscription function for.
		 * @return A function that accepts a callback and optional priority.
		 */
		createSubscribeFunction( eventName: string ) {
			return ( callback: EventListener, priority = 10 ) =>
				this.subscribe( callback, priority, eventName );
		},
	};
}
