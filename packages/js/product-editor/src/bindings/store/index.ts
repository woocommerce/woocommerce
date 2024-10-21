/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';

export const store = 'woocommerce/bindings';

const bindingsStore = createReduxStore( store, {
	actions,
	selectors,
	reducer,
} );

export default function registerBindingsStore() {
	register( bindingsStore );
}
