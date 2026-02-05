/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';
import type {
    Cart,
    CartItem,
    CartVariationItem,
    ApiErrorResponse,
    ApiResponse,
    CartResponseTotals,
    Currency,
} from '@woocommerce/types';
import type {
    Store as StoreNotices,
    Notice,
} from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';

export type WooCommerceConfig = {
    products?: {
        [ productId: number ]: ProductData;
    };
    messages?: {
        addedToCartText?: string;
    };
    placeholderImgSrc?: string;
    currency?: Currency;
};

export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

export type OptimisticCartItem = {
    key?: string | undefined;
    id: number;
    quantity: number;
    variation?: CartVariationItem[];
    type: string;
};

export type ClientCartItem = Omit< OptimisticCartItem, 'variation' > & {
    variation?: SelectedAttributes[];
};

export type VariationData = {
    attributes: Record< string, string >;
    is_in_stock: boolean;
    sold_individually: boolean;
    price_html?: string;
    image_id?: number;
    availability?: string;
    variation_description?: string;
    sku?: string;
    weight?: string;
    dimensions?: string;
    min?: number;
    max?: number;
    step?: number;
};

export type ProductData = {
    type: string;
    is_in_stock: boolean;
    sold_individually: boolean;
    price_html?: string;
    image_id?: number;
    availability?: string;
    sku?: string;
    weight?: string;
    dimensions?: string;
    min?: number;
    max?: number;
    step?: number;
    variations?: Record< number, VariationData >;
};

type CartUpdateOptions = { showCartUpdatesNotices?: boolean };

export type Store = {
    state: {
        errorMessages?: {
            [ key: string ]: string;
        };
        restUrl: string;
        nonce: string;
        cart: Omit< Cart, 'items' > & {
            items: ( OptimisticCartItem | CartItem )[];
            totals: CartResponseTotals;
        };
    };
    actions: {
        removeCartItem: ( key: string ) => void;
        addCartItem: (
            args: ClientCartItem,
            options?: CartUpdateOptions
        ) => void;
        batchAddCartItems: (
            items: ClientCartItem[],
            options?: CartUpdateOptions
        ) => void;
        // Todo: Check why if I switch to an async function here the types of the store stop working.
        refreshCartItems: () => void;
        showNoticeError: ( error: Error | ApiErrorResponse ) => void;
        updateNotices: ( notices: Notice[], removeOthers?: boolean ) => void;
    };
};

type QuantityChanges = {
    cartItemsPendingQuantity?: string[];
    cartItemsPendingDelete?: string[];
    productsPendingAdd?: number[];
};

type BatchResponse = {
    responses: ApiResponse< Cart >[];
};

/**
 * Type guard to check if an item is a full CartItem or an OptimisticCartItem.
 * 
 * @param {OptimisticCartItem | CartItem} item - The item to check.
 * @return {boolean} True if the item is a CartItem.
 */
function isCartItem( item: OptimisticCartItem | CartItem ): item is CartItem {
    return 'name' in item;
}

/**
 * Checks if the API response indicates an error.
 * 
 * @param {Response} res - The fetch response object.
 * @param {unknown} json - The parsed JSON from the response.
 * @return {boolean} True if the response is an error.
 */
function isApiErrorResponse(
    res: Response,
    json: unknown
): json is ApiErrorResponse {
    return ! res.ok;
}

/**
 * Generates an Error object from an API error response.
 * 
 * @param {ApiErrorResponse} error - The API error response.
 * @return {Error} The generated Error object with code and message.
 */
function generateError( error: ApiErrorResponse ): Error {
    return Object.assign( new Error( error.message || 'Unknown error.' ), {
        code: error.code || 'unknown_error',
    } );
}

/**
 * Helper function to get the total count of items in the cart.
 * 
 * @param {{ items: (OptimisticCartItem | CartItem)[] }} cart - The cart object.
 * @return {number} The total quantity of items in the cart.
 */
// --- HELPER: Get Total Items Count ---
const getCount = ( cart: { items: ( OptimisticCartItem | CartItem )[] } ) => 
    ( cart.items ? cart.items.reduce( ( sum, item ) => sum + item.quantity, 0 ) : 0 );
