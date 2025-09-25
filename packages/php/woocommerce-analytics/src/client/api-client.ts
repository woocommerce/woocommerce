/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import debugFactory from 'debug';
/**
 * Internal dependencies
 */
import { API_NAMESPACE, API_ENDPOINT, BATCH_SIZE, DEBOUNCE_DELAY } from './constants';
import type { ApiEvent, ApiFetchResponse } from './types/shared';

const debug = debugFactory( 'wc-analytics:api-client' );

/**
 * API Client for sending analytics events to the WordPress REST API
 */
export class ApiClient {
	private eventQueue: ApiEvent[] = [];
	private debounceTimer: number | null = null;
	private isInitialized: boolean = false;
	/**
	 * Initialize the API client
	 */
	init = (): void => {
		if ( this.isInitialized ) {
			return;
		}

		debug( 'API client initialized' );
		// Send any pending events when the page is about to unload
		window.addEventListener( 'beforeunload', this.flush );
		window.addEventListener( 'pagehide', this.flush );

		this.isInitialized = true;
	};

	/**
	 * Add an event to the queue for batch sending
	 *
	 * @param eventName  - The name of the event.
	 * @param properties - The properties of the event.
	 */
	addEvent = ( eventName: string, properties: Record< string, unknown > = {} ): void => {
		if ( ! this.isInitialized ) {
			debug( 'API client not initialized, skipping event: %s', eventName );
			return;
		}

		debug( 'Recording event via API: "%s" with props %o', eventName, properties );
		const apiEvent: ApiEvent = {
			event_name: eventName,
			properties,
		};

		this.eventQueue.push( apiEvent );
		debug( 'Event added to queue: %s (queue size: %d)', eventName, this.eventQueue.length );

		// Schedule debounced send
		this.debouncedSend();

		// If queue is full, send immediately
		if ( this.eventQueue.length >= BATCH_SIZE ) {
			this.flush();
		}
	};

	/**
	 * Debounced send function
	 */
	private debouncedSend = (): void => {
		if ( this.debounceTimer ) {
			clearTimeout( this.debounceTimer );
		}

		this.debounceTimer = window.setTimeout( () => {
			this.flush();
		}, DEBOUNCE_DELAY );
	};

	/**
	 * Flush all pending events immediately
	 */
	flush = (): void => {
		if ( this.debounceTimer ) {
			clearTimeout( this.debounceTimer );
			this.debounceTimer = null;
		}

		if ( this.eventQueue.length === 0 ) {
			return;
		}

		const eventsToSend = [ ...this.eventQueue ];
		this.eventQueue = [];

		this.sendEvents( eventsToSend );
	};

	/**
	 * Send events to the API
	 *
	 * @param events - The events to send.
	 */
	private sendEvents = async ( events: ApiEvent[] ): Promise< void > => {
		if ( events.length === 0 ) {
			return;
		}

		try {
			debug( 'Sending %d events to API', events.length );

			const response = await apiFetch< ApiFetchResponse >( {
				path: `/${ API_NAMESPACE }/${ API_ENDPOINT }`,
				method: 'POST',
				data: events,
			} );
			debug( 'API response received: %o', response );

			if ( ! response.success ) {
				debug( 'Some events failed to send: %o', response.results );
			}
		} catch ( error ) {
			debug( 'Failed to send events to API: %o', error );
			// Re-add events to queue for potential retry on next event
			this.eventQueue.unshift( ...events );
		}
	};
}
