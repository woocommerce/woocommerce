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
	hasCollectableRate,
	triggerAddedToCartEvent,
	triggerAddingToCartEvent,
} from '@woocommerce/base-utils';
import {
	type CurriedSelectorsOf,
	type ConfigOf,
	type ActionCreatorsOf,
	type DispatchFunction,
} from '@wordpress/data/build-types/types';
import { __ } from '@wordpress/i18n';
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
import { isObject } from '../../types/type-guards/object';
import {
	getIsCustomerDataDirty,
	setIsCustomerDataDirty,
	setTriggerStoreSyncEvent,
} from './utils';
import { isEditor } from '../utils';
import { store as checkoutStore } from '../checkout';

interface CartThunkArgs {
	select: CurriedSelectorsOf< typeof cartStore >;
	dispatch: ActionCreatorsOf< ConfigOf< typeof cartStore > >;
	registry?: { dispatch: DispatchFunction };
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

		const cartItemsPendingDelete = select.getItemsPendingDelete();

		notifyQuantityChanges( {
			oldCart,
			newCart,
			cartItemsPendingQuantity: select.getItemsPendingQuantityUpdate(),
			cartItemsPendingDelete,
			productsPendingAdd: select.getProductsPendingAdd(),
		} );

		// Clear pending delete status for items no longer in the cart.
		// This handles cases where removing one item causes dependent items
		// to also be removed server-side (e.g., bundled product children
		// removed when their parent bundle is deleted).
		if ( cartItemsPendingDelete.length > 0 ) {
			const newCartItemKeys = new Set(
				newCart.items.map( ( item ) => item.key )
			);
			cartItemsPendingDelete.forEach( ( key ) => {
				if ( ! newCartItemKeys.has( key ) ) {
					dispatch.itemIsPendingDelete( key, false );
				}
			} );
		}

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
 * Updates the checkout store with the shopper's collection preference based on
 * the selected shipping rates in the cart.
 *
 * @param {CartResponse} response
 * @param {CartThunkArgs['registry']} registry
 */
const syncPrefersCollectionFromSelectedShippingRates = (
	response: CartResponse,
	registry?: CartThunkArgs[ 'registry' ]
) => {
	if ( ! registry ) {
		return;
	}

	const selectedMethodIds = response.shipping_rates
		?.flatMap( ( shippingPackage ) => shippingPackage.shipping_rates )
		.filter( ( rate ) => rate.selected )
		.map( ( rate ) => rate.method_id );

	if ( selectedMethodIds?.length ) {
		void registry
			.dispatch( checkoutStore )
			.setPrefersCollection( hasCollectableRate( selectedMethodIds ) );
	}
};

/**
 * POSTs to the /cart/extensions endpoint with the data supplied by the extension.
 *
 * @param {Object} args The data to be posted to the endpoint
 */
export const applyExtensionCartUpdate =
	( args: ExtensionCartUpdateArgs ) =>
	async ( { dispatch, registry }: CartThunkArgs ) => {
		try {
			const { response } = await apiFetchWithHeaders< {
				response: CartResponse;
			} >( {
				path: '/wc/store/v1/cart/extensions',
				method: 'POST',
				data: { namespace: args.namespace, data: args.data },
				cache: 'no-store',
			} );
			// Determine which addresses should be overwritten in the store.
			const raw = args.overwriteDirtyCustomerData;
			const overwrite = isObject( raw )
				? {
						shipping_address: raw.shipping_address === true,
						billing_address: raw.billing_address === true,
				  }
				: {
						shipping_address: raw === true,
						billing_address: raw === true,
				  };

			const isDirty = getIsCustomerDataDirty();

			// Decide per-address: include it unless it's dirty and not being overwritten.
			const includeShipping = overwrite.shipping_address || ! isDirty;
			const includeBilling = overwrite.billing_address || ! isDirty;

			if ( ! includeShipping || ! includeBilling ) {
				const {
					shipping_address: _shipping,
					billing_address: _billing,
					...responseWithoutAddresses
				} = response;

				const cartToReceive: Partial< CartResponse > = {
					...responseWithoutAddresses,
				};

				if ( includeShipping ) {
					cartToReceive.shipping_address = response.shipping_address;
				}
				if ( includeBilling ) {
					cartToReceive.billing_address = response.billing_address;
				}

				dispatch.receiveCart( cartToReceive );
				syncPrefersCollectionFromSelectedShippingRates(
					response,
					registry
				);
				return response;
			}
			dispatch.receiveCart( response );
			syncPrefersCollectionFromSelectedShippingRates(
				response,
				registry
			);
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
 * Saves a cart line item to the saved-for-later shopper list.
 *
 * On success, emits a `wc-blocks_store_sync_required` event with the saved
 * item in `detail.item` so a `woocommerce/shopper-lists` iAPI store on the
 * same page (rendered by a Saved for Later block) can splice the row
 * into its local state — no extra GET, no race window between a slow
 * refetch and concurrent mutations. Same envelope the cart's iAPI → wp.data
 * sync uses to ship payloads (`detail.type === 'from_iAPI'` carries
 * `quantityChanges`); this is the wp.data → iAPI direction of the same
 * pattern.
 *
 * Removing the item from the cart is the caller's responsibility — keep the
 * two awaits separate so save and remove errors can be reported distinctly.
 *
 * @param {string} cartItemKey Cart item to save.
 */
export const saveForLater =
	( cartItemKey: string ) => async (): Promise< { key: string } > => {
		if (
			typeof cartItemKey !== 'string' ||
			cartItemKey.trim().length === 0
		) {
			throw new Error(
				__(
					'A cart item is required to save it for later.',
					'woocommerce'
				)
			);
		}
		const { response } = await apiFetchWithHeaders< {
			response: { key: string };
		} >( {
			path: '/wc/store/v1/shopper-lists/saved-for-later/items',
			method: 'POST',
			data: { cart_item_key: cartItemKey },
			cache: 'no-store',
		} );

		window.dispatchEvent(
			new CustomEvent( 'wc-blocks_store_sync_required', {
				detail: {
					type: 'shopper-list-item-added',
					slug: 'saved-for-later',
					item: response,
				},
			} )
		);

		return response;
	};

/**
 * Tracks AbortControllers per cart item for cancelling in-flight quantity requests.
 */
const quantityAbortControllers = new Map< string, AbortController >();

/**
 * Persists a quantity change for the specified cart item:
 * - Aborts any in-flight request for the same item.
 * - Calls API to set quantity.
 * - If successful, yields action to update store.
 * - If error (except AbortError), yields action to store error.
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

		// Abort any existing in-flight request for this item.
		const existingController = quantityAbortControllers.get( cartItemKey );
		if ( existingController ) {
			existingController.abort();
		}

		// Create new AbortController for this request.
		const abortController =
			typeof AbortController === 'undefined'
				? null
				: new AbortController();
		if ( abortController ) {
			quantityAbortControllers.set( cartItemKey, abortController );
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
				signal: abortController?.signal ?? null,
			} );

			dispatch.receiveCart( response );
			return response;
		} catch ( error ) {
			// Don't treat aborted requests as errors - they were intentionally cancelled.
			if (
				error instanceof DOMException &&
				error.name === 'AbortError'
			) {
				return;
			}
			dispatch.receiveError( isApiErrorResponse( error ) ? error : null );
			return Promise.reject( error );
		} finally {
			// Clean up controller if it's still the current one for this item.
			if (
				quantityAbortControllers.get( cartItemKey ) === abortController
			) {
				quantityAbortControllers.delete( cartItemKey );
			}
			dispatch.itemIsPendingQuantity( cartItemKey, false );
		}
	};

// Facilitates aborting fetch requests for shipping rate selection.
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

		const previousRates = select.getShippingRates();

		try {
			dispatch.shippingRatesBeingSelected( true );

			// Optimistically update the selected flag so the UI (labels, totals)
			// reflects the new rate immediately without waiting for the API.
			dispatch.setCartData( {
				shippingRates: previousRates.map( ( pkg ) => {
					if ( packageId !== null && pkg.package_id !== packageId ) {
						return pkg;
					}
					return {
						...pkg,
						shipping_rates: pkg.shipping_rates.map( ( rate ) => ( {
							...rate,
							selected: rate.rate_id === rateId,
						} ) ),
					};
				} ),
			} );

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
			// Roll back the optimistic update so the UI reflects the server's
			// actual selection rather than a rate the server never committed.
			dispatch.setCartData( {
				shippingRates: previousRates,
			} );
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
	| typeof saveForLater
	| typeof changeCartItemQuantity
	| typeof selectShippingRate
	| typeof updateCustomerData;
