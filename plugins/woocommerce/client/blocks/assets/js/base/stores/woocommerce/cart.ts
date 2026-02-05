/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getConfig, store } from '@wordpress/interactivity';
import { doesCartItemMatchAttributes } from '../../utils/variations/does-cart-item-match-attributes';
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
            args: ClientCartItem
        ) => void;
        batchAddCartItems: (
            items: ClientCartItem[]
        ) => void;
        refreshCartItems: () => void;
        showNoticeError: ( error: Error | ApiErrorResponse ) => void;
        updateNotices: ( notices: Notice[], removeOthers?: boolean ) => void;
        processCollectiveActions: () => void;
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
            generateInfoNotice(
                sprintf( __( '"%s" was removed from your cart.', 'woocommerce' ), item.name )
            )
        ),
        ...autoUpdatedToNotify.map( ( item ) =>
            generateInfoNotice(
                sprintf( __( 'The quantity of "%1$s" was changed to %2$d.', 'woocommerce' ), item.name, item.quantity )
            )
        ),
    ];
};

/**
 * Normalizes a product variation object to ensure consistent key generation.
 * Handles nulls, sorts attributes alphabetically, and normalizes values to lowercase
 * to prevent duplicates due to case sensitivity (e.g., "Red" vs "red").
 * 
 * @param {Array|Object} variation - The variation object.
 * @returns {string} The normalized stringified variation.
 */
type VariationInput = CartVariationItem[] | Record<string, unknown> | null | undefined;

function normalizeVariation(variation: VariationInput): string {
    if (!variation) return "";
    try {
        const arr: unknown[] = Array.isArray(variation) ? variation.slice() : Object.values(variation);
        const normalized = arr
            .filter(Boolean)
            .map((attr) => {
                const a = attr as { attribute?: unknown; value?: unknown };
                return {
                    attribute: (a.attribute || "").toString(),
                    value: a.value == null ? "" : String(a.value).toLowerCase(),
                };
            })
            .sort((a, b) => a.attribute.localeCompare(b.attribute));
        return JSON.stringify(normalized);
    } catch (err) {
        return JSON.stringify(variation);
    }
}

/**
 * Generates a deterministic key for queue operations.
 * 
 * @param {OptimisticCartItem} item - The cart item.
 * @returns {string} The unique key for the item.
 */
const makeQueueKey = (item: OptimisticCartItem): string => {
    if (item.key) return item.key;
    const idPart = item.id != null ? String(item.id) : "noid";
    const variationPart = normalizeVariation(item.variation);
    return `${idPart}::${variationPart}`;
};

/**
 * The delay (in ms) to wait for additional actions before sending a batch request.
 * Balances between collecting rapid clicks and providing timely feedback.
 */
const COLLECTIVE_DELAY = 600;

/**
 * The threshold of queued items that triggers an immediate batch send.
 * If the queue reaches this size, the COLLECTIVE_DELAY is ignored to prevent UI lag.
 */
const IMMEDIATE_TRIGGER_SIZE = 5;

/**
 * The maximum number of items allowed in a single batch request payload.
 * Prevents excessively large request bodies that could cause server timeouts.
 */
const MAX_BATCH_SIZE = 50;

/**
 * Queue for item keys pending removal.
 */
let deleteQueue: Set<string> = new Set();

/**
 * Queue for items pending update. Key is the queue key.
 */
let updateQueue: Map<string, OptimisticCartItem> = new Map();

/**
 * Queue for items pending addition. Key is the queue key.
 */
let addQueue: Map<string, OptimisticCartItem> = new Map();

let collectiveTimer: ReturnType<typeof setTimeout> | null = null;
let isProcessing = false;

// --- GLOBAL REQUEST COUNTER ---
// Used to prevent race conditions by ignoring outdated server responses.
let _requestCounter = 0;
// -------------------------------

/**
 * Applies the server cart state to the local state, filtering out items marked for deletion.
 * 
 * @param {Cart} serverCart - The cart object returned by the server.
 */
const applyServerState = (serverCart: Cart) => {
    let items = serverCart.items || [];
    const filteredItems = items.filter(item => !deleteQueue.has(item.key));
    state.cart = { ...serverCart, items: filteredItems };
};

