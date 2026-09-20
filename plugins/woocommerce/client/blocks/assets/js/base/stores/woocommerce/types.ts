/**
 * Internal dependencies
 */
import type { CatalogState } from './catalog';
import type { ScopeState } from './scope';
import type { CartActionsState, CartActionsActions } from './cart-actions';

export type { CatalogState, TemplateVariationAttribute } from './catalog';
export type {
	ScopeState,
	ProductScopeContext,
	ProductScopeEnvelope,
	ProductScopeRecord,
	ProductScopesState,
	DraftCartItem,
	DraftCartItemRecord,
} from './scope';
export type {
	CartActionsState,
	CartActionsActions,
	AddCartItemPayload,
	AddCartItemOptions,
	AddCartItemError,
	AddCartItemOutcome,
	OptimisticCartItem,
	SelectedAttributes,
	WooCommerceConfig,
} from './cart-actions';

/**
 * The unified `woocommerce` Interactivity API store's public TypeScript
 * surface. Its `state` is assembled from the module's sibling files, one
 * concern per file — this one contributes the catalog layer, the product
 * scope envelope, and the cart plane (state and actions).
 */
export type WooCommerceStore = {
	state: CatalogState & ScopeState & CartActionsState;
	actions: CartActionsActions;
};
