/**
 * External dependencies
 */
import {
	Cart,
	CartResponse,
	ApiErrorResponse,
	isApiErrorResponse,
	ExtensionCartUpdateArgs,
	CartShippingPackageShippingRate,
	CartShippingRate,
	BillingAddressShippingAddress,
} from '@woocommerce/types';
import {
	camelCaseKeys,
	triggerAddedToCartEvent,
	triggerAddingToCartEvent,
} from '@woocommerce/base-utils';
import {
	type CurriedSelectorsOf,
	type ConfigOf,
	type ActionCreatorsOf,
} from '@wordpress/data/build-types/types';
import { cartStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import {
	notifyQuantityChanges,
	QuantityChanges,
} from './notify-quantity-changes';
import { updateCartErrorNotices } from './notify-errors';
import { apiFetchWithHeaders } from '../shared-controls';
import {
	getIsCustomerDataDirty,
	setIsCustomerDataDirty,
	setTriggerStoreSyncEvent,
} from './utils';
import { isEditor } from '../utils';
interface CartThunkArgs {
	select: CurriedSelectorsOf< typeof cartStore >;
	dispatch: ActionCreatorsOf< ConfigOf< typeof cartStore > >;
}

/**
 * A thunk used in updating the store with the cart items retrieved from a request. This also notifies the shopper
 * of any unexpected quantity changes occurred.
 *
 * @param {CartResponse} response The response from the API request.
 */
export const receiveCart =
	( response: Partial< CartResponse > ) =>
	( { dispatch, select }: CartThunkArgs ) => {
		const cartResponse = camelCaseKeys( response ) as unknown as Cart;
		const oldCart = select.getCartData();
		const oldCartErrors = [ ...oldCart.errors, ...select.getCartErrors() ];

		dispatch.setCartData( cartResponse );

		// Get the new cart data before showing updates.
		const newCart = select.getCartData();

		notifyQuantityChanges( {
			oldCart,
			newCart,
			cartItemsPendingQuantity: select.getItemsPendingQuantityUpdate(),
			cartItemsPendingDelete: select.getItemsPendingDelete(),
			productsPendingAdd: select.getProductsPendingAdd(),
		} );

		updateCartErrorNotices( newCart.errors, oldCartErrors );
		dispatch.setErrorData( null );
	};

/**
 * Updates the store with the provided cart but omits the customer addresses.
 *
 * This is useful when currently editing address information to prevent it being overwritten from the server.
 *
 * @param {CartResponse} response
 */
export const receiveCartContents =
	( response: Partial< CartResponse > ) =>
	( { dispatch }: CartThunkArgs ) => {
		// eslint-disable-next-line @typescript-eslint/naming-convention
		const { shipping_address, billing_address, ...cartWithoutAddress } =
			response;
		dispatch.receiveCart( cartWithoutAddress );
	};

/**
 * A thunk used in updating the store with cart errors retrieved from a request.
 */
export const receiveError =
	( response: ApiErrorResponse | null = null ) =>
	( { dispatch }: CartThunkArgs ) => {
		if ( ! isApiErrorResponse( response ) ) {
			return;
		}
		if ( response.data?.cart ) {
			dispatch.receiveCart( response?.data?.cart );
		}

		dispatch.setErrorData( response );
	};

/**
 * POSTs to the /cart/extensions endpoint with the data supplied by the extension.
 *
 * @param {Object} args The data to be posted to the endpoint
 */
export const applyExtensionCartUpdate =
	( args: ExtensionCartUpdateArgs ) =>
	async ( { dispatch }: CartThunkArgs ) => {
		try {
			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: '/wc/store/v1/cart/extensions',
				method: 'POST',
				data: { namespace: args.namespace, data: args.data },
				cache: 'no-store',
			} );
			if ( args.overwriteDirtyCustomerData === true ) {
				dispatch.receiveCart( response );
				return response;
			}
			if ( getIsCustomerDataDirty() ) {
				// If the customer data is dirty, we don't want to overwrite it with the response.
				// Remove shipping and billing address from the response and then receive the cart.
				const {
					shipping_address: _,
					billing_address: __,
					...responseWithoutShippingOrBilling
				} = response;
				dispatch.receiveCart( responseWithoutShippingOrBilling );
				return response;
			}
			dispatch.receiveCart( response );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			return Promise.reject( error );
		}
	};

