/**
 * Registers the `woocommerce` store.
 *
 * Each file below contributes its members to the store through its own
 * `store( 'woocommerce', … )` call, so loading this module is what assembles
 * the store. These imports are side-effect-only: they survive production
 * tree-shaking only because `package.json` lists
 * `./assets/js/base/stores/woocommerce/**` in `sideEffects`.
 */
import './cart/notices';
import './cart/actions';

export type { WooCommerceConfig, SelectedAttributes } from './types';
export type {
	OptimisticCartItem,
	ClientCartItem,
	AddCartItemError,
	AddCartItemOutcome,
	Store,
} from './cart/types';
