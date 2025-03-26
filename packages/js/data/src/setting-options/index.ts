/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
// @ts-expect-error WP core data doesn't explicitly export the actions
// eslint-disable-next-line @woocommerce/dependency-group
import createLocksActions from '@wordpress/core-data/build/locks/actions';
/**
 * Internal dependencies
 */
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducer';

export * from './types';
export const STORE_NAME = 'wc/admin/settings-options' as const;

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions: {
		...actions,
		...createLocksActions(),
	},
	controls,
	selectors,
	resolvers,
} );

register( store );