/**
 * Fetch the cart but avoid triggering the event that syncs with the
 * Interactivity API store to avoid infinite loops.
 *
 * @param {QuantityChanges} quantityChanges The quantity changes data included in the sync event.
 * @throws Will throw an error if there is an API problem.
 */
export const syncCartWithIAPIStore =
	( {
		cartItemsPendingQuantity,
		cartItemsPendingDelete,
		productsPendingAdd,
	}: QuantityChanges ) =>
	async ( { dispatch, select }: CartThunkArgs ) => {
		try {
			// Dispatch pending state actions to show loading indicators
			// before fetching the updated cart data

			// Set pending add states for new products
			if ( productsPendingAdd && productsPendingAdd.length > 0 ) {
				productsPendingAdd.forEach( ( productId ) => {
					dispatch.setProductsPendingAdd( productId, true );
				} );
			}

			// Set pending quantity states for items being updated
			if (
				cartItemsPendingQuantity &&
				cartItemsPendingQuantity.length > 0
			) {
				cartItemsPendingQuantity.forEach( ( cartItemKey ) => {
					dispatch.itemIsPendingQuantity( cartItemKey, true );
				} );
			}

			// Set pending delete states for items being removed
			if ( cartItemsPendingDelete && cartItemsPendingDelete.length > 0 ) {
				cartItemsPendingDelete.forEach( ( cartItemKey ) => {
					dispatch.itemIsPendingDelete( cartItemKey, true );
				} );
			}

			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: '/wc/store/v1/cart',
				method: 'GET',
				cache: 'no-store',
			} );

			const cartResponse = camelCaseKeys( response ) as unknown as Cart;
			const oldCart = select.getCartData();
			const oldCartErrors = [
				...oldCart.errors,
				...select.getCartErrors(),
			];

			// Set data from the response.
			setTriggerStoreSyncEvent( false );
			dispatch.setCartData( cartResponse );
			setTriggerStoreSyncEvent( true );

			// Clear pending states after updating cart data
			if ( productsPendingAdd && productsPendingAdd.length > 0 ) {
				productsPendingAdd.forEach( ( productId ) => {
					dispatch.setProductsPendingAdd( productId, false );
				} );
			}

			if (
				cartItemsPendingQuantity &&
				cartItemsPendingQuantity.length > 0
			) {
				cartItemsPendingQuantity.forEach( ( cartItemKey ) => {
					dispatch.itemIsPendingQuantity( cartItemKey, false );
				} );
			}

			if ( cartItemsPendingDelete && cartItemsPendingDelete.length > 0 ) {
				cartItemsPendingDelete.forEach( ( cartItemKey ) => {
					dispatch.itemIsPendingDelete( cartItemKey, false );
				} );
			}

			// Get the new cart data before showing updates.
			const newCart = select.getCartData();

			notifyQuantityChanges( {
				oldCart,
				newCart,
				cartItemsPendingQuantity,
				cartItemsPendingDelete,
				productsPendingAdd,
			} );

			updateCartErrorNotices( newCart.errors, oldCartErrors );
			dispatch.setErrorData( null );
		} catch ( error ) {
			// Clear pending states on error as well
			if ( productsPendingAdd && productsPendingAdd.length > 0 ) {
				productsPendingAdd.forEach( ( productId ) => {
					dispatch.setProductsPendingAdd( productId, false );
				} );
			}

			if (
				cartItemsPendingQuantity &&
				cartItemsPendingQuantity.length > 0
			) {
				cartItemsPendingQuantity.forEach( ( cartItemKey ) => {
					dispatch.itemIsPendingQuantity( cartItemKey, false );
				} );
			}

			if ( cartItemsPendingDelete && cartItemsPendingDelete.length > 0 ) {
				cartItemsPendingDelete.forEach( ( cartItemKey ) => {
					dispatch.itemIsPendingDelete( cartItemKey, false );
				} );
			}

			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			return Promise.reject( error );
		}
	};

/**
 * Applies a coupon code and either invalidates caches, or receives an error if
 * the coupon cannot be applied.
 *
 * @param {string} couponCode The coupon code to apply to the cart.
 * @throws            Will throw an error if there is an API problem.
 */
