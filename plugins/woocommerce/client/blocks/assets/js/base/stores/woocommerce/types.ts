/**
 * Internal dependencies
 */
import type { CatalogState } from './catalog';
import type { ScopeState } from './scope';

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

/**
 * The unified `woocommerce` Interactivity API store's public TypeScript
 * surface, assembled from the catalog layer and the product scope envelope.
 * The `woocommerce/cart` state and actions register separately, from
 * `cart.ts`.
 */
export type WooCommerceStore = {
	state: CatalogState & ScopeState;
};
