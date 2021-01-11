/**
 * External dependencies
 */
import { camelCase, mapKeys } from 'lodash';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';
import { defaultCartState } from '../default-states';

/**
 * Sub-reducer for cart items array.
 *
 * @param   {Array}  state   cartData.items state slice.
 * @param   {Object}  action  Action object.
 */
const cartItemsReducer = ( state = [], action ) => {
	switch ( action.type ) {
		case types.RECEIVE_CART_ITEM:
			// Replace specified cart element with the new data from server.
			return state.map( ( cartItem ) => {
				if ( cartItem.key === action.cartItem.key ) {
					return action.cartItem;
				}
				return cartItem;
			} );
	}
	return state;
};

/**
 * Reducer for receiving items related to the cart.
 *
 * @param   {Object}  state   The current state in the store.
 * @param   {Object}  action  Action object.
 *
 * @return  {Object}          New or existing state.
 */
const reducer = ( state = defaultCartState, action ) => {
	switch ( action.type ) {
		case types.RECEIVE_ERROR:
			state = {
				...state,
				errors: state.errors.concat( action.error ),
			};
			break;
		case types.REPLACE_ERRORS:
			state = {
				...state,
				errors: [ action.error ],
			};
			break;
		case types.RECEIVE_CART:
			state = {
				...state,
				errors: [],
				cartData: mapKeys( action.response, ( _, key ) =>
					camelCase( key )
				),
			};
			break;
		case types.APPLYING_COUPON:
			state = {
				...state,
				metaData: {
					...state.metaData,
					applyingCoupon: action.couponCode,
				},
			};
			break;
		case types.REMOVING_COUPON:
			state = {
				...state,
				metaData: {
					...state.metaData,
					removingCoupon: action.couponCode,
				},
			};
			break;

		case types.ITEM_PENDING_QUANTITY:
			// Remove key by default - handles isQuantityPending==false
			// and prevents duplicates when isQuantityPending===true.
			const keysPendingQuantity = state.cartItemsPendingQuantity.filter(
				( key ) => key !== action.cartItemKey
			);
			if ( action.isPendingQuantity ) {
				keysPendingQuantity.push( action.cartItemKey );
			}
			state = {
				...state,
				cartItemsPendingQuantity: keysPendingQuantity,
			};
			break;
		case types.RECEIVE_REMOVED_ITEM:
			const keysPendingDelete = state.cartItemsPendingDelete.filter(
				( key ) => key !== action.cartItemKey
			);
			if ( action.isPendingDelete ) {
				keysPendingDelete.push( action.cartItemKey );
			}
			state = {
				...state,
				cartItemsPendingDelete: keysPendingDelete,
			};
			break;
		// Delegate to cartItemsReducer.
		case types.RECEIVE_CART_ITEM:
			state = {
				...state,
				errors: [],
				cartData: {
					...state.cartData,
					items: cartItemsReducer( state.cartData.items, action ),
				},
			};
			break;
		case types.UPDATING_CUSTOMER_DATA:
			state = {
				...state,
				metaData: {
					...state.metaData,
					updatingCustomerData: action.isResolving,
				},
			};
			break;
		case types.UPDATING_SELECTED_SHIPPING_RATE:
			state = {
				...state,
				metaData: {
					...state.metaData,
					updatingSelectedRate: action.isResolving,
				},
			};
	}
	return state;
};

export default reducer;