/**
 * Re-applies pending optimistic changes (updates/adds) to the current cart state.
 * Used after a server refresh to ensure UI reflects user actions not yet confirmed by server.
 */
const reapplyOptimisticState = () => {
    if (!state.cart.items) return;

    // Helper to find matching item
    const findMatching = (item: OptimisticCartItem) => {
        const queueKey = makeQueueKey(item);
        return state.cart.items.find(i => i.key === queueKey || (i.id === item.id && normalizeVariation(i.variation) === normalizeVariation(item.variation)));
    };

    // Apply Pending Updates
    updateQueue.forEach((item) => {
        const existing = findMatching(item);
        if (existing) {
            existing.quantity = item.quantity;
        }
    });

    // Apply Pending Adds
    addQueue.forEach((item) => {
        const exists = findMatching(item);
        if (!exists) {
            state.cart.items.push(item);
        }
    });
};

/**
 * Schedules a collective sync operation. Uses an adaptive delay strategy.
 * 
 * @param {boolean} immediate - Whether to force an immediate send (ignoring delay).
 */
const scheduleCollectiveSync = (immediate = false) => {
    if (collectiveTimer) clearTimeout(collectiveTimer);
    
    if (!isProcessing) {
        const totalSize = deleteQueue.size + updateQueue.size + addQueue.size;
        const delay = (immediate || totalSize >= IMMEDIATE_TRIGGER_SIZE) ? 0 : COLLECTIVE_DELAY;

        collectiveTimer = setTimeout(() => {
            actions.processCollectiveActions();
        }, delay);
    }
};

