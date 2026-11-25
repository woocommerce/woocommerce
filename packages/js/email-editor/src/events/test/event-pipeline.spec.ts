/**
 * External dependencies
 */
import { addFilter, removeAllFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	dispatcher,
	EMAIL_STRING,
	recordEvent,
	recordEventOnce,
} from '../event-pipeline';

const HOOK = 'woocommerce_email_editor_events_tracking_enabled';

describe( 'event pipeline', () => {
	let received: Record< string, unknown >[];
	const listener = ( event: Event ) =>
		received.push( ( event as CustomEvent ).detail );

	beforeEach( () => {
		received = [];
		dispatcher.addEventListener( EMAIL_STRING, listener );
	} );

	afterEach( () => {
		dispatcher.removeEventListener( EMAIL_STRING, listener );
		removeAllFilters( HOOK );
	} );

	it( 'sends nothing while tracking is off', () => {
		recordEvent( 'opened', { id: 1 } );
		recordEventOnce( 'opened_once' );

		expect( received ).toEqual( [] );
	} );

	it( 'prefixes the event name and wraps data that is not an object', () => {
		addFilter( HOOK, 'test', () => true );

		recordEvent( 'opened', { id: 1 } );
		recordEvent( 'clicked', 'button' as unknown as object );

		expect( received ).toEqual( [
			{ name: 'email_editor_events_opened', id: 1 },
			{ name: 'email_editor_events_clicked', data: 'button' },
		] );
	} );

	it( 'sends a once-only event a single time for the same name and data size', () => {
		addFilter( HOOK, 'test', () => true );

		recordEventOnce( 'loaded', { a: 1 } );
		recordEventOnce( 'loaded', { a: 1 } );
		recordEventOnce( 'loaded', { a: 12 } );

		expect( received ).toHaveLength( 2 );
	} );
} );
