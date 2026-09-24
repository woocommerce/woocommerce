/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { AsyncAction } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
} from '@woocommerce/types';
import type {
	Store as StoreNotices,
	Notice,
} from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import { doesCartItemMatchAttributes } from '../../utils/variations/does-cart-item-match-attributes';
import type {
	Store,
	OptimisticCartItem,
	SelectedAttributes,
} from './cart-actions';

// Guard to distinguish between optimistic and cart items.
export function isCartItem(
	item: OptimisticCartItem | CartItem
): item is CartItem {
	return 'name' in item;
}

export function isApiErrorResponse(
	res: Response,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	json: unknown
): json is ApiErrorResponse {
	return ! res.ok;
}

export function generateError( error: ApiErrorResponse ): Error {
	return Object.assign( new Error( error.message || 'Unknown error.' ), {
		code: error.code || 'unknown_error',
	} );
}

export const generateErrorNotice = (
	error: Error | ApiErrorResponse
): Notice => ( {
	notice: error.message,
	type: 'error',
	dismissible: true,
} );

export const generateInfoNotice = ( message: string ): Notice => ( {
	notice: message,
	type: 'notice',
	dismissible: true,
} );

/**
 * Returns `true` when the given cart line matches the product identified by
 * `id` and `variation`, using the same matching logic as `findItemInCart`.
 *
 * Simple items match by `id` equality. Variation items additionally require
 * `variation.length` equality and `doesCartItemMatchAttributes`.
 *
 * @param item      The cart line to test.
 * @param id        The product id to match against.
 * @param variation The variation attributes to match against, if any.
 * @return `true` when the line belongs to the specified product.
 */
export function lineMatchesProduct(
	item: OptimisticCartItem | CartItem,
	id: number,
	variation?: CartVariationItem[] | SelectedAttributes[]
): boolean {
	if ( item.type === 'variation' ) {
		if (
			id !== item.id ||
			! item.variation ||
			! variation ||
			item.variation.length !== variation.length
		) {
			return false;
		}
		return doesCartItemMatchAttributes( item, variation );
	}
	return id === item.id;
}

/**
 * Builds a `Set` of pre-existing cart-line keys to suppress from the
 * "quantity changed" auto-update notice after a successful keyless add.
 *
 * For each product entry in `products`, computes:
 *   - `serverTotal` = sum of the committed server cart's lines matching that
 *     product (using the same matcher as `findItemInCart`).
 *   - `expectedTotal` = pre-add total + sum of posted deltas.
 *
 * When `serverTotal === expectedTotal`, the add was exact for that product
 * (no server-initiated cap, redistribution, or concurrent change), so every
 * pre-existing line key captured for that product is added to the returned
 * set and will be skipped in the auto-UPDATE notice diff.
 *
 * When the totals diverge, the product's keys are left out of the set and
 * the diff fires normally, reporting the server's actual quantity.
 *
 * Only keyless adds should call this helper. Keyed `update-item` changes
 * must never populate `products`; leaving their line keys out of the
 * suppression set ensures the "your change was undone" notice keeps firing.
 *
 * @param products   Per-product capture records, one per added product.
 * @param serverCart The committed server cart to sum against.
 * @return The flat set of pre-existing line keys to suppress.
 */
export function computeKeylessAddSuppressKeys(
	products: Array< {
		/** The product id used for matching. */
		id: number;
		/** The variation attributes used for matching, if any. */
		variation?: CartVariationItem[] | SelectedAttributes[] | undefined;
		/** Sum of all pre-add quantities across matching lines, captured before the optimistic bump. */
		preAddTotal: number;
		/** Sum of all posted deltas for this product in this add cycle. */
		deltaTotal: number;
		/** The pre-existing cart-line keys belonging to this product, captured before the optimistic bump. */
		preExistingKeys: string[];
	} >,
	serverCart: Cart
): Set< string > {
	const suppressKeys = new Set< string >();
	for ( const product of products ) {
		const serverTotal = serverCart.items
			.filter( ( item ) =>
				lineMatchesProduct( item, product.id, product.variation )
			)
			.reduce( ( sum, item ) => sum + item.quantity, 0 );
		const expectedTotal = product.preAddTotal + product.deltaTotal;
		if ( serverTotal === expectedTotal ) {
			for ( const key of product.preExistingKeys ) {
				suppressKeys.add( key );
			}
		}
	}
	return suppressKeys;
}

