/**
 * External dependencies
 */
import {
	register,
	subscribe,
	createReduxStore,
	select as wpSelect,
	dispatch as wpDispatch,
} from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducers';
import type { SelectFromMap, DispatchFromMap } from '../mapped-types';
import { pushChanges, flushChanges } from './push-changes';
import {
	updatePaymentMethods,
	debouncedUpdatePaymentMethods,
} from './update-payment-methods';
import { ResolveSelectFromMap } from '../mapped-types';
import { hasCartSession } from './persistence-layer';

// Please update from deprecated "registerStore" to "createReduxStore" when this PR is merged:
// https://github.com/WordPress/gutenberg/pull/45513
const store = createReduxStore( STORE_KEY, {
	reducer,
	actions,
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	controls: dataControls,
	selectors,
	resolvers,
	__experimentalUseThunks: true,
} );

register( store );

declare module '@wordpress/data' {
	function dispatch(
		key: typeof STORE_KEY
	): DispatchFromMap< typeof actions >;
	function select( key: typeof STORE_KEY ): SelectFromMap<
		typeof selectors
	> & {
		hasFinishedResolution: ( selector: string ) => boolean;
	};
}

// The resolver for getCartData fires off an API request. But if we know the cart is empty, we can skip the request.
// The only reliable way to check if the cart is empty is to check the cookies.
window.addEventListener( 'load', () => {
	if ( ! hasCartSession() ) {
		wpDispatch( STORE_KEY ).finishResolution( 'getCartData' );
	}
} );

// Pushes changes whenever the store is updated.
subscribe( pushChanges, store );

// This will skip the debounce and immediately push changes to the server when a field is blurred.
document.body.addEventListener( 'focusout', ( event: FocusEvent ) => {
	if (
		event.target &&
		event.target instanceof Element &&
		event.target.tagName.toLowerCase() === 'input'
	) {
		flushChanges();
	}
} );

// First we will run the updatePaymentMethods function without any debounce to ensure payment methods are ready as soon
// as the cart is loaded. After that, we will unsubscribe this function and instead run the
// debouncedUpdatePaymentMethods function on subsequent cart updates.
const unsubscribeUpdatePaymentMethods = subscribe( async () => {
	const didActionDispatch = await updatePaymentMethods();
	if ( didActionDispatch ) {
		// The function we're currently in will unsubscribe itself. When we reach this line, this will be the last time
		// this function is called.
		unsubscribeUpdatePaymentMethods();
		// Resubscribe, but with the debounced version of updatePaymentMethods.
		subscribe( debouncedUpdatePaymentMethods, store );
	}
}, store );

export const CART_STORE_KEY = STORE_KEY;

/**
 * CartDispatchFromMap is a type that maps the cart store's action creators to the dispatch function passed to thunks.
 */
export type CartDispatchFromMap = DispatchFromMap< typeof actions >;

/**
 * CartResolveSelectFromMap is a type that maps the cart store's resolvers and selectors to the resolveSelect function
 * passed to thunks.
 */
export type CartResolveSelectFromMap = ResolveSelectFromMap<
	typeof resolvers & typeof selectors
>;

/**
 * CartSelectFromMap is a type that maps the cart store's selectors to the select function passed to thunks.
 */
export type CartSelectFromMap = SelectFromMap< typeof selectors >;