// -------------------------------------

/**
 * Generates an error notice from an error object.
 * 
 * @param {Error | ApiErrorResponse} error - The error to generate a notice for.
 * @return {Notice} The generated error notice.
 */
const generateErrorNotice = ( error: Error | ApiErrorResponse ): Notice => ( {
    notice: error.message,
    type: 'error',
    dismissible: true,
} );

/**
 * Generates an info notice with a given message.
 * 
 * @param {string} message - The message for the notice.
 * @return {Notice} The generated info notice.
 */
const generateInfoNotice = ( message: string ): Notice => ( {
    notice: message,
    type: 'notice',
    dismissible: true,
} );

/**
 * Generates info notices based on cart updates.
 * 
 * @param {Store['state']['cart']} oldCart - The previous cart state.
 * @param {Cart} newCart - The updated cart state.
 * @param {QuantityChanges} quantityChanges - Pending quantity changes.
 * @return {Notice[]} Array of generated notices for cart changes.
 */
const getInfoNoticesFromCartUpdates = (
    oldCart: Store[ 'state' ][ 'cart' ],
    newCart: Cart,
    quantityChanges: QuantityChanges
): Notice[] => {
    const oldItems = oldCart.items;
    const newItems = newCart.items;

    const {
        productsPendingAdd: pendingAdd = [],
        cartItemsPendingQuantity: pendingQuantity = [],
        cartItemsPendingDelete: pendingDelete = [],
    } = quantityChanges;

    // FIX from PR #63078: Removed `! pendingDelete.includes( old.key )` check
    const autoDeletedToNotify = oldItems.filter(
        ( old ) =>
            old.key &&
            isCartItem( old ) &&
            ! newItems.some( ( item ) => old.key === item.key )
    );

    // FIX from PR #63078: Added `old.quantity > 0` and removed `! pendingAdd.includes`
    const autoUpdatedToNotify = newItems.filter( ( item ) => {
        if ( ! isCartItem( item ) ) {
            return false;
        }
        const old = oldItems.find( ( o ) => o.key === item.key );
        return old
            ? ! pendingQuantity.includes( item.key ) &&
                    item.quantity !== old.quantity &&
                    old.quantity > 0
            : false;
    } );
    return [
        ...autoDeletedToNotify.map( ( item ) =>
            // TODO: move the message template to iAPI config.
            generateInfoNotice(
                '"%s" was removed from your cart.'.replace( '%s', item.name )
            )
        ),
        ...autoUpdatedToNotify.map( ( item ) =>
            // TODO: move the message template to iAPI config.
            generateInfoNotice(
                'The quantity of "%1$s" was changed to %2$d.'
                    .replace( '%1$s', item.name )
                    .replace( '%2$d', item.quantity.toString() )
            )
        ),
    ];
};

// Same as the one in /assets/js/base/utils/variations/does-cart-item-match-attributes.ts.
/**
 * Checks if a cart item's variation attributes match the selected attributes.
 * 
 * @param {OptimisticCartItem} cartItem - The cart item to check.
 * @param {SelectedAttributes[]} selectedAttributes - The selected attributes.
 * @return {boolean} True if the attributes match.
 */
const doesCartItemMatchAttributes = (
    cartItem: OptimisticCartItem,
    selectedAttributes: SelectedAttributes[]
) => {
    if (
        ! Array.isArray( cartItem.variation ) ||
        ! Array.isArray( selectedAttributes )
    ) {
        return false;
    }

    if ( cartItem.variation.length !== selectedAttributes.length ) {
        return false;
    }

    return cartItem.variation.every(
        ( {
            // eslint-disable-next-line
            raw_attribute,
            value,
        }: {
            raw_attribute: string;
            value: string;
        } ) =>
            selectedAttributes.some( ( item: SelectedAttributes ) => {
                return (
                    item.attribute === raw_attribute &&
                    ( item.value.toLowerCase() === value.toLowerCase() ||
                        ( item.value && value === '' ) ) // Handle "any" attribute type
                );
            } )
    );
};

let pendingRefresh = false;
let refreshTimeout = 3000;

// --- GLOBAL REQUEST COUNTER ---
// Used to prevent race conditions by ignoring outdated server responses.
let _requestCounter = 0;
// -------------------------------