/**
 * Derives the auto-update and auto-removal info notices from the diff between
 * the post-optimistic cart and the committed server cart.
 *
 * Auto-removal notices fire for lines present in `oldCart` that the server
 * dropped entirely (stock removal, product deletion, etc.). Because
 * `oldCart` is the post-optimistic snapshot, user-initiated removals are
 * already absent and do not produce spurious notices.
 *
 * Auto-update notices fire for server lines whose quantity differs from the
 * post-optimistic snapshot, with one suppression rule: any line whose key
 * appears in `suppressKeys` is skipped unconditionally. The action populates
 * `suppressKeys` with the pre-existing keys of products whose keyless add was
 * exact (server total == pre-add total + posted delta), so a successful keyless
 * add never emits a spurious "quantity changed" notice regardless of which
 * server line received the delta. Genuine server changes (cap, clamp, concurrent
 * mutation) still notify because they make the per-product totals diverge and
 * the keys are left out of the set.
 *
 * Keyed `update-item` changes and `removeCartItem` never populate
 * `suppressKeys` (the parameter defaults to an empty set), so their notice
 * behavior is byte-for-byte unchanged.
 *
 * @param oldCart      The post-optimistic cart snapshot used as the diff baseline.
 * @param newCart      The committed server cart to diff against.
 * @param suppressKeys Keys of pre-existing lines whose product's add was exact;
 *                     these lines are skipped in the auto-UPDATE filter.
 * @return The list of info notices to surface to the shopper.
 */
export const getInfoNoticesFromCartUpdates = (
	oldCart: Store[ 'state' ][ 'cart' ],
	newCart: Cart,
	suppressKeys: Set< string > = new Set()
): Notice[] => {
	const oldItems = oldCart.items;
	const newItems = newCart.items;

	// Items auto-removed by the server (stock change, product deleted, etc.).
	// We pass the optimistic snapshot as oldCart, so user-initiated removals
	// are already absent and do not generate spurious notices here.
	// Filtering on the `isCartItem` type guard first (rather than folding it
	// into a single predicate) narrows the result to `CartItem[]`, so `.name`
	// below type-checks without a cast.
	const autoDeletedToNotify = oldItems
		.filter( isCartItem )
		.filter(
			( old ) => ! newItems.some( ( item ) => old.key === item.key )
		);

	// Items whose quantity was adjusted by the server (stock cap, sold-individually).
	// By default a line is compared optimistic → server, so intentional user
	// changes are already reflected in oldItems and do not trigger this notice.
	// Lines whose key appears in suppressKeys are skipped: the action proved that
	// the product's add was exact (server total == expected total), so any
	// quantity difference on those lines is an intentional add result, not a
	// server-initiated change. Keyed update-item lines and removeCartItem lines
	// are never in suppressKeys, so their notice behavior is unchanged.
	const autoUpdatedToNotify = newItems.filter( ( item ) => {
		if ( ! isCartItem( item ) ) {
			return false;
		}
		if ( suppressKeys.has( item.key ) ) {
			return false; // The action proved this product's add was exact.
		}
		const old = oldItems.find( ( o ) => o.key === item.key );
		return old && item.quantity !== old.quantity;
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

/**
 * Shows a dismissible error notice for a failed cart mutation and logs the
 * error to the console.
 *
 * Moved out of `cart.ts`'s `showNoticeError` store action, which now
 * delegates here, passing `state.errorMessages` explicitly since this module
 * does not have access to the `woocommerce` store's own state.
 *
 * @param error         The error to report, either a thrown `Error` or a
 *                      parsed Store API error response.
 * @param errorMessages A code-to-message lookup for a user-friendly override
 *                      of the server's own message (`state.errorMessages`,
 *                      seeded by PHP).
 */
export function* showNoticeError(
	error: Error | ApiErrorResponse,
	errorMessages?: { [ key: string ]: string }
): AsyncAction< void > {
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

	const userFriendlyMessage = errorMessages?.[ code ] || message;

	// Todo: Check what should happen if the notice is already displayed.
	noticeActions.addNotice( {
		notice: userFriendlyMessage,
		type: 'error',
		dismissible: true,
	} );

	// Emits console.error for troubleshooting.
	// eslint-disable-next-line no-console
	console.error( error );
}

export function* updateNotices(
	newNotices: Notice[] = [],
	removeOthers = false
): AsyncAction< void > {
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
}
