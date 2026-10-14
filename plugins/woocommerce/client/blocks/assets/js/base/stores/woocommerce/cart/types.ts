/**
 * External dependencies
 */
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
	CartResponseTotals,
} from '@woocommerce/types';
import type { Notice } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import type { SelectedAttributes } from '../types';

export type OptimisticCartItem = {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};

export type ClientCartItem = Omit<
	OptimisticCartItem,
	'variation' | 'quantity'
> & {
	variation?: SelectedAttributes[];
	/** The target quantity (absolute). Either this or quantityToAdd must be provided. */
	quantity?: number;
	/** Optional: add this delta to current quantity instead of setting absolute quantity */
	quantityToAdd?: number;
};

export type CartUpdateOptions = { showCartUpdatesNotices?: boolean };

/**
 * The failure detail carried by a rejected `AddCartItemOutcome`.
 */
export type AddCartItemError = {
	/**
	 * Server error code for a per-item rejection (e.g.
	 * `woocommerce_rest_product_out_of_stock`), or the batcher's
	 * `unknown_error` fallback. Absent on whole-batch/transport failures.
	 */
	code?: string;
	/** Human-readable failure description. Always non-empty. */
	message: string;
};

/**
 * The per-call outcome `addCartItem` resolves with, captured at the moment
 * its own request settles (accepted or rejected). A later throw in
 * post-success processing (notices) never downgrades an already-captured
 * success into a failure.
 */
export type AddCartItemOutcome =
	{ success: true } | { success: false; error: AddCartItemError };

export type Store = {
	state: {
		errorMessages?: {
			[ key: string ]: string;
		};
		restUrl: string;
		nonce: string;
		findItemInCart: ( args: {
			id: ClientCartItem[ 'id' ];
			key?: ClientCartItem[ 'key' ];
			variation?: ClientCartItem[ 'variation' ];
		} ) => CartItem | OptimisticCartItem | undefined;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
	};
	actions: {
		removeCartItem: ( key: string ) => Promise< void >;
		/**
		 * Sets the quantity of the cart line with the given key. Rejects when
		 * `key` is missing.
		 */
		updateCartItem: ( args: {
			key: string;
			quantity: number;
		} ) => Promise< void >;
		addCartItem: (
			args: ClientCartItem,
			options?: CartUpdateOptions
		) => Promise< AddCartItemOutcome >;
		batchAddCartItems: (
			items: ClientCartItem[],
			options?: CartUpdateOptions
		) => Promise< void >;
		/**
		 * Replaces `state.cart` with a fresh GET of the Store API cart. It
		 * does nothing while a cart mutation is in flight or another refresh is
		 * pending, and retries after a delay when the request fails.
		 */
		// Todo: Check why if I switch to an async function here the types of the store stop working.
		refreshCart: () => Promise< void >;
		waitForIdle: () => Promise< void >;
		showNoticeError: ( error: Error | ApiErrorResponse ) => Promise< void >;
		updateNotices: (
			notices: Notice[],
			removeOthers?: boolean
		) => Promise< void >;
	};
};