/**
 * Emits a custom event to trigger store synchronization.
 * 
 * @param {{ quantityChanges: QuantityChanges }} options - The quantity changes to include in the event detail.
 */
function emitSyncEvent( {
    quantityChanges,
}: {
    quantityChanges: QuantityChanges;
} ) {
    window.dispatchEvent(
        new CustomEvent( 'wc-blocks_store_sync_required', {
            detail: {
                type: 'from_iAPI',
                quantityChanges,
            },
        } )
    );
}

// Todo: export this store once the store is public.
const { state, actions } = store< Store >(
    'woocommerce',
    {
        actions: {
            *removeCartItem( key: string ) {
				/**
				 * Removes an item from the cart.
				 * 
				 * @param {string} key - The key of the item to remove.
				 * @yield {Generator} Handles the removal process asynchronously.
				 */
                const _currentId = ++_requestCounter;
                const previousCart = JSON.stringify( state.cart );

                // Optimistically update the cart
                state.cart.items = state.cart.items.filter(
                    ( item ) => item.key !== key
                );

                try {
                    const res: Response = yield fetch(
                        `${ state.restUrl }wc/store/v1/cart/remove-item`,
                        {
                            method: 'POST',
                            headers: {
                                Nonce: state.nonce,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify( { key } ),
                        }
                    );

                    const json: Cart | ApiErrorResponse = yield res.json();

                    if ( isApiErrorResponse( res, json ) ) {
                        throw generateError( json );
                    }

                    // RACE CONDITION FIX: If this response is older than the latest action, ignore it.
                    if ( _currentId !== _requestCounter ) return;

                    const quantityChanges = { cartItemsPendingDelete: [ key ] };
                    const infoNotices = getInfoNoticesFromCartUpdates(
                        state.cart,
                        json,
                        quantityChanges
                    );
                    const errorNotices = json.errors.map( generateErrorNotice );
                    yield actions.updateNotices(
                        [ ...infoNotices, ...errorNotices ],
                        true
                    );

                    state.cart = json;
                    emitSyncEvent( { quantityChanges } );
                } catch ( error ) {
                    // Only restore if this is still the latest action
                    if ( _currentId === _requestCounter ) {
                        state.cart = JSON.parse( previousCart );
                        // Shows the error notice.
                        actions.showNoticeError( error as Error );
                    }
                }
            },
			/**
			 * Adds an item to the cart or updates an existing one.
			 * 
			 * @param {ClientCartItem} itemData - The item data to add or update.
			 * @param {CartUpdateOptions} options - Options for updating notices.
			 * @yield {Generator} Handles the addition process asynchronously.
			 */
            *addCartItem(
                { id, key, quantity, variation }: ClientCartItem,
                { showCartUpdatesNotices = true }: CartUpdateOptions = {}
            ) {
                const _currentId = ++_requestCounter;
                const a11yModulePromise = import( '@wordpress/a11y' );

                let item = state.cart.items.find( ( cartItem ) => {
                    if ( cartItem.type === 'variation' ) {
                        // If it's a variation, check that attributes match.
                        // While different variations have different attributes,
                        // some variations might accept 'Any' value for an attribute,
                        // in which case, we need to check that the attributes match.
                        if (
                            id !== cartItem.id ||
                            ! cartItem.variation ||
                            ! variation ||
                            cartItem.variation.length !== variation.length
                        ) {
                            return false;
                        }
                        return doesCartItemMatchAttributes(
                            cartItem,
                            variation
                        );
                    }
                    // If no key is provided, rely on the id.
                    return key ? key === cartItem.key : id === cartItem.id;
                } );
                
                const endpoint = item ? 'update-item' : 'add-item';
                const previousCart = JSON.stringify( state.cart );
                const quantityChanges: QuantityChanges = {};

                // Optimistically update the number of items in the cart except
                // if the product is sold individually and is already in the
                // cart.
                let updatedItem = null;
                if ( item ) {
                    const isSoldIndividually =
                        isCartItem( item ) && item.sold_individually;
                    updatedItem = { ...item, quantity };
                    if ( item.key && ! isSoldIndividually ) {
                        quantityChanges.cartItemsPendingQuantity = [ item.key ];
                        item.quantity = quantity;
                    }
                } else {
                    item = {
                        id,
                        quantity,
                        ...( variation && { variation } ),
                    } as OptimisticCartItem;
                    quantityChanges.productsPendingAdd = [ id ];
                    state.cart.items.push( item );
                    updatedItem = item;
                }

                const clientCount = getCount( state.cart );

                // Updates the database.
                try {
                    const res: Response = yield fetch(
                        `${ state.restUrl }wc/store/v1/cart/${ endpoint }`,
                        {
                            method: 'POST',
                            headers: {
                                Nonce: state.nonce,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify( updatedItem ),
                        }
                    );
                    const json: Cart = yield res.json();

                    // Checks if the response contains an error.
                    if ( isApiErrorResponse( res, json ) )
                        throw generateError( json );

                    // RACE CONDITION FIX: Ignore stale responses
                    if ( _currentId !== _requestCounter ) return;

                    // DEGRADATION CHECK: If server returns fewer items than client has, 
                    // keep client state to prevent UI jumps/glitches.
                    // NOTE: This is a "Client Wins" strategy. We intentionally ignore server state
                    // here to preserve UX during rapid clicks. Real discrepancies (e.g. stock depletion)
                    // will be resolved by the next scheduled refresh or page reload.
                    const serverCount = getCount( json );
                    if ( serverCount < clientCount ) {
                        if ( json.errors && json.errors.length > 0 ) {
                            yield actions.updateNotices( json.errors.map( generateErrorNotice ), true );
                        }
                        return; 
                    }

                    const infoNotices = showCartUpdatesNotices
                        ? getInfoNoticesFromCartUpdates(
                                state.cart,
                                json,
                                quantityChanges
                          )
                        : [];
                    const errorNotices = json.errors.map( generateErrorNotice );
                    yield actions.updateNotices(
                        [ ...infoNotices, ...errorNotices ],
                        true
                    );

                    // Updates the local cart.
                    state.cart = json;

                    // Dispatches a legacy event.
                    triggerAddedToCartEvent( {
                        preserveCartData: true,
                    } );

                    const { messages } = getConfig(
                        'woocommerce'
                    ) as WooCommerceConfig;
                    if ( messages?.addedToCartText ) {
                        const { speak } = yield a11yModulePromise;
                        speak( messages.addedToCartText, 'polite' );
                    }

                    // Dispatches the event to sync the @wordpress/data store.
                    emitSyncEvent( { quantityChanges } );
                } catch ( error ) {
                    // Only restore if this is still the latest action
                    if ( _currentId === _requestCounter ) {
                        state.cart = JSON.parse( previousCart );
                        // Shows the error notice.
                        actions.showNoticeError( error as Error );
                    }
                }
            },
			/**
			 * Batch adds multiple items to the cart.
			 * 
			 * @param {ClientCartItem[]} items - Array of items to add.
			 * @param {CartUpdateOptions} options - Options for updating notices.
			 * @yield {Generator} Handles the batch addition asynchronously.
			 */
            *batchAddCartItems(
                items: ClientCartItem[],
                { showCartUpdatesNotices = true }: CartUpdateOptions = {}
            ) {
                const _currentId = ++_requestCounter;
                const a11yModulePromise = import( '@wordpress/a11y' );
                const previousCart = JSON.stringify( state.cart );
                const quantityChanges: QuantityChanges = {};

                // Optimistic Updates
                items.forEach( ( itemData ) => {
                    const t = state.cart.items.find( ( { id: productId } ) => itemData.id === productId );
                    if ( t ) {
                        t.quantity = itemData.quantity;
                        t.key && ( quantityChanges.cartItemsPendingQuantity = [...( quantityChanges.cartItemsPendingQuantity ?? [] ), t.key] );
                    } else {
                        const newItem = {
                            id: itemData.id,
                            quantity: itemData.quantity,
                            ...( itemData.variation && { variation: itemData.variation } ),
                        } as OptimisticCartItem;
                        state.cart.items.push( newItem );
                        quantityChanges.productsPendingAdd = quantityChanges.productsPendingAdd ? [ ...quantityChanges.productsPendingAdd, itemData.id ] : [ itemData.id ];
                    }
                });

                const clientCount = getCount( state.cart );

                // Updates the database.
                try {
                    const requests = items.map( ( item ) => {
                        const existingItem = state.cart.items.find(
                            ( { id: productId } ) => item.id === productId
                        );

                        // Updates existing cart item.
                        if ( existingItem ) {
                            return {
                                method: 'POST',
                                path: `/wc/store/v1/cart/update-item`,
                                headers: {
                                    Nonce: state.nonce,
                                    'Content-Type': 'application/json',
                                },
                                body: existingItem,
                            };
                        }

                        // Adds new cart item.
                        const newItemPayload = {
                            id: item.id,
                            quantity: item.quantity,
                            ...( item.variation && {
                                variation: item.variation,
                            } ),
                        } as OptimisticCartItem;
                        return {
                            method: 'POST',
                            path: `/wc/store/v1/cart/add-item`,
                            headers: {
                                Nonce: state.nonce,
                                'Content-Type': 'application/json',
                            },
                            body: newItemPayload,
                        };
                    } );

                    const res: Response = yield fetch(
                        `${ state.restUrl }wc/store/v1/batch`,
                        {
                            method: 'POST',
                            headers: {
                                Nonce: state.nonce,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify( { requests } ),
                        }
                    );

                    const json: BatchResponse = yield res.json();

                    // Checks if the response contains an error.
                    if ( isApiErrorResponse( res, json ) )
                        throw generateError( json );

                    // RACE CONDITION FIX
                    if ( _currentId !== _requestCounter ) return;

                    const lastSuccessBody = Array.isArray( json.responses )
                        ? json.responses.filter( e => e.status >= 200 && e.status < 300 ).pop()?.body
                        : json;
                    
                    // DEGRADATION CHECK
                    if ( lastSuccessBody && getCount( lastSuccessBody ) < clientCount ) {
                        return;
                    }

                    const errorResponses = Array.isArray( json.responses )
                        ? json.responses.filter(
                                ( response ) =>
                                    response.status < 200 ||
                                    response.status >= 300
                          )
                        : [];

                    const successfulResponses = Array.isArray( json.responses )
                        ? json.responses.filter(
                                ( response ) =>
                                    response.status >= 200 &&
                                    response.status < 300
                          )
                        : [];

                    // Only update the cart and trigger events if there is at least one successful response.
                    if ( successfulResponses.length > 0 ) {
                        const lastSuccessfulCartResponse = successfulResponses[
                            successfulResponses.length - 1
                        ]?.body as Cart;

                        const infoNotices = showCartUpdatesNotices
                            ? getInfoNoticesFromCartUpdates(
                                    state.cart,
                                    lastSuccessfulCartResponse,
                                    quantityChanges
                              )
                            : [];

                        // Generate notices for any error that successful
                        // responses may contain.
                        const errorNotices = successfulResponses.flatMap(
                            ( response ) => {
                                const errors = ( response.body.errors ??
                                    [] ) as ApiErrorResponse[];
                                return errors.map( generateErrorNotice );
                            }
                        );

                        yield actions.updateNotices(
                            [ ...infoNotices, ...errorNotices ],
                            true
                        );

                        // Use the last successful response to update the local cart.
                        state.cart = lastSuccessfulCartResponse;

                        // Dispatches a legacy event.
                        triggerAddedToCartEvent( {
                            preserveCartData: true,
                        } );

                        const { messages } = getConfig(
                            'woocommerce'
                        ) as WooCommerceConfig;
                        if ( messages?.addedToCartText ) {
                            const { speak } = yield a11yModulePromise;
                            speak( messages.addedToCartText, 'polite' );
                        }

                        // Dispatches the event to sync the @wordpress/data store.
                        emitSyncEvent( { quantityChanges } );
                    }

                    // Show error notices for all failed responses.
                    yield actions.updateNotices(
                        errorResponses
                            .filter(
                                ( response ) =>
                                    response.body &&
                                    typeof response.body === 'object'
                            )
                            .map( ( { body } ) =>
                                generateErrorNotice( body as ApiErrorResponse )
                            )
                    );
                } catch ( error ) {
                    // Only restore if this is still the latest action
                    if ( _currentId === _requestCounter ) {
                        state.cart = JSON.parse( previousCart );
                        // Shows the error notice.
                        actions.showNoticeError( error as Error );
                    }
                }
            },
			/**
			 * Refreshes the cart items from the server.
			 * 
			 * @yield {Generator} Handles the refresh process asynchronously.
			 */
            *refreshCartItems() {
                if ( pendingRefresh ) return;
                pendingRefresh = true;
                
                // PROTECTION: Remember ID at the moment of request start
                const _refreshId = _requestCounter;

                try {
                    const res: Response = yield fetch(
                        `${ state.restUrl }wc/store/v1/cart`,
                        {
                            method: 'GET',
                            cache: 'no-store',
                            headers: { 'Content-Type': 'application/json' },
                        }
                    );
                    const json: Cart = yield res.json();

                    // Checks if the response contains an error.
                    if ( isApiErrorResponse( res, json ) )
                        throw generateError( json );

                    // CRITICAL CHECK: If we clicked again while loading (_requestCounter changed),
                    // the data (json) is already stale. We ignore it to prevent UI corruption.
                    if ( _refreshId !== _requestCounter ) {
                        return;
                    }

                    // Updates the local cart.
                    state.cart = json;

                    // Resets the timeout.
                    refreshTimeout = 3000;
                } catch ( error ) {
                    // If error - check ID before retry
                    if ( _refreshId === _requestCounter ) {
                        setTimeout( actions.refreshCartItems, refreshTimeout );
                        // Increases the timeout exponentially.
                        refreshTimeout *= 2;
                    }
                } finally {
                    pendingRefresh = false;
                }
            },
			/**
			 * Shows an error notice based on the provided error.
			 * 
			 * @param {Error | ApiErrorResponse} error - The error to display.
			 * @yield {Generator} Handles notice addition asynchronously.
			 */
            *showNoticeError( error: Error | ApiErrorResponse ) {
                // Todo: Use the module exports instead of `store()` once the store-notices
                // store is public.
                yield import( '@woocommerce/stores/store-notices' );
                const { actions: noticeActions } = store< StoreNotices >(
                    'woocommerce/store-notices',
                    {},
                    {
                        lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
                    }
                );

                const { code, message } = error as ApiErrorResponse;

                const userFriendlyMessage =
                    state.errorMessages?.[ code ] || message;

                // Todo: Check what should happen if the notice is already displayed.
                noticeActions.addNotice( {
                    notice: userFriendlyMessage,
                    type: 'error',
                    dismissible: true,
                } );

                // Emmits console.error for troubleshooting.
                // eslint-disable-next-line no-console
                console.error( error );
            },
			/**
			 * Updates notices in the store-notices.
			 * 
			 * @param {Notice[]} newNotices - Array of new notices to add.
			 * @param {boolean} removeOthers - Whether to remove existing notices.
			 * @yield {Generator} Handles notice updates asynchronously.
			 */
            *updateNotices( newNotices: Notice[] = [], removeOthers = false ) {
                // Todo: Use the module exports instead of `store()` once the store-notices
                // store is public.
                yield import( '@woocommerce/stores/store-notices' );
                const { state: noticeState, actions: noticeActions } =
                    store< StoreNotices >(
                        'woocommerce/store-notices',
                        {},
                        {
                            lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
                        }
                    );

                // Todo: Check what should happen if the notice is already displayed.
                const noticeIds = newNotices.map( ( notice ) =>
                    noticeActions.addNotice( notice )
                );

                const { notices } = noticeState;
                if ( removeOthers ) {
                    notices
                        .map( ( { id } ) => id )
                        .filter( ( id ) => ! noticeIds.includes( id ) )
                        .forEach( ( id ) => noticeActions.removeNotice( id ) );
                }
            },
        },
    },
    { lock: true }
);

window.addEventListener(
    'wc-blocks_store_sync_required',
    async ( event: Event ) => {
        const customEvent = event as CustomEvent< {
            type: string;
            id: number;
        } >;
        if ( customEvent.detail.type === 'from_@wordpress/data' ) {
            actions.refreshCartItems();
        }
    }
);
