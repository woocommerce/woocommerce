/**
 * External dependencies
 */
import type { Reducer, AnyAction } from 'redux';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';
import { defaultCartState, CartState } from './default-state';
import { EMPTY_CART_ERRORS } from '../constants';
import { setIsCustomerDataDirty } from './utils';
import { persistenceLayer } from './persistence-layer';
import { isEditor } from '../utils';

/**
 * Reducer for receiving items related to the cart.
 */
const reducer: Reducer< CartState > = ( state = defaultCartState, action ) => {
	switch ( action.type ) {
		case types.PRODUCT_PENDING_ADD:
			if ( action.isAdding ) {
				const productsPendingAdd = [ ...state.productsPendingAdd ];
				productsPendingAdd.push( action.productId );
				state = {
					...state,
					productsPendingAdd,
				};
				break;
			}
			state = {
				...state,
				productsPendingAdd: state.productsPendingAdd.filter(
					( productId ) => productId !== action.productId
				),
			};
			break;
		case types.SET_ERROR_DATA:
			if ( 'error' in action && action.error ) {
				state = {
					...state,
					errors: [ action.error ],
				};
			}
			break;
		case types.SET_CART_DATA:
			if ( action.response ) {
				state = {
					...state,
					errors: EMPTY_CART_ERRORS,
					cartData: {
						...state.cartData,
						...action.response,
					},
				};
			}
			break;
		case types.APPLYING_COUPON:
			if ( action.couponCode || action.couponCode === '' ) {
				state = {
					...state,
					metaData: {
						...state.metaData,
						applyingCoupon: action.couponCode,
					},
				};
			}
			break;
		case types.SET_BILLING_ADDRESS:
			const billingAddressChanged = Object.keys(
				action.billingAddress
			).some( ( key ) => {
				return (
					action.billingAddress[ key ] !==
					state.cartData.billingAddress?.[ key ]
				);
			} );
			state = {
				...state,
				cartData: {
					...state.cartData,
					billingAddress: {
						...state.cartData.billingAddress,
						...action.billingAddress,
					},
				},
			};
			if ( billingAddressChanged ) {
				setIsCustomerDataDirty( true );
			}
			break;
		case types.SET_SHIPPING_ADDRESS:
			const shippingAddressChanged = Object.keys(
				action.shippingAddress
			).some( ( key ) => {
				return (
					action.shippingAddress[ key ] !==
					state.cartData.shippingAddress?.[ key ]
				);
			} );
			state = {
				...state,
				cartData: {
					...state.cartData,
					shippingAddress: {
						...state.cartData.shippingAddress,
						...action.shippingAddress,
					},
				},
			};
			if ( shippingAddressChanged ) {
				setIsCustomerDataDirty( true );
			}
			break;

		case types.REMOVING_COUPON:
			if ( action.couponCode || action.couponCode === '' ) {
				state = {
					...state,
					metaData: {
						...state.metaData,
						removingCoupon: action.couponCode,
					},
				};
			}
			break;

		case types.ITEM_PENDING_QUANTITY:
			// Remove key by default - handles isQuantityPending==false
			// and prevents duplicates when isQuantityPending===true.
			const keysPendingQuantity = state.cartItemsPendingQuantity.filter(
				( key ) => key !== action.cartItemKey
			);
			if ( action.isPendingQuantity && action.cartItemKey ) {
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
			if ( action.isPendingDelete && action.cartItemKey ) {
				keysPendingDelete.push( action.cartItemKey );
			}
			state = {
				...state,
				cartItemsPendingDelete: keysPendingDelete,
			};
			break;
		case types.RECEIVE_CART_ITEM:
			state = {
				...state,
				errors: EMPTY_CART_ERRORS,
				cartData: {
					...state.cartData,
					items: state.cartData.items.map( ( cartItem ) => {
						if ( cartItem.key === action.cartItem?.key ) {
							return action.cartItem;
						}
						return cartItem;
					} ),
				},
			};
			break;
		case types.UPDATING_CUSTOMER_DATA:
			state = {
				...state,
				metaData: {
					...state.metaData,
					updatingCustomerData: !! action.isResolving,
				},
			};
			break;
		case types.UPDATING_ADDRESS_FIELDS_FOR_SHIPPING_RATES:
			state = {
				...state,
				metaData: {
					...state.metaData,
					updatingAddressFieldsForShippingRates:
						!! action.isResolving,
				},
			};
			break;
		case types.UPDATING_SELECTED_SHIPPING_RATE:
			state = {
				...state,
				metaData: {
					...state.metaData,
					updatingSelectedRate: !! action.isResolving,
				},
			};
			break;
		case types.SET_IS_CART_DATA_STALE:
			state = {
				...state,
				metaData: {
					...state.metaData,
					isCartDataStale: action.isCartDataStale,
				},
			};
			break;
	}
	return state;
};

export type State = ReturnType< typeof reducer >;

/**
 * Updates cached cart data in local storage.
 */
function withPersistenceLayer( cartReducer: Reducer< CartState > ) {
	return ( state: CartState | undefined, action: AnyAction ): CartState => {
		const nextState = cartReducer( state, action );

		if ( nextState.cartData && ! isEditor() ) {
			persistenceLayer.set( nextState.cartData );
		}

		return nextState;
	};
}

export default withPersistenceLayer( reducer );
