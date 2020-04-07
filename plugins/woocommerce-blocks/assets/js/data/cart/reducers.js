/**
 * External dependencies
 */
import { camelCase, mapKeys } from 'lodash';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';

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
const reducer = (
	state = {
		cartItemsPendingQuantity: [],
		cartItemsPendingDelete: [],
		cartData: {
			coupons: [],
			shippingRates: [],
			shippingAddress: {
				first_name: '',
				last_name: '',
				company: '',
				address_1: '',
				address_2: '',
				city: '',
				state: '',
				postcode: '',
				country: '',
			},
			items: [],
			itemsCount: 0,
			itemsWeight: 0,
			needsShipping: true,
			totals: {
				currency_code: '',
				currency_symbol: '',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '',
				currency_suffix: '',
				total_items: '0',
				total_items_tax: '0',
				total_fees: '0',
				total_fees_tax: '0',
				total_discount: '0',
				total_discount_tax: '0',
				total_shipping: '0',
				total_shipping_tax: '0',
				total_price: '0',
				total_tax: '0',
				tax_lines: [],
			},
		},
		metaData: {},
		errors: [],
	},
	action
) => {
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
		case types.UPDATING_SHIPPING_ADDRESS:
			state = {
				...state,
				metaData: {
					...state.metaData,
					updatingShipping: action.isResolving,
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
