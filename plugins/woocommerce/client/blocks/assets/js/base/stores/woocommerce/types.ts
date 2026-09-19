/**
 * Internal dependencies
 */
import type { CatalogState } from './catalog';

export type { CatalogState, TemplateVariationAttribute } from './catalog';

/**
 * The unified `woocommerce` Interactivity API store's public TypeScript
 * surface. Its `state` is assembled from the module's sibling files, one
 * concern per file — this one contributes the catalog layer.
 */
export type WooCommerceStore = {
	state: CatalogState;
};
