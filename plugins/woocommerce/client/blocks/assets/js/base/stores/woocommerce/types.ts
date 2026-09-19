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
 * surface. Its `state` is assembled from the module's sibling files, one
 * concern per file — this one contributes the catalog layer and the product
 * scope envelope.
 */
export type WooCommerceStore = {
	state: CatalogState & ScopeState;
};
