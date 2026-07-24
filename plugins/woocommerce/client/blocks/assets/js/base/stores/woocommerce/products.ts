/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type {
	DraftItem,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import {
	deriveFamilyVariationAttributes,
	findFamilyDraft,
	resolveCollection,
	resolveDraftKey,
	resolveFamilyVariation,
	warnDraftInvariant,
	writeDraft,
} from './draft-internals';

/**
 * Per-element selection for the current product/variation.
 *
 * The "current" product can be set in two ways:
 * - Globally, via `wp_interactivity_state( 'woocommerce/products', [ ... ] )`
 *   (used by SingleProductTemplate — one product per page).
 * - Per-element, via `data-wp-context="woocommerce/products::{ ... }"` on a
 *   wrapper element (used by SingleProduct so each product in a loop gets
 *   its own IDs).
 *
 * When present, per-element context takes precedence over the global state.
 * See ./README.md for the full model and precedence rules.
 */
type ProductContext = {
	productId: number;
	variationId?: number | null;
};

/**
 * The state shape for the products store.
 * This matches the server-side ProductsStore state structure.
 */
export type ProductsStoreState = {
	/**
	 * Products keyed by product ID.
	 * These are in Store API format (ProductResponseItem).
	 */
	products: Record< number, ProductResponseItem >;
	/**
	 * Product variations keyed by variation ID.
	 * These are in Store API format (ProductResponseItem).
	 */
	productVariations: Record< number, ProductResponseItem >;
	/**
	 * Look up a product by ID. If the ID exists in `productVariations`,
	 * returns the variation directly (ignoring `selectedAttributes`).
	 * Otherwise looks in `products`: for variable products with
	 * `selectedAttributes`, returns the matching variation or `null`;
	 * for all other cases returns the product itself.
	 */
	findProduct: ( args: {
		id: number;
		selectedAttributes?: SelectedAttributes[] | null;
	} ) => ProductResponseItem | null;
	/**
	 * The current product ID from state or per-element context.
	 */
	productId: number;
	/**
	 * The current variation ID from state or per-element context.
	 */
	variationId: number | null;
	/**
	 * The base product for this page/block. Always the top-level product
	 * (e.g. the variable product "Hoodie"), never a variation.
	 * Resolves productId from per-block context when available.
	 */
	baseProductInContext: ProductResponseItem | null;
	/**
	 * The currently selected variation for the in-context product, or
	 * `null` when none is selected. For simple/grouped products, this is
	 * always `null`.
	 *
	 * Derives from the in-context product's family draft in the
	 * `woocommerce/cart` collection when one exists there (a draft carrying
	 * a resolvable attribute set resolves the matching variation; an
	 * unresolvable one resolves `null`, exactly as an equivalent
	 * `variationId` write resolves today); when no family draft exists,
	 * falls back to today's `variationId` context/state resolution
	 * (SSR/first-paint parity).
	 *
	 * Assignable: setting this to a `ProductResponseItem` belonging to the
	 * in-context product's family (or to `null`, to clear) writes that
	 * selection into the family draft — through the same internal write
	 * routine every other draft write goes through — syncing every surface
	 * sharing the collection. Assigning a foreign variation, or assigning
	 * while the in-context product is simple/grouped, is a
	 * dev-build-warned no-op.
	 */
	productVariationInContext: ProductResponseItem | null;
	/**
	 * The resolved product for the current context:
	 * `productVariationInContext` if one is set, otherwise
	 * `baseProductInContext`. This is the property most blocks should
	 * bind to — use `baseProductInContext` / `productVariationInContext`
	 * explicitly only when the distinction matters.
	 *
	 * Blocks can bind directly to properties, e.g.:
	 *   state.productInContext.stock_availability.text
	 *   state.productInContext.sku
	 */
	productInContext: ProductResponseItem | null;
};

/**
 * The products store type definition.
 */
export type ProductsStore = {
	state: ProductsStoreState;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const normalizeAttributeName = ( name: string ): string =>
	name
		.replace( /^attribute_(pa_)?/, '' )
		.replace( /-/g, ' ' )
		.toLowerCase();

const attributeNamesMatch = ( a: string, b: string ): boolean =>
	normalizeAttributeName( a ) === normalizeAttributeName( b );

/**
 * Resolves the in-context product's family draft from the raw, unmediated
 * `state.draftItems` collection — never `draftSeeds` (a seed-derived read
 * here would desync first paint from what PHP actually rendered) and never
 * a `woocommerce/cart` getter (reading one from `woocommerce/products`
 * throws "Cycle detected" in the Interactivity runtime).
 *
 * Reaches `woocommerce/cart`'s state through `draft-internals.ts` by
 * namespace rather than importing `./cart` as a value, so a products-only
 * page that never loads `cart.ts` degrades to "no family draft" (the
 * unregistered namespace's empty stub) rather than throwing — the try/catch
 * below is exactly that degrade.
 *
 * @param base The in-context base product whose family draft to resolve.
 * @return The base product's family draft, or `undefined` when none exists
 *         in the resolved collection (including when the read degrades).
 */
function resolveInContextFamilyDraft(
	base: ProductResponseItem
): DraftItem | undefined {
	try {
		const collection = resolveCollection( resolveDraftKey() );
		return collection ? findFamilyDraft( base, collection ) : undefined;
	} catch {
		return undefined;
	}
}

/**
 * Names the "any" attributes a variation defines that a derived attribute
 * set carries no value for — the setter's signal that
 * `deriveFamilyVariationAttributes` (see `draft-internals.ts`) degraded to a
 * partial selection by omitting, rather than inventing, a value for one or
 * more of them.
 *
 * @param base        The family's base product, whose `variations[]` names
 *                    the assigned variation's "any" attributes.
 * @param variationId The assigned variation's id.
 * @param derived     The derived attribute set to check for completeness.
 * @return The label of each "any" attribute `derived` carries no value for;
 *         empty when `variationId` names none of `base`'s variations, or
 *         when every attribute is covered.
 */
function findMissingAnyAttributes(
	base: ProductResponseItem,
	variationId: number,
	derived: SelectedAttributes[]
): string[] {
	const variationEntry = base.variations.find(
		( variation ) => variation.id === variationId
	);
	if ( ! variationEntry ) {
		return [];
	}
	return variationEntry.attributes
		.filter( ( attr ) => ! attr.value )
		.filter(
			( attr ) =>
				! derived.some( ( selected ) =>
					attributeNamesMatch( selected.attribute, attr.name )
				)
		)
		.map( ( attr ) => attr.name );
}

/**
 * The woocommerce/products store.
 *
 * Server-hydrated cache of product and variation data in Store API format
 * (`ProductResponseItem`). PHP loaders populate `products` / `productVariations`;
 * derived getters below resolve the "current" product from either global state
 * or per-element context. These getters are mirrored in PHP
 * (see ProductsStore::register_getters) so directive bindings like
 * `state.productInContext.sku` resolve during SSR as well as on the client.
 *
 * See ./README.md for the complete model, loaders, and consumer patterns.
 */
const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{
		state: {
			products: {},
			productVariations: {},
			findProduct( {
				id,
				selectedAttributes,
			}: {
				id: number;
				selectedAttributes?: SelectedAttributes[] | null;
			} ): ProductResponseItem | null {
				const variation = productsState.productVariations[ id ];
				if ( variation ) {
					return variation;
				}

				const product = productsState.products[ id ];

				if ( ! product ) {
					return null;
				}

				if (
					product.type !== 'variable' ||
					! selectedAttributes?.length
				) {
					return product;
				}

				const matchedVariation = product.variations?.find( ( v ) =>
					v.attributes.every( ( attr ) => {
						const selectedAttr = selectedAttributes.find(
							( selected ) =>
								attributeNamesMatch(
									attr.name,
									selected.attribute
								)
						);

						if ( attr.value === null ) {
							return (
								selectedAttr !== undefined &&
								selectedAttr.value !== null
							);
						}

						return selectedAttr?.value === attr.value;
					} )
				);

				if ( ! matchedVariation ) {
					return null;
				}

				return (
					productsState.productVariations[ matchedVariation.id ] ??
					null
				);
			},

			get baseProductInContext(): ProductResponseItem | null {
				const context = getContext< ProductContext >(
					'woocommerce/products'
				);
				const productId =
					context && 'productId' in context
						? context.productId
						: productsState.productId;

				if ( ! productId ) {
					return null;
				}
				return productsState.products[ productId ] ?? null;
			},

			get productVariationInContext(): ProductResponseItem | null {
				const base = productsState.baseProductInContext;
				if ( base ) {
					const familyDraft = resolveInContextFamilyDraft( base );
					if ( familyDraft ) {
						return resolveFamilyVariation( base, familyDraft );
					}
				}

				const context = getContext< ProductContext >(
					'woocommerce/products'
				);
				const variationId =
					context && 'variationId' in context
						? context.variationId
						: productsState.variationId;
				if ( ! variationId ) {
					return null;
				}
				return productsState.productVariations[ variationId ] ?? null;
			},

			set productVariationInContext(
				variation: ProductResponseItem | null
			) {
				const base = productsState.baseProductInContext;
				if ( ! base || base.type !== 'variable' ) {
					warnDraftInvariant(
						'cannot set productVariationInContext: the in-context product is not variable — simple/grouped products carry no family draft to write into.'
					);
					return;
				}

				if (
					variation !== null &&
					! base.variations.some(
						( baseVariation ) => baseVariation.id === variation.id
					)
				) {
					warnDraftInvariant(
						`cannot set productVariationInContext to variation ${ variation.id }: it does not belong to in-context product ${ base.id }'s family.`
					);
					return;
				}

				const existingDraft = resolveInContextFamilyDraft( base );
				const derivedAttributes =
					variation === null
						? []
						: deriveFamilyVariationAttributes(
								base,
								variation.id,
								existingDraft
						  );

				if ( variation !== null ) {
					const missingAttributes = findMissingAnyAttributes(
						base,
						variation.id,
						derivedAttributes
					);
					if ( missingAttributes.length > 0 ) {
						warnDraftInvariant(
							`assigning variation ${
								variation.id
							} to productVariationInContext with no recorded value for "any" attribute(s) ${ missingAttributes.join(
								', '
							) }; filing a partial selection at the parent product ${
								base.id
							}.`
						);
					}
				}

				writeDraft( base.id, 'variation', derivedAttributes );
			},

			get productInContext(): ProductResponseItem | null {
				return (
					productsState.productVariationInContext ||
					productsState.baseProductInContext
				);
			},
		},
	},
	{ lock: universalLock }
);
