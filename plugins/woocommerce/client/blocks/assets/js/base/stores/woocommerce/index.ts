/**
 * Registers the `woocommerce` store.
 *
 * Each file below contributes its members to the store through its own
 * `store( 'woocommerce', … )` call, so loading this module is what assembles
 * the store.
 */
import './cart/notices';
import './cart/actions';

export type { WooCommerceConfig, SelectedAttributes } from './types';
export type {
	OptimisticCartItem,
	AddCartItemPayload,
	AddCartItemError,
	AddCartItemOutcome,
	Store,
} from './cart/types';
