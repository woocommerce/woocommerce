/** @format */
/**
 * External dependencies
 */
import { combineReducers, registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import notices from './notices';

registerStore( 'wc-admin', {
	reducer: combineReducers( {
		...notices.reducers,
	} ),
	actions: {
		...notices.actions,
	},
	selectors: {
		...notices.selectors,
	},
} );
