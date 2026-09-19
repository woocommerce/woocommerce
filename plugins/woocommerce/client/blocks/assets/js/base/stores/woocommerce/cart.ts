/**
 * @deprecated This module is a temporary compatibility shim. The cart plane
 * it used to implement now lives in the unified `@woocommerce/stores/woocommerce`
 * module (`cart-actions.ts`), which this file re-exports. It exists only so
 * that consumers still importing `@woocommerce/stores/woocommerce/cart` —
 * `atomic/blocks/product-elements/button/frontend.ts`,
 * `blocks/mini-cart/frontend.ts`,
 * `blocks/add-to-cart-with-options/frontend.ts` and its
 * `grouped-product-selector`, `blocks/wishlist/frontend.ts`,
 * `blocks/saved-for-later/frontend.ts`, `blocks/add-to-wishlist-button/frontend.ts`,
 * and `blocks/product-gallery/types.ts` — keep compiling and, at run time,
 * load the one unified store instead of registering the `woocommerce`
 * namespace a second time. T18 deletes this file once every consumer
 * migrates to `@woocommerce/stores/woocommerce`.
 */

/**
 * Internal dependencies
 */
import './index';

export type {
	OptimisticCartItem,
	SelectedAttributes,
	ClientCartItem,
	AddCartItemError,
	AddCartItemOutcome,
	WooCommerceConfig,
} from './cart-actions';

/**
 * @deprecated The compatibility view of the unified store's cart plane: the
 * five compatibility members (`findItemInCart`, `batchAddCartItems`,
 * `refreshCartItems`, `waitForIdle`, and the keyed `addCartItem` form) plus
 * `cart` and the designed `addCartItem`/`removeCartItem`. Consumers listed
 * above type against this instead of the unified store's own
 * `WooCommerceStore` type. T18 removes it.
 */
export type Store = {
	state: {
		cart: import('./cart-actions').CartActionsState[ 'cart' ];
		findItemInCart: import('./cart-actions').CartActionsState[ 'findItemInCart' ];
	};
	actions: {
		removeCartItem: import('./cart-actions').CartActionsActions[ 'removeCartItem' ];
		addCartItem: (
			args: import('./cart-actions').ClientCartItem,
			options?: import('./cart-actions').AddCartItemOptions
		) => Promise< import('./cart-actions').AddCartItemOutcome >;
		batchAddCartItems: import('./cart-actions').CartActionsActions[ 'batchAddCartItems' ];
		refreshCartItems: import('./cart-actions').CartActionsActions[ 'refreshCartItems' ];
		waitForIdle: import('./cart-actions').CartActionsActions[ 'waitForIdle' ];
	};
};
