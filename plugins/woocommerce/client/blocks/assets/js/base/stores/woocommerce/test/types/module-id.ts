/**
 * Compiled only by the blocks type check. A type error here means
 * `@woocommerce/stores/woocommerce` does not resolve to a module that exports
 * every cart type. It is not a Jest suite and nothing imports it.
 */
import type {
	WooCommerceConfig,
	SelectedAttributes,
	OptimisticCartItem,
	ClientCartItem,
	AddCartItemError,
	AddCartItemOutcome,
	Store,
} from '@woocommerce/stores/woocommerce';

/**
 * Names every imported type so the declaration is used.
 */
export type CartTypesByModuleId = [
	WooCommerceConfig,
	SelectedAttributes,
	OptimisticCartItem,
	ClientCartItem,
	AddCartItemError,
	AddCartItemOutcome,
	Store,
];