export const applyCoupon =
	( couponCode: string ) =>
	async ( { dispatch }: CartThunkArgs ) => {
		try {
			dispatch.receiveApplyingCoupon( couponCode );
			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: '/wc/store/v1/cart/apply-coupon',
				method: 'POST',
				data: {
					code: couponCode,
				},
				cache: 'no-store',
			} );
			dispatch.receiveCartContents( response );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			return Promise.reject( error );
		} finally {
			dispatch.receiveApplyingCoupon( '' );
		}
	};

/**
 * Removes a coupon code and either invalidates caches, or receives an error if
 * the coupon cannot be removed.
 *
 * @param {string} couponCode The coupon code to remove from the cart.
 * @throws            Will throw an error if there is an API problem.
 */
export const removeCoupon =
	( couponCode: string ) =>
	async ( { dispatch }: CartThunkArgs ) => {
		try {
			dispatch.receiveRemovingCoupon( couponCode );
			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: '/wc/store/v1/cart/remove-coupon',
				method: 'POST',
				data: {
					code: couponCode,
				},
				cache: 'no-store',
			} );
			dispatch.receiveCartContents( response );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			return Promise.reject( error );
		} finally {
			dispatch.receiveRemovingCoupon( '' );
		}
	};

type Variation = {
	attribute: string;
	value: string;
};

/**
 * Adds an item to the cart:
 * - Calls API to add item.
 * - If successful, yields action to add item from store.
 * - If error, yields action to store error.
 *
 * @param {number} productId        Product ID to add to cart.
 * @param {number} [quantity=1]     Number of product ID being added to cart.
 * @param {Array}  [variation]      Array of variation attributes for the product.
 * @param {Object} [additionalData] Array of additional fields for the product.
 * @throws         Will throw an error if there is an API problem.
 */
export const addItemToCart =
	(
		productId: number,
		quantity = 1,
		variation: Variation[],
		additionalData: Record< string, unknown > = {}
	) =>
	async ( { dispatch }: CartThunkArgs ) => {
		try {
			dispatch.startAddingToCart( productId );
			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: `/wc/store/v1/cart/add-item`,
				method: 'POST',
				data: {
					...additionalData,
					id: productId,
					quantity,
					variation,
				},
				cache: 'no-store',
			} );
			dispatch.receiveCart( response );
			dispatch.finishAddingToCart( productId );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );

			// Finish adding to cart, but don't dispatch the added to cart event.
			dispatch.finishAddingToCart( productId, false );
			return Promise.reject( error );
		}
	};

/**
 * Sets the metadata to show an item ID being added.
 */
export function startAddingToCart( productId: number ) {
	return async ( { dispatch }: CartThunkArgs ) => {
		triggerAddingToCartEvent();
		dispatch.setProductsPendingAdd( productId, true );
	};
}

/**
 * Removes the metadata of an item ID that was added.
 */
export function finishAddingToCart( productId: number, dispatchEvent = true ) {
	return async ( { dispatch }: CartThunkArgs ) => {
		if ( dispatchEvent ) {
			triggerAddedToCartEvent( { preserveCartData: true } );
		}
		dispatch.setProductsPendingAdd( productId, false );
	};
}

/**
 * Removes specified item from the cart:
 * - Calls API to remove item.
 * - If successful, yields action to remove item from store.
 * - If error, yields action to store error.
 * - Sets cart item as pending while API request is in progress.
 *
 * @param {string} cartItemKey Cart item being updated.
 */
export const removeItemFromCart =
	( cartItemKey: string ) =>
	async ( { dispatch }: CartThunkArgs ) => {
		try {
			dispatch.itemIsPendingDelete( cartItemKey );
			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: `/wc/store/v1/cart/remove-item`,
				data: {
					key: cartItemKey,
				},
				method: 'POST',
				cache: 'no-store',
			} );
			dispatch.receiveCart( response );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			return Promise.reject( error );
		} finally {
			dispatch.itemIsPendingDelete( cartItemKey, false );
		}
	};

/**
 * Persists a quantity change the for specified cart item:
 * - Calls API to set quantity.
 * - If successful, yields action to update store.
 * - If error, yields action to store error.
 *
 * @param {string} cartItemKey Cart item being updated.
 * @param {number} quantity    Specified (new) quantity.
 */
