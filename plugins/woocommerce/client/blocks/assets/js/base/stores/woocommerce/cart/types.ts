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
};

/**
 * The product `addCartItem` and `batchAddCartItems` add to the cart.
 */
export type AddCartItemPayload = {
	/** The product or variation id. */
	id: number;
	/**
	 * The amount to add to the cart. Defaults to 1 when omitted. A numeric
	 * string from untyped data, such as a server-rendered context value, is
	 * added as the number it holds.
	 */
	quantity?: number;
	/** The selected attributes, when the product is a variation. */
	variation?: SelectedAttributes[];
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
			id: number;
			key?: string | undefined;
			variation?: SelectedAttributes[] | undefined;
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
		/**
		 * Adds `quantity` units (1 when omitted) of a product to the cart.
		 * Resolves the outcome and never rejects on a request failure.
		 */
		addCartItem: (
			args: AddCartItemPayload,
			options?: CartUpdateOptions
		) => Promise< AddCartItemOutcome >;
		/**
		 * Adds several products to the cart in one cycle, each by its
		 * `quantity` (1 when omitted).
		 */
		batchAddCartItems: (
			items: AddCartItemPayload[],
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