let pendingRefresh = false;
let refreshTimeout = 3000;

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
            /**
             * Optimistically removes an item from the cart and queues the removal request.
             * 
             * @param {string} key - The cart item key to remove.
             */
            *removeCartItem(key: string) {
                // 1. Optimistic UI Update
                state.cart.items = state.cart.items.filter((t) => t.key !== key);
                
                // 2. Queue Management
                deleteQueue.add(key);
                // Remove from other queues if present (Cancel update/add)
                if (updateQueue.has(key)) updateQueue.delete(key);
                if (addQueue.has(key)) addQueue.delete(key);
                
                scheduleCollectiveSync();
            },
            /**
             * Adds an item to the cart or updates an existing one.
             * Handles variation matching and optimistic updates.
             * 
             * @param {ClientCartItem} itemData - The item data to add or update.
             */
            *addCartItem(
                { id, key, quantity, variation }: ClientCartItem
            ) {
                // Cancel deletion if re-adding the same item
                if (key && deleteQueue.has(key)) deleteQueue.delete(key);

                let item = state.cart.items.find( ( cartItem ) => {
                    if ( cartItem.type === 'variation' ) {
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
                    return key ? key === cartItem.key : id === cartItem.id;
                } );
                
                let actionType = item ? "update-item" : "add-item";
                let payload = item ? { ...item, quantity } : { id, quantity, type: variation ? 'variation' : 'simple', ...(variation && { variation }) };
                let queueKey = makeQueueKey(payload);

                // Optimistic UI Update
                if (item) {
                    item.quantity = quantity;
                } else {
                    state.cart.items.push(payload);
                }

                // Queue Management
                if (actionType === "update-item") {
                    updateQueue.set(queueKey, payload);
                } else {
                    addQueue.set(queueKey, payload);
                }

                scheduleCollectiveSync();
            },
            /**
             * Batch adds multiple items to the cart in a single batch operation.
             * 
             * @param {ClientCartItem[]} items - Array of items to add.
             */
            *batchAddCartItems(
                items: ClientCartItem[]
            ) {
                items.forEach((itemData) => {
                    const existingItem = state.cart.items.find((cartItem) => {
                        if (cartItem.type === 'variation' && itemData.variation) {
                            if (
                                itemData.id !== cartItem.id ||
                                !cartItem.variation ||
                                cartItem.variation.length !== itemData.variation.length
                            ) {
                                return false;
                            }
                            return doesCartItemMatchAttributes(cartItem, itemData.variation);
                        }
                        return itemData.id === cartItem.id;
                    });
                    const queueKey = existingItem ? existingItem.key : makeQueueKey(itemData);
                    
                    if (existingItem) {
                        existingItem.quantity = itemData.quantity;
                        updateQueue.set(queueKey, existingItem);
                    } else {
                        const newItem = {
                            id: itemData.id,
                            quantity: itemData.quantity,
                            type: itemData.variation ? 'variation' : 'simple',
                            ...(itemData.variation && { variation: itemData.variation }),
                        };
                        state.cart.items.push(newItem);
                        addQueue.set(queueKey, newItem);
                    }
                });
                scheduleCollectiveSync();
            },
            /**
             * Processes the current queues (add, update, remove) into a single batch request.
             * Handles snapshotting to allow new clicks during network latency.
             * Includes logic for splitting large batches (Chunking) and re-queuing remainder.
             */
            *processCollectiveActions() {
                if (isProcessing) return;
                if (deleteQueue.size === 0 && updateQueue.size === 0 && addQueue.size === 0) return;

                isProcessing = true;
                if (collectiveTimer) clearTimeout(collectiveTimer);

                const _currentId = ++_requestCounter;

                // SNAPSHOT & QUEUE MANAGEMENT
                const currentDeletes = Array.from(deleteQueue);
                const currentUpdates = Array.from(updateQueue.entries());
                const currentAdds = Array.from(addQueue.entries());

                // Clear main queues to allow new interactions during processing
                deleteQueue.clear();
                updateQueue.clear();
                addQueue.clear();

                const requestMap = new Map<number, { type: 'remove' | 'update' | 'add'; key: string; item?: OptimisticCartItem }>();
                const batchRequests: any[] = [];

                // HELPER: takeSlice with explicit queue type
                const takeSlice = (arr: any[], queueType: 'delete' | 'update' | 'add') => {
                    if (arr.length <= MAX_BATCH_SIZE) return arr;
                    const slice = arr.slice(0, MAX_BATCH_SIZE);
                    const remainder = arr.slice(MAX_BATCH_SIZE);
                    
                    if (queueType === 'delete') {
                        remainder.forEach(item => { deleteQueue.add(item); });
                    } else if (queueType === 'add') {
                        remainder.forEach(([key, item]) => { addQueue.set(key, item); });
                    } else if (queueType === 'update') {
                        remainder.forEach(([key, item]) => { updateQueue.set(key, item); });
                    }
                    return slice;
                };

                const processDeletes = takeSlice(currentDeletes, 'delete');
                const processUpdates = takeSlice(currentUpdates, 'update');
                const processAdds = takeSlice(currentAdds, 'add');

                // BUILDING REQUESTS
                processDeletes.forEach(key => {
                    batchRequests.push({
                        method: "POST",
                        path: "/wc/store/v1/cart/remove-item",
                        headers: { Nonce: state.nonce, "Content-Type": "application/json" },
                        body: { key }
                    });
                    requestMap.set(batchRequests.length - 1, { type: 'remove', key });
                });

                processUpdates.forEach(([key, item]) => {
                    batchRequests.push({
                        method: "POST",
                        path: "/wc/store/v1/cart/update-item",
                        headers: { Nonce: state.nonce, "Content-Type": "application/json" },
                        body: item
                    });
                    requestMap.set(batchRequests.length - 1, { type: 'update', key, item });
                });

                processAdds.forEach(([key, item]) => {
                    batchRequests.push({
                        method: "POST",
                        path: "/wc/store/v1/cart/add-item",
                        headers: { Nonce: state.nonce, "Content-Type": "application/json" },
                        body: item
                    });
                    requestMap.set(batchRequests.length - 1, { type: 'add', key, item });
                });

                try {
                    // Determine intent for URL naming (DevTools clarity)
                    let intent = "mixedCart";
                    if (processDeletes.length > 0 && processUpdates.length === 0 && processAdds.length === 0) intent = "removeCart";
                    else if (processDeletes.length === 0 && processUpdates.length > 0 && processAdds.length === 0) intent = "updateCart";
                    else if (processDeletes.length === 0 && processUpdates.length === 0 && processAdds.length > 0) intent = "addCart";

                    const batchResponse = yield fetch(`${state.restUrl}wc/store/v1/batch?intent=${intent}`, {
                        method: "POST",
                        headers: { Nonce: state.nonce, "Content-Type": "application/json" },
                        body: JSON.stringify({ requests: batchRequests }),
                    });

                    const data = yield batchResponse.json();

                    if (isApiErrorResponse(batchResponse, data)) throw generateError(data);

                    // RACE CONDITION PROTECTION
                    if (_currentId !== _requestCounter) {
                        // Stale response: Re-queue everything to prevent data loss
                        processDeletes.forEach(k => { deleteQueue.add(k); });
                        processUpdates.forEach(([k, i]) => { updateQueue.set(k, i); });
                        processAdds.forEach(([k, i]) => { addQueue.set(k, i); });
                        return;
                    }

                    // RESPONSE HANDLING & ERROR RECOVERY
                    const responses = Array.isArray(data.responses) ? data.responses : [];
                    let hasErrors = false;
                    let finalCartState = state.cart; 

                    // Find the last successful body to update the cart state
                    for (let i = responses.length - 1; i >= 0; i--) {
                        if (responses[i].status >= 200 && responses[i].status < 300 && responses[i].body) {
                            finalCartState = responses[i].body;
                            break;
                        }
                    }

                    // Analyze individual responses to handle partial failures
                    responses.forEach((res, index) => {
                        const meta = requestMap.get(index);
                        if (!meta) return;
                        if (res.status >= 400) {
                            hasErrors = true;
                            // Re-queue failed operation to the correct queue
                            if (meta.type === 'remove') {
                                deleteQueue.add(meta.key);
                            } else if (meta.type === 'update' || meta.type === 'add') {
                                const targetQueue = meta.type === 'update' ? updateQueue : addQueue;
                                targetQueue.set(meta.key, meta.item!);
                            }
                        }
                    });

                    // Update State
                    applyServerState(finalCartState);
                    reapplyOptimisticState();

                    if (hasErrors) {
                        actions.showNoticeError(new Error(__('Some items failed to update. Please refresh or try again.', 'woocommerce')));
                    }

                } catch (err) {
                    // NETWORK ERROR HANDLING
                    if (_currentId === _requestCounter) {
                        actions.showNoticeError(err as Error);
                        // Re-queue everything as network state is uncertain
                        processDeletes.forEach(k => { deleteQueue.add(k); });
                        processUpdates.forEach(([k, i]) => { updateQueue.set(k, i); });
                        processAdds.forEach(([k, i]) => { addQueue.set(k, i); });
                        yield actions.refreshCartItems();
                    }
                } finally {
                    isProcessing = false;
                    // If new clicks accumulated while processing (or re-queued items exist), trigger next cycle
                    if (deleteQueue.size > 0 || updateQueue.size > 0 || addQueue.size > 0) {
                        setTimeout(() => actions.processCollectiveActions(), 50);
                    }
                }
            },
            /**
             * Refreshes the cart items from the server.
             * 
             * @yield {Generator} Handles the refresh process asynchronously.
             */
            *refreshCartItems() {
                if (pendingRefresh) return;
                pendingRefresh = true;
                
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

                    if ( isApiErrorResponse( res, json ) )
                        throw generateError( json );

                    if ( _refreshId !== _requestCounter ) {
                        return;
                    }

                    applyServerState(json);
                    reapplyOptimisticState();

                    refreshTimeout = 3000;
                } catch ( error ) {
                    if ( _refreshId === _requestCounter ) {
                        setTimeout( actions.refreshCartItems, refreshTimeout );
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

                noticeActions.addNotice( {
                    notice: userFriendlyMessage,
                    type: 'error',
                    dismissible: true,
                } );

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
                yield import( '@woocommerce/stores/store-notices' );
                const { state: noticeState, actions: noticeActions } =
                    store< StoreNotices >(
                        'woocommerce/store-notices',
                        {},
                        {
                            lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
                        }
                    );

                const noticeIds = newNotices.map( ( notice ) =>
                    noticeActions.addNotice( notice )
                );

                const { notices } = noticeState;
                if ( removeOthers ) {
                    notices
                        .map( ( { id } ) => id )
                        .filter( ( id ) => ! noticeIds.includes( id ) )
                        .forEach( ( id ) => { noticeActions.removeNotice( id ); } );
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
