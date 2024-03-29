/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

export const interactivityStore = store( 'myPlugin', {
	state: {
		someText: 'Hello Universe!',
	},
} );