export const changeCartItemQuantity =
	(
		cartItemKey: string,
		quantity: number
		// eslint-disable-next-line @typescript-eslint/no-explicit-any -- unclear how to represent multiple different yields as type
	) =>
	async ( { dispatch, select }: CartThunkArgs ) => {
		const cartItem = select.getCartItem( cartItemKey );
		if ( cartItem?.quantity === quantity ) {
			return;
		}
		try {
			dispatch.itemIsPendingQuantity( cartItemKey );
			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: '/wc/store/v1/cart/update-item',
				method: 'POST',
				data: {
					key: cartItemKey,
					quantity,
				},
				cache: 'no-store',
			} );
			dispatch.receiveCart( response );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			return Promise.reject( error );
		} finally {
			dispatch.itemIsPendingQuantity( cartItemKey, false );
		}
	};

// Facilitates aborting fetch requests.
let abortController: AbortController | null = null;

/**
 * Selects a shipping rate.
 *
 * @param {string}          rateId      The id of the rate being selected.
 * @param {number | string} [packageId] The key of the packages that we will select within.
 */
export const selectShippingRate =
	( rateId: string, packageId: number | null = null ) =>
	async ( { dispatch, select }: CartThunkArgs ) => {
		const selectedShippingRate = select
			.getShippingRates()
			.find(
				( shippingPackage: CartShippingRate ) =>
					shippingPackage.package_id === packageId
			)
			?.shipping_rates.find(
				( rate: CartShippingPackageShippingRate ) =>
					rate.selected === true
			);

		if ( selectedShippingRate?.rate_id === rateId ) {
			// Early return here signifies that the rate is correctly selected.
			// We might have some pending requests that will be trying to set it, so
			// let's abort them just in case.
			if ( abortController ) {
				abortController.abort();
			}
			return;
		}

		if ( isEditor() ) {
			return;
		}

		try {
			dispatch.shippingRatesBeingSelected( true );
			if ( abortController ) {
				abortController.abort();
			}
			abortController =
				typeof AbortController === 'undefined'
					? null
					: new AbortController();

			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: `/wc/store/v1/cart/select-shipping-rate`,
				method: 'POST',
				data: {
					package_id: packageId,
					rate_id: rateId,
				},
				cache: 'no-store',
				signal: abortController?.signal || null,
			} );

			// Remove shipping and billing address from the response, so we don't overwrite what the shopper is
			// entering in the form if rates suddenly appear mid-edit.
			const {
				shipping_address: shippingAddress,
				billing_address: billingAddress,
				...rest
			} = response;

			dispatch.receiveCart( rest );
			dispatch.shippingRatesBeingSelected( false );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			dispatch.shippingRatesBeingSelected( false );
			return Promise.reject( error );
		}
	};

/**
 * Updates the shipping and/or billing address for the customer and returns an updated cart.
 */
export const updateCustomerData =
	(
		// Address data to be updated; can contain both billing_address and shipping_address.
		customerData: Partial< BillingAddressShippingAddress >,
		// If the address is being edited, we don't update the customer data in the store from the response.
		editing = true,
		haveAddressFieldsForShippingRatesChanged = false
	) =>
	async ( { dispatch }: CartThunkArgs ) => {
		try {
			dispatch.updatingCustomerData( true );
			// Signal that the fields needed for shipping rate calculations have changed
			if (
				'shipping_address' in customerData &&
				haveAddressFieldsForShippingRatesChanged
			) {
				dispatch.updatingAddressFieldsForShippingRates( true );
			}

			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: '/wc/store/v1/cart/update-customer',
				method: 'POST',
				data: customerData,
				cache: 'no-store',
			} );
			if ( editing ) {
				dispatch.receiveCartContents( response );
			} else {
				dispatch.receiveCart( response );
			}
			setIsCustomerDataDirty( false );
			return response;
		} catch ( error ) {
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			setIsCustomerDataDirty( true );
			return Promise.reject( error );
		} finally {
			dispatch.updatingCustomerData( false );
			dispatch.updatingAddressFieldsForShippingRates( false );
		}
	};

export type Thunks =
	| typeof receiveCart
	| typeof receiveCartContents
	| typeof receiveError
	| typeof applyExtensionCartUpdate
	| typeof applyCoupon
	| typeof removeCoupon
	| typeof addItemToCart
	| typeof removeItemFromCart
	| typeof changeCartItemQuantity
	| typeof selectShippingRate
	| typeof updateCustomerData;
