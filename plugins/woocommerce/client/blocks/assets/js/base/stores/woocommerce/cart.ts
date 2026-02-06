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
// Removed unused triggerAddedToCartEvent

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
    return Object.assign( new Error( error.message || __( 'Unknown error.', 'woocommerce' ) ), {
        code: error.code || 'unknown_error',
    } );
}

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

    const autoDeletedToNotify = oldItems.filter(
        ( old ) =>
            old.key &&
            isCartItem( old ) &&
            ! newItems.some( ( item ) => old.key === item.key ) &&
            ! pendingDelete.includes( old.key )
    );

    const autoUpdatedToNotify = newItems.filter( ( item ) => {
        if ( ! isCartItem( item ) ) {
            return false;
        }
        const old = oldItems.find( ( o ) => o.key === item.key );
        if ( old ) {
            return ! pendingQuantity.includes( item.key ) &&
                item.quantity !== old.quantity &&
                old.quantity > 0;
        }
        return ! pendingAdd.includes( item.id );
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
 * Handles both array and object inputs, preserving attribute names.
 * Filters out invalid entries and normalizes casing to prevent collisions.
 *
 * @param {VariationInput} variation - The variation object or array.
 * @returns {string} The normalized stringified variation.
 */
type VariationInput = CartVariationItem[] | Record<string, unknown> | null | undefined;

function normalizeVariation(variation: VariationInput): string {
    if (!variation) return "";
    try {
        const pairs = Array.isArray(variation)
            ? variation.map((attr) => {
                  const a = attr as { attribute?: unknown; raw_attribute?: unknown; value?: unknown };
                  const attrName = String(a.attribute ?? a.raw_attribute ?? '').trim();
                  return [attrName, a.value];
              })
            : Object.entries(variation);

        const normalized = pairs
            .filter(([attribute, value]) => attribute !== '' && value != null)
            .map(([attribute, value]) => ({
                attribute: attribute.toLowerCase(),
                value: value == null ? '' : String(value).toLowerCase(),
            }))
            .sort((a, b) => a.attribute.localeCompare(b.attribute));
        return JSON.stringify(normalized);
    } catch (err) {
        return JSON.stringify(variation);
    }
}

/**
 * Generates a deterministic key for queue operations.
 *
 * @param {OptimisticCartItem | ClientCartItem} item - The cart item.
 * @returns {string} The unique key for the item.
 */
const makeQueueKey = (item: OptimisticCartItem | ClientCartItem): string => {
    if ('key' in item && item.key) return item.key;
    const idPart = item.id != null ? String(item.id) : "noid";
    const variationPart = normalizeVariation(item.variation as VariationInput);
    return `${idPart}::${variationPart}`;
};

/**
 * The delay (in ms) to wait for additional actions before sending a batch request.
 * Optimized for faster response in slow networks.
 */
const COLLECTIVE_DELAY = 100;

/**
 * The threshold of queued items that triggers an immediate batch send.
 * Lowered for better burst handling.
 */
const IMMEDIATE_TRIGGER_SIZE = 2;

/**
 * The maximum number of items allowed in a single batch request payload.
 * Enforced as total across all queues.
 */
const MAX_BATCH_SIZE = 50;

/**
 * Maximum retry attempts for failed operations to prevent infinite loops.
 */
const MAX_RETRIES = 3;

/**
 * Tracks retry attempts per queue key to enforce MAX_RETRIES across batches.
 */
const retryCount = new Map<string, number>();

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

/**
 * Global request counter to prevent race conditions by ignoring outdated responses.
 */
let _requestCounter = 0;

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

    const findMatching = (item: OptimisticCartItem) => {
        const queueKey = makeQueueKey(item);
        return state.cart.items.find(i => i.key === queueKey || (i.id === item.id && normalizeVariation(i.variation) === normalizeVariation(item.variation)));
    };

    updateQueue.forEach((item) => {
        const existing = findMatching(item);
        if (existing) existing.quantity = item.quantity;
    });

    addQueue.forEach((item) => {
        const exists = findMatching(item);
        if (!exists) state.cart.items.push(item);
    });
};

/**
 * Schedules a collective sync operation with optimized debounce for fast response.
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

const { state, actions } = store< Store >(
    'woocommerce',
    {
        actions: {
            *removeCartItem(key: string) {
                const removedItem = state.cart.items.find(i => i.key === key);

                state.cart.items = state.cart.items.filter((t) => t.key !== key);

                if (removedItem) {
                    const queueKey = makeQueueKey(removedItem);
                    addQueue.delete(queueKey);
                    updateQueue.delete(queueKey);
                }

                if (key) {
                    deleteQueue.add(key);
                }

                scheduleCollectiveSync();
            },
            *addCartItem(
                { id, key, quantity, variation }: ClientCartItem
            ) {
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
                
                const hasServerKey = Boolean(item?.key);
                const actionType = item && hasServerKey ? "update-item" : "add-item";
                const payload = item ? { ...item, quantity } : { id, quantity, type: variation ? 'variation' : 'simple', ...(variation && { variation }) };
                const queueKey = makeQueueKey(payload);

                if (item) {
                    item.quantity = quantity;
                } else {
                    state.cart.items.push(payload);
                }

                if (actionType === "update-item") {
                    addQueue.delete(queueKey);
                    updateQueue.set(queueKey, payload);
                } else {
                    updateQueue.delete(queueKey);
                    addQueue.set(queueKey, payload);
                }

                scheduleCollectiveSync();
            },
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
                    const queueKey = existingItem?.key ?? makeQueueKey(itemData);
                    
                    const hasServerKey = Boolean(existingItem?.key);
                    if (existingItem && hasServerKey) {
                        existingItem.quantity = itemData.quantity;
                        addQueue.delete(queueKey);
                        updateQueue.set(queueKey, existingItem);
                    } else {
                        const newItem = {
                            id: itemData.id,
                            quantity: itemData.quantity,
                            type: itemData.variation ? 'variation' : 'simple',
                            ...(itemData.variation && { variation: itemData.variation }),
                        };
                        if (existingItem) {
                            existingItem.quantity = itemData.quantity;
                            updateQueue.delete(queueKey);
                        } else {
                            state.cart.items.push(newItem);
                        }
                        addQueue.set(queueKey, existingItem ?? newItem);
                    }
                });
                scheduleCollectiveSync();
            },
            *processCollectiveActions() {
                if (isProcessing) return;
                if (deleteQueue.size === 0 && updateQueue.size === 0 && addQueue.size === 0) return;

                isProcessing = true;
                if (collectiveTimer) clearTimeout(collectiveTimer);

                const _currentId = ++_requestCounter;

                const currentDeletes = Array.from(deleteQueue);
                const currentUpdates = Array.from(updateQueue.entries());
                const currentAdds = Array.from(addQueue.entries());

                deleteQueue.clear();
                updateQueue.clear();
                addQueue.clear();

                const requestMap = new Map<number, { type: 'remove' | 'update' | 'add'; key: string; item?: OptimisticCartItem }>();
                const batchRequests: any[] = [];

                let remainingSlots = MAX_BATCH_SIZE;

                const processDeletes = currentDeletes.slice(0, remainingSlots);
                remainingSlots -= processDeletes.length;
                const remainderDeletes = currentDeletes.slice(processDeletes.length);
                remainderDeletes.forEach((item) => {
                    deleteQueue.add(item);
                });

                const processUpdates = currentUpdates.slice(0, remainingSlots);
                remainingSlots -= processUpdates.length;
                const remainderUpdates = currentUpdates.slice(processUpdates.length);
                remainderUpdates.forEach(([key, item]) => {
                    updateQueue.set(key, item);
                });

                const processAdds = currentAdds.slice(0, remainingSlots);
                const remainderAdds = currentAdds.slice(processAdds.length);
                remainderAdds.forEach(([key, item]) => {
                    addQueue.set(key, item);
                });

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
                        body: { key, quantity: item.quantity }
                    });
                    requestMap.set(batchRequests.length - 1, { type: 'update', key, item });
                });

                processAdds.forEach(([key, item]) => {
                    const addBody = {
                        id: item.id,
                        quantity: item.quantity,
                        ...(item.variation && { variation: item.variation })
                    };
                    batchRequests.push({
                        method: "POST",
                        path: "/wc/store/v1/cart/add-item",
                        headers: { Nonce: state.nonce, "Content-Type": "application/json" },
                        body: addBody
                    });
                    requestMap.set(batchRequests.length - 1, { type: 'add', key, item });
                });

                try {
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

                    if (_currentId !== _requestCounter) {
                        currentDeletes.forEach((k) => deleteQueue.add(k));
                        currentUpdates.forEach(([k, i]) => updateQueue.set(k, i));
                        currentAdds.forEach(([k, i]) => addQueue.set(k, i));
                        return;
                    }

                    const responses = Array.isArray(data.responses) ? data.responses : [];
                    let hasErrors = false;
                    let finalCartState: Cart | null = null;

                    for (let i = responses.length - 1; i >= 0; i--) {
                        if (responses[i].status >= 200 && responses[i].status < 300 && responses[i].body) {
                            finalCartState = responses[i].body;
                            break;
                        }
                    }

                    responses.forEach((res, index) => {
                        const meta = requestMap.get(index);
                        if (!meta) return;
                        if (res.status >= 400) {
                            hasErrors = true;
                            const count = (retryCount.get(meta.key) ?? 0) + 1;
                            if (count <= MAX_RETRIES) {
                                retryCount.set(meta.key, count);
                                if (meta.type === 'remove') {
                                    deleteQueue.add(meta.key);
                                } else if (meta.type === 'update' || meta.type === 'add') {
                                    const targetQueue = meta.type === 'update' ? updateQueue : addQueue;
                                    targetQueue.set(meta.key, meta.item!);
                                }
                            } else {
                                retryCount.delete(meta.key); // drop
                            }
                        } else {
                            retryCount.delete(meta.key); // success clear
                        }
                    });

                    if (finalCartState) {
                        applyServerState(finalCartState);
                        reapplyOptimisticState();
                    }

                    if (hasErrors) {
                        actions.showNoticeError(new Error(__('Some items failed to update. Please refresh or try again.', 'woocommerce')));
                    }
                } catch (err) {
                    if (_currentId === _requestCounter) {
                        actions.showNoticeError(err as Error);
                        yield actions.refreshCartItems();
                    }
                } finally {
                    isProcessing = false;
                    if (deleteQueue.size > 0 || updateQueue.size > 0 || addQueue.size > 0) {
                        setTimeout(() => actions.processCollectiveActions(), 200); // short delay after error
                    }
                }
            },
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
                    const json: unknown = yield res.json();

                    if ( isApiErrorResponse( res, json ) ) {
                        throw generateError( json as ApiErrorResponse );
                    }

                    if ( _refreshId !== _requestCounter ) {
                        return;
                    }

                    applyServerState(json as Cart);
                    reapplyOptimisticState();

                    refreshTimeout = 3000;
                } catch ( error ) {
                    if ( _refreshId === _requestCounter ) {
                        setTimeout( actions.refreshCartItems, refreshTimeout );
                        refreshTimeout = Math.min(refreshTimeout * 3, 30000);
                    }
                } finally {
                    pendingRefresh = false;
                }
            },
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
        const customEvent = event as CustomEvent<{ type?: string }>;
        if ( customEvent.detail?.type === 'from_@wordpress/data' ) {
            actions.refreshCartItems();
        }
    }
);
