/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';
// @ts-expect-error These files are not TypeScript files.
import * as actions from './actions';
// @ts-expect-error These files are not TypeScript files.
import * as resolvers from './resolvers';
// @ts-expect-error These files are not TypeScript files.
import * as selectors from './selectors';
// @ts-expect-error These files are not TypeScript files.
import reducer from './reducer';
import { STORE_KEY } from './constants';
export const store = createReduxStore(STORE_KEY, {
    reducer,
    actions,
    controls,
    selectors,
    resolvers,
});
register(store);
