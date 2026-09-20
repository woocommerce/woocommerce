/**
 * External dependencies
 */
import { getContext } from '@wordpress/interactivity';
import type { ProductResponseItem, CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { attributeNamesMatch } from './catalog';
import type { CatalogState, TemplateVariationAttribute } from './catalog';
import type { Store as CartStore, OptimisticCartItem } from './cart';

/**
 * The `{ productId, variation, scopeName, cartItemKey }` a scope element
 * declares in the `woocommerce` namespace context, or a caller-built ref
 * passed to {@link ScopeState.findProductScope}. `productScope` and
 * `findProductScope` read the same four fields from either source.
 */
export type ProductScopeContext = {
	/** The scope's product id. */
	productId?: number;
	/** The scope's selected variation attributes. */
	variation?: TemplateVariationAttribute[];
	/** The name addressing this scope's record under `state.productScopes`. */
	scopeName?: string;
	/** The committed cart line this scope corresponds to, when known. */
	cartItemKey?: string;
};

/**
 * The client-only draft cart item stored on a scope's record. `id` and
 * `variation` mirror the scope's identity at the time the record was
 * created or last written; any other key is an extension prop written
 * through `draftCartItem`.
 */
export type DraftCartItemRecord = {
	/** The scope's product id. */
	id?: number;
	/** The scope's selected variation attributes. */
	variation?: TemplateVariationAttribute[];
	/** An extension prop, or any other draft cart item field. */
	[ extensionKey: string ]: unknown;
};

/**
 * A single scope's client-only record under `state.productScopes`. Created
 * the first time a shopper's action writes to the scope's identity or
 * draft cart item; absent until then.
 */
export type ProductScopeRecord = {
	/** The scope's draft cart item, once created. */
	draftCartItem?: DraftCartItemRecord;
};

/** `state.productScopes`: every scope's client-only record, keyed by name. */
export type ProductScopesState = Record< string, ProductScopeRecord >;

/**
 * A scope's draft cart item, read through `state.productScope.draftCartItem`
 * or `state.findProductScope( ref ).draftCartItem`. A `Proxy`: `id` and
 * `variation` reads/writes go through the scope's identity; `quantity`
 * defaults to `1` until set; any other key is an extension prop read from
 * and written to the scope's record.
 */
export type DraftCartItem = {
	/** The scope's product id. */
	id: number | undefined;
	/** The scope's selected variation attributes. */
	variation: TemplateVariationAttribute[] | undefined;
	/** The typed quantity, defaulting to `1` until the shopper sets it. */
	quantity: number;
	/** An extension prop. */
	[ extensionKey: string ]: unknown;
};

/**
 * The cart line a scope's `cartItem` accessor resolves to: either a
 * committed server line or an optimistic one still in flight.
 */
type CartLine = CartItem | OptimisticCartItem;

/**
 * The product scope envelope returned by `state.productScope` and
 * `state.findProductScope( ref )`. `productId`, `variation` and
 * `draftCartItem` are writable; `baseProduct`, `productVariation`, `product`
 * and `cartItem` are read-only.
 */
export type ProductScopeEnvelope = {
	/** The scope's product id. */
	productId: number;
	/** The scope's selected variation attributes. */
	variation: TemplateVariationAttribute[];
	/**
	 * The scope's draft cart item. Always present for `productScope`;
	 * `undefined` for an unnamed `findProductScope( ref )` whose product id
	 * matches zero or several records.
	 */
	readonly draftCartItem: DraftCartItem | undefined;
	/** The scope's top-level product, never a variation. */
	readonly baseProduct: ProductResponseItem | null;
	/** The variation matching the scope's selected attributes, if any. */
	readonly productVariation: ProductResponseItem | null;
	/** `productVariation` when one resolved, otherwise `baseProduct`. */
	readonly product: ProductResponseItem | null;
	/** The committed cart line the scope corresponds to, if any. */
	readonly cartItem: CartLine | null;
};

/** The unified store's scope layer, merged into `WooCommerceStore['state']`. */
export type ScopeState = {
	/** Every scope's client-only record, keyed by name. */
	productScopes: ProductScopesState;
	/** The reading element's product scope envelope. */
	readonly productScope: ProductScopeEnvelope;
	/**
	 * Builds a product scope envelope from a ref instead of the reading
	 * element's context. Script-only: directives call functions with no
	 * arguments.
	 *
	 * @param ref The scope's `{ productId, variation, scopeName, cartItemKey }`.
	 * @return The ref's product scope envelope.
	 */
	findProductScope: ( ref: ProductScopeContext ) => ProductScopeEnvelope;
};

/**
 * The slice of the shared `woocommerce` state this module reads: the
 * catalog layer, this module's own `productScopes` records, and
 * `findItemInCart` from the cart layer (`cart.ts`) for `cartItem`. It
 * excludes `productScope` and `findProductScope` themselves — this module
 * defines those, it does not read them back.
 */
type SharedState = CatalogState &
	Pick< ScopeState, 'productScopes' > &
	Pick< CartStore[ 'state' ], 'findItemInCart' >;

// Bound once, by `index.ts`, to the store's own returned state reference
// right after its single `store()` registration — before any directive can
// read `productScope` or call `findProductScope`. This module never calls
// `store()` itself: doing so would register `woocommerce` a second time,
// which the module's own registration test (`test/catalog.ts`) counts.
let state: SharedState;

/**
 * Binds the shared `woocommerce` state reference this module's accessors
 * read and write. Called once, by `index.ts`, immediately after its own
 * `store()` call returns.
 *
 * @param sharedState The store's own returned state reference.
 */
export function bindState( sharedState: SharedState ): void {
	state = sharedState;
}

/**
 * Resolves the scope's product id in the module's standard order: the named
 * record, then the locator (the reading element's context, or a
 * `findProductScope` ref), then the single-product template.
 *
 * @param name    The scope's name.
 * @param locator The context or ref to fall back to.
 * @return The resolved product id.
 */
function resolveProductId(
	name: string,
	locator: ProductScopeContext | null
): number {
	const record = state.productScopes[ name ];
	if ( record?.draftCartItem?.id !== undefined ) {
		return record.draftCartItem.id;
	}
	if ( locator?.productId !== undefined ) {
		return locator.productId;
	}
	return state.template?.productId ?? 0;
}

/**
 * Resolves the scope's selected variation attributes in the module's
 * standard order: the named record, then the locator, then the
 * single-product template.
 *
 * @param name    The scope's name.
 * @param locator The context or ref to fall back to.
 * @return The resolved variation attributes.
 */
function resolveVariation(
	name: string,
	locator: ProductScopeContext | null
): TemplateVariationAttribute[] {
	const record = state.productScopes[ name ];
	if ( record?.draftCartItem?.variation !== undefined ) {
		return record.draftCartItem.variation;
	}
	if ( locator?.variation !== undefined ) {
		return locator.variation;
	}
	return state.template?.variation ?? [];
}

/**
 * Returns the scope's record, creating it first when missing. A newly
 * created record's draft cart item snapshots the scope's currently resolved
 * `id` and `variation`, so a record created by writing only one of them
 * still carries a complete identity.
 *
 * @param name    The scope's name.
 * @param locator The context or ref used to resolve the identity to snapshot.
 * @return The scope's record, with `draftCartItem` guaranteed present.
 */
function ensureRecord(
	name: string,
	locator: ProductScopeContext | null
): Required< ProductScopeRecord > {
	let record = state.productScopes[ name ];
	if ( ! record ) {
		record = {};
		state.productScopes[ name ] = record;
	}
	if ( ! record.draftCartItem ) {
		record.draftCartItem = {
			id: resolveProductId( name, locator ),
			variation: resolveVariation( name, locator ),
		};
	}
	return record as Required< ProductScopeRecord >;
}

/**
 * Writes a scope's `id` or `variation` identity field to both the scope's
 * record (creating it first when missing) and, when present, the locator
 * (the reading element's declared context).
 *
 * @param name    The scope's name.
 * @param locator The context to also update, or `null` to skip it.
 * @param field   Which identity field is being written.
 * @param value   The new value.
 */
function writeIdentity(
	name: string,
	locator: ProductScopeContext | null,
	field: 'id' | 'variation',
	value: number | TemplateVariationAttribute[]
): void {
	const record = ensureRecord( name, locator );
	if ( field === 'id' ) {
		record.draftCartItem.id = value as number;
		if ( locator ) {
			locator.productId = value as number;
		}
	} else {
		record.draftCartItem.variation = value as TemplateVariationAttribute[];
		if ( locator ) {
			locator.variation = value as TemplateVariationAttribute[];
		}
	}
}

/**
 * Finds the one name under `state.productScopes` whose record's draft cart
 * item id matches `productId`, for an unnamed `findProductScope( ref )`.
 *
 * @param productId The product id to match records against.
 * @return The matching name, or `null` when zero or several records match.
 */
function findSingleMatchingRecordName( productId: number ): string | null {
	const matches = Object.keys( state.productScopes ).filter(
		( name ) => state.productScopes[ name ].draftCartItem?.id === productId
	);
	return matches.length === 1 ? matches[ 0 ] : null;
}

/**
 * Resolves a term's slug from a product's own attribute terms, falling back
 * to the label itself when the product carries no matching term (e.g. the
 * product's `attributes` were never loaded) — the same fallback
 * `doesCartItemMatchAttributes` uses for cart lines.
 *
 * @param product       The product carrying the `attributes` terms table.
 * @param attributeName The attribute name, e.g. `attribute_pa_color`.
 * @param label         The term's label, e.g. `Blue`.
 * @return The term's slug, or `label` when it cannot be resolved.
 */
function resolveTermSlug(
	product: ProductResponseItem,
	attributeName: string,
	label: string
): string {
	const terms = product.attributes?.find( ( attribute ) =>
		attributeNamesMatch( attribute.name, attributeName )
	)?.terms;
	return terms?.find( ( term ) => term.name === label )?.slug ?? label;
}

/**
 * Finds the id of the variation on `product` whose attributes match
 * `selection`: equal attribute count, names normalized, and each candidate
 * label resolved to its slug before comparing. A candidate attribute whose
 * value is `null` is a Store API "Any" attribute: it matches any non-null
 * selection for that name.
 *
 * @param product   The parent product carrying the `variations` list.
 * @param selection The scope's selected variation attributes.
 * @return The matching variation's id, or `null` when none matches.
 */
function findMatchingVariationId(
	product: ProductResponseItem,
	selection: TemplateVariationAttribute[]
): number | null {
	const matched = ( product.variations ?? [] ).find( ( candidate ) => {
		if ( candidate.attributes.length !== selection.length ) {
			return false;
		}
		return candidate.attributes.every( ( attribute ) => {
			const selected = selection.find( ( entry ) =>
				attributeNamesMatch( entry.attribute, attribute.name )
			);
			if ( attribute.value === null ) {
				return selected !== undefined && selected.value !== null;
			}
			if ( ! selected ) {
				return false;
			}
			const slug = resolveTermSlug(
				product,
				attribute.name,
				attribute.value
			);
			return selected.value.toLowerCase() === slug.toLowerCase();
		} );
	} );
	return matched ? matched.id : null;
}

/**
 * Resolves a scope's `baseProduct`, `productVariation` and `product`
 * together. When `productId` is itself a variation id, it is returned
 * directly (mirroring the identity `id` a cart line or a grouped child
 * carries), with `baseProduct` its parent; otherwise `productId` is looked
 * up as a top-level product and, when `selection` is non-empty, matched
 * against its variations.
 *
 * @param productId The scope's resolved product id.
 * @param selection The scope's resolved selected variation attributes.
 * @return The three resolved product members.
 */
function resolveProductMembers(
	productId: number,
	selection: TemplateVariationAttribute[]
): {
	baseProduct: ProductResponseItem | null;
	productVariation: ProductResponseItem | null;
	product: ProductResponseItem | null;
} {
	const directVariation = state.productVariations[ productId ];
	if ( directVariation ) {
		return {
			baseProduct: state.products[ directVariation.parent ] ?? null,
			productVariation: directVariation,
			product: directVariation,
		};
	}

	const baseProduct = state.products[ productId ] ?? null;
	if ( ! baseProduct || ! selection.length ) {
		return { baseProduct, productVariation: null, product: baseProduct };
	}

	const matchedId = findMatchingVariationId( baseProduct, selection );
	const productVariation =
		matchedId !== null
			? state.productVariations[ matchedId ] ?? null
			: null;

	return {
		baseProduct,
		productVariation,
		product: productVariation ?? baseProduct,
	};
}

/**
 * Resolves a scope's `cartItem`: the committed line matching `cartItemKey`
 * when given, otherwise the line matching `productId` and `variation` —
 * `state.findItemInCart`'s own matching, unchanged.
 *
 * @param productId   The scope's resolved product id.
 * @param variation   The scope's resolved selected variation attributes.
 * @param cartItemKey The scope's declared cart line key, if any.
 * @return The matching cart line, or `null`.
 */
function resolveCartItem(
	productId: number,
	variation: TemplateVariationAttribute[],
	cartItemKey: string | undefined
): CartLine | null {
	return (
		state.findItemInCart( {
			id: productId,
			key: cartItemKey,
			variation,
		} ) ?? null
	);
}

/**
 * Builds the `draftCartItem` Proxy for one scope. Reads `id` and `variation`
 * through the scope's identity resolution and `quantity` from the record,
 * defaulting to `1`; any other key reads straight from the record. Writes to
 * `id` and `variation` go through the identity setters (record and locator);
 * any other key writes to the record only, creating it first when missing.
 *
 * @param name       The scope's name.
 * @param getLocator Returns the current context or ref to resolve against.
 * @return The scope's `draftCartItem` Proxy.
 */
function createDraftCartItemProxy(
	name: string,
	getLocator: () => ProductScopeContext | null
): DraftCartItem {
	const handler: ProxyHandler< Record< string, unknown > > = {
		get( _target, key ) {
			if ( typeof key !== 'string' ) {
				return undefined;
			}
			if ( key === 'id' ) {
				return resolveProductId( name, getLocator() );
			}
			if ( key === 'variation' ) {
				return resolveVariation( name, getLocator() );
			}
			const record = state.productScopes[ name ];
			if ( key === 'quantity' ) {
				return record?.draftCartItem?.quantity ?? 1;
			}
			return record?.draftCartItem?.[ key ];
		},
		set( _target, key, value ) {
			if ( typeof key !== 'string' ) {
				return false;
			}
			if ( key === 'id' || key === 'variation' ) {
				writeIdentity( name, getLocator(), key, value );
				return true;
			}
			const record = ensureRecord( name, getLocator() );
			record.draftCartItem[ key ] = value;
			return true;
		},
	};
	return new Proxy( {}, handler ) as DraftCartItem;
}

/**
 * Builds a product scope envelope. Shared by `productScope` (backed by the
 * reading element's context) and `findProductScope` (backed by a ref): every
 * accessor resolves lazily, at access time, through the three callbacks
 * rather than eagerly when the envelope itself is built, so building the
 * envelope reads nothing reactive.
 *
 * @param resolveName Resolves the name addressing the scope's record, or
 *                    `null` for an unnamed ref with no single record to
 *                    address.
 * @param getLocator  Returns the current context or ref.
 * @param getIdentity Resolves the `{ productId, variation }` that
 *                    `baseProduct`, `productVariation`, `product` and
 *                    `cartItem` always use — the resolved scope identity
 *                    for `productScope`, or the ref's own values for
 *                    `findProductScope`.
 * @return The product scope envelope.
 */
function createEnvelope(
	resolveName: () => string | null,
	getLocator: () => ProductScopeContext | null,
	getIdentity: () => {
		productId: number;
		variation: TemplateVariationAttribute[];
	}
): ProductScopeEnvelope {
	const envelope = {} as ProductScopeEnvelope;

	Object.defineProperties( envelope, {
		productId: {
			enumerable: true,
			get(): number {
				const name = resolveName();
				return name !== null
					? resolveProductId( name, getLocator() )
					: getIdentity().productId;
			},
			set( value: number ): void {
				const name = resolveName();
				if ( name !== null ) {
					writeIdentity( name, getLocator(), 'id', value );
				}
			},
		},
		variation: {
			enumerable: true,
			get(): TemplateVariationAttribute[] {
				const name = resolveName();
				return name !== null
					? resolveVariation( name, getLocator() )
					: getIdentity().variation;
			},
			set( value: TemplateVariationAttribute[] ): void {
				const name = resolveName();
				if ( name !== null ) {
					writeIdentity( name, getLocator(), 'variation', value );
				}
			},
		},
		draftCartItem: {
			enumerable: true,
			get(): DraftCartItem | undefined {
				const name =
					resolveName() ??
					findSingleMatchingRecordName( getIdentity().productId );
				return name === null
					? undefined
					: createDraftCartItemProxy( name, getLocator );
			},
		},
		baseProduct: {
			enumerable: true,
			get(): ProductResponseItem | null {
				const { productId, variation } = getIdentity();
				return resolveProductMembers( productId, variation )
					.baseProduct;
			},
		},
		productVariation: {
			enumerable: true,
			get(): ProductResponseItem | null {
				const { productId, variation } = getIdentity();
				return resolveProductMembers( productId, variation )
					.productVariation;
			},
		},
		product: {
			enumerable: true,
			get(): ProductResponseItem | null {
				const { productId, variation } = getIdentity();
				return resolveProductMembers( productId, variation ).product;
			},
		},
		cartItem: {
			enumerable: true,
			get(): CartLine | null {
				const { productId, variation } = getIdentity();
				return resolveCartItem(
					productId,
					variation,
					getLocator()?.cartItemKey
				);
			},
		},
	} );

	return envelope;
}

/**
 * The scope layer's initial state, merged into the `woocommerce` store.
 * `productScope` and `findProductScope` build a fresh envelope per call —
 * see {@link createEnvelope}.
 */
export const scopeState: ScopeState = {
	productScopes: {},

	get productScope(): ProductScopeEnvelope {
		const getLocator = () =>
			getContext< ProductScopeContext >( 'woocommerce' ) ?? null;
		const resolveName = () => getLocator()?.scopeName ?? '_default';
		return createEnvelope( resolveName, getLocator, () => {
			const name = resolveName();
			return {
				productId: resolveProductId( name, getLocator() ),
				variation: resolveVariation( name, getLocator() ),
			};
		} );
	},

	findProductScope( ref: ProductScopeContext ): ProductScopeEnvelope {
		const getLocator = () => ref;
		const resolveName = () => ref.scopeName ?? null;
		return createEnvelope( resolveName, getLocator, () => ( {
			productId: ref.productId ?? state.template?.productId ?? 0,
			variation: ref.variation ?? state.template?.variation ?? [],
		} ) );
	},
};
