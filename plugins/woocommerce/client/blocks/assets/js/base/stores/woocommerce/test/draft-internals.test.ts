/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { DraftItem, DraftKey, SelectedAttributes } from '../cart';
import type { ProductsStoreState } from '../products';
import { attributeNamesMatch } from '../../../utils/variations/attribute-matching';

type MockCartState = { draftItems: Record< DraftKey, DraftItem[] > };

let mockCartState: MockCartState;
let mockProductsState: Partial< ProductsStoreState >;

/**
 * The value `getContext( 'woocommerce/cart' )` should return for the draft
 * key resolver, controlled per test. `undefined` (or an object with no
 * `draftKey`) simulates a surface with no container of its own, so the
 * resolver degrades to {@link GLOBAL_DRAFT_KEY}.
 */
let mockCartContext: { draftKey?: DraftKey } | undefined;

/**
 * When `true`, the mocked `getContext` throws regardless of namespace,
 * reproducing the real Interactivity runtime's behavior when called with no
 * directive currently executing on the call stack.
 */
let mockCartContextThrows = false;

/**
 * The value `getServerState( 'woocommerce/cart' )` should return, controlled
 * per test. `undefined` simulates a page carrying no `draftSeeds` payload at
 * all.
 */
let mockServerState:
	| { draftSeeds?: Record< DraftKey, Record< number, DraftItem > > }
	| undefined;

/**
 * A faithful re-implementation of `woocommerce/products`' real `findProduct`
 * getter (see `../products.ts`), used as the mocked store's own
 * implementation so `draft-internals.ts`'s family-resolution helpers (which
 * call `findProduct` for their attrs rung) exercise the same matching
 * semantics the real store provides, rather than a stubbed-out shortcut.
 */
function findProductImpl( {
	id,
	selectedAttributes,
}: {
	id: number;
	selectedAttributes?: SelectedAttributes[] | null;
} ): ProductResponseItem | null {
	const variation = mockProductsState.productVariations?.[ id ];
	if ( variation ) {
		return variation;
	}

	const product = mockProductsState.products?.[ id ];
	if ( ! product ) {
		return null;
	}

	if ( product.type !== 'variable' || ! selectedAttributes?.length ) {
		return product;
	}

	const matched = product.variations?.find( ( variationEntry ) =>
		variationEntry.attributes.every( ( attr ) => {
			const selected = selectedAttributes.find( ( sel ) =>
				attributeNamesMatch( attr.name, sel.attribute )
			);
			if ( attr.value === null ) {
				return selected !== undefined && selected.value !== null;
			}
			return selected?.value === attr.value;
		} )
	);

	if ( ! matched ) {
		return null;
	}

	return mockProductsState.productVariations?.[ matched.id ] ?? null;
}

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: jest.fn( () => {
			if ( mockCartContextThrows ) {
				throw new Error(
					'Cannot call `getContext()` when there is no scope.'
				);
			}
			return mockCartContext;
		} ),
		getServerState: jest.fn( () => mockServerState ),
		store: jest.fn( ( namespace: string ) => {
			if ( namespace === 'woocommerce/products' ) {
				return { state: mockProductsState };
			}
			return { state: mockCartState };
		} ),
	} ),
	{ virtual: true }
);

// eslint-disable-next-line import/order
import {
	GLOBAL_DRAFT_KEY,
	resolveDraftKey,
	resolveCollection,
	findDraftInCollection,
	warnDraftInvariant,
	getDraftSeed,
	getFamilyDraftSeed,
	resolveEffectiveSeed,
	findFamilyDraft,
	resolveLiveDraft,
	resolveFamilyVariation,
	deriveFamilyVariationAttributes,
	effectiveVariationAttributes,
	writeDraft,
} from '../draft-internals';

/**
 * A variable base product whose family backs most of this file's tests:
 * variation 20 fixes `Color` but leaves `Logo` as "any" (`value: null`);
 * variation 21 fixes both attributes concretely. `productVariations[20]`/
 * `[21]` deliberately carry no `attributes` field of their own — the real
 * Store API serializer leaves it empty, and nothing in `draft-internals.ts`
 * may read it.
 */
const baseVariableProduct = {
	id: 10,
	type: 'variable',
	variations: [
		{
			id: 20,
			attributes: [
				{ name: 'Color', value: 'blue' },
				{ name: 'Logo', value: null },
			],
		},
		{
			id: 21,
			attributes: [
				{ name: 'Color', value: 'red' },
				{ name: 'Logo', value: 'yes' },
			],
		},
	],
} as unknown as ProductResponseItem;

const variation20 = { id: 20, parent: 10, name: 'Blue' } as ProductResponseItem;
const variation21 = {
	id: 21,
	parent: 10,
	name: 'Red/Yes',
} as ProductResponseItem;

const simpleProduct = {
	id: 30,
	type: 'simple',
	variations: [],
} as unknown as ProductResponseItem;

let warnSpy: jest.SpyInstance;
let originalNodeEnv: string | undefined;

beforeEach( () => {
	mockCartState = { draftItems: {} };
	mockProductsState = {
		products: {},
		productVariations: {},
		findProduct: jest.fn( findProductImpl ),
	};
	mockCartContext = undefined;
	mockCartContextThrows = false;
	mockServerState = undefined;
	originalNodeEnv = process.env.NODE_ENV;
	warnSpy = jest.spyOn( console, 'warn' ).mockImplementation( () => {} );
} );

afterEach( () => {
	warnSpy.mockRestore();
	process.env.NODE_ENV = originalNodeEnv;
} );

describe( 'resolveDraftKey', () => {
	it( 'returns the nearest declared context.draftKey when one is set', () => {
		mockCartContext = { draftKey: 'product-collection/123' };
		expect( resolveDraftKey() ).toBe( 'product-collection/123' );
	} );

	it( 'returns the reserved global key when context sets no draftKey', () => {
		mockCartContext = {};
		expect( resolveDraftKey() ).toBe( GLOBAL_DRAFT_KEY );
	} );

	it( 'degrades to the global key when called with no active directive scope', () => {
		mockCartContextThrows = true;
		expect( () => resolveDraftKey() ).not.toThrow();
		expect( resolveDraftKey() ).toBe( GLOBAL_DRAFT_KEY );
	} );
} );

describe( 'resolveCollection', () => {
	it( 'returns undefined for a not-yet-created collection', () => {
		expect( resolveCollection( GLOBAL_DRAFT_KEY ) ).toBeUndefined();
	} );

	it( 'returns the collection filed under key', () => {
		const draft = { id: 1, quantity: 2 } as DraftItem;
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [ draft ];
		expect( resolveCollection( GLOBAL_DRAFT_KEY ) ).toEqual( [ draft ] );
	} );
} );

describe( 'findDraftInCollection', () => {
	it( 'returns undefined when id is undefined', () => {
		expect(
			findDraftInCollection(
				[ { id: 1, quantity: 1 } as DraftItem ],
				undefined
			)
		).toBeUndefined();
	} );

	it( 'finds the matching draft by id', () => {
		const draft = { id: 5, quantity: 2 } as DraftItem;
		expect( findDraftInCollection( [ draft ], 5 ) ).toBe( draft );
	} );

	it( 'returns undefined when no draft matches', () => {
		const draft = { id: 5, quantity: 2 } as DraftItem;
		expect( findDraftInCollection( [ draft ], 6 ) ).toBeUndefined();
	} );
} );

describe( 'warnDraftInvariant', () => {
	it( 'warns in a dev build', () => {
		process.env.NODE_ENV = 'development';
		warnDraftInvariant( 'something went wrong' );
		expect( warnSpy ).toHaveBeenCalledWith(
			expect.stringContaining( 'something went wrong' )
		);
	} );

	it( 'stays silent in production', () => {
		process.env.NODE_ENV = 'production';
		warnDraftInvariant( 'something went wrong' );
		expect( warnSpy ).not.toHaveBeenCalled();
	} );
} );

describe( 'getDraftSeed', () => {
	it( 'returns undefined when no seed is filed', () => {
		expect( getDraftSeed( GLOBAL_DRAFT_KEY, 1 ) ).toBeUndefined();
	} );

	it( 'returns the seed filed for key/id', () => {
		const seed = { id: 1, quantity: 2 } as DraftItem;
		mockServerState = { draftSeeds: { [ GLOBAL_DRAFT_KEY ]: { 1: seed } } };
		expect( getDraftSeed( GLOBAL_DRAFT_KEY, 1 ) ).toBe( seed );
	} );
} );

describe( 'getFamilyDraftSeed', () => {
	it( 'returns the variation-keyed seed when one exists', () => {
		const variationSeed = { id: 20, quantity: 2 } as DraftItem;
		const parentSeed = { id: 10, quantity: 9 } as DraftItem;
		mockServerState = {
			draftSeeds: {
				[ GLOBAL_DRAFT_KEY ]: { 20: variationSeed, 10: parentSeed },
			},
		};
		expect( getFamilyDraftSeed( GLOBAL_DRAFT_KEY, 20, 10 ) ).toBe(
			variationSeed
		);
	} );

	it( 'falls back to the parent-keyed seed for quantity when no variation-keyed seed exists', () => {
		const parentSeed = { id: 10, quantity: 4 } as DraftItem;
		mockServerState = {
			draftSeeds: { [ GLOBAL_DRAFT_KEY ]: { 10: parentSeed } },
		};
		expect( getFamilyDraftSeed( GLOBAL_DRAFT_KEY, 20, 10 ) ).toBe(
			parentSeed
		);
	} );

	it( 'returns undefined when neither is filed', () => {
		expect(
			getFamilyDraftSeed( GLOBAL_DRAFT_KEY, 20, 10 )
		).toBeUndefined();
	} );
} );

describe( 'resolveEffectiveSeed', () => {
	beforeEach( () => {
		mockProductsState.products = {
			10: baseVariableProduct,
			30: simpleProduct,
		};
		mockProductsState.productVariations = {
			20: variation20,
			21: variation21,
		};
	} );

	it( 'returns the direct seed when one is filed for id', () => {
		const seed = { id: 20, quantity: 3 } as DraftItem;
		mockServerState = {
			draftSeeds: { [ GLOBAL_DRAFT_KEY ]: { 20: seed } },
		};
		expect( resolveEffectiveSeed( GLOBAL_DRAFT_KEY, 20 ) ).toBe( seed );
	} );

	it( 'returns the family seed re-addressed to a resolved variation id', () => {
		const parentSeed = { id: 10, quantity: 2 } as DraftItem;
		mockServerState = {
			draftSeeds: { [ GLOBAL_DRAFT_KEY ]: { 10: parentSeed } },
		};
		expect( resolveEffectiveSeed( GLOBAL_DRAFT_KEY, 20 ) ).toEqual( {
			id: 20,
			quantity: 2,
		} );
	} );

	it( 'is an identity no-change for a parent id', () => {
		const parentSeed = { id: 10, quantity: 2 } as DraftItem;
		mockServerState = {
			draftSeeds: { [ GLOBAL_DRAFT_KEY ]: { 10: parentSeed } },
		};
		expect( resolveEffectiveSeed( GLOBAL_DRAFT_KEY, 10 ) ).toBe(
			parentSeed
		);
	} );

	it( 'returns undefined when no seed exists at all', () => {
		expect( resolveEffectiveSeed( GLOBAL_DRAFT_KEY, 20 ) ).toBeUndefined();
	} );

	it( 'degrades to the direct lookup only for a non-family (parent-only) id', () => {
		const seed = { id: 30, quantity: 1 } as DraftItem;
		mockServerState = {
			draftSeeds: { [ GLOBAL_DRAFT_KEY ]: { 30: seed } },
		};
		expect( resolveEffectiveSeed( GLOBAL_DRAFT_KEY, 30 ) ).toBe( seed );
	} );
} );

describe( 'findFamilyDraft', () => {
	it( 'finds the draft whose id is the base product id', () => {
		const draft = { id: 10, quantity: 1 } as DraftItem;
		expect( findFamilyDraft( baseVariableProduct, [ draft ] ) ).toBe(
			draft
		);
	} );

	it( 'finds the draft whose id is one of the variation ids', () => {
		const draft = { id: 20, quantity: 1 } as DraftItem;
		expect( findFamilyDraft( baseVariableProduct, [ draft ] ) ).toBe(
			draft
		);
	} );

	it( 'returns undefined when no draft in the collection belongs to the family', () => {
		const draft = { id: 999, quantity: 1 } as DraftItem;
		expect(
			findFamilyDraft( baseVariableProduct, [ draft ] )
		).toBeUndefined();
	} );

	it( 'returns the last match in collection order when several family drafts exist', () => {
		const first = { id: 20, quantity: 1 } as DraftItem;
		const second = { id: 21, quantity: 2 } as DraftItem;
		expect(
			findFamilyDraft( baseVariableProduct, [ first, second ] )
		).toBe( second );
	} );
} );

describe( 'resolveLiveDraft', () => {
	beforeEach( () => {
		mockProductsState.products = { 10: baseVariableProduct };
		mockProductsState.productVariations = {
			20: variation20,
			21: variation21,
		};
	} );

	it( 'resolves the exact-id draft first', () => {
		const draft = { id: 20, quantity: 1 } as DraftItem;
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [ draft ];
		expect( resolveLiveDraft( GLOBAL_DRAFT_KEY, 20 ) ).toBe( draft );
	} );

	it( 'falls back to the family draft when no exact-id draft exists', () => {
		const draft = { id: 10, quantity: 1 } as DraftItem;
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [ draft ];
		expect( resolveLiveDraft( GLOBAL_DRAFT_KEY, 20 ) ).toBe( draft );
	} );

	it( 'returns undefined when neither an exact nor a family draft exists', () => {
		expect( resolveLiveDraft( GLOBAL_DRAFT_KEY, 20 ) ).toBeUndefined();
	} );
} );

describe( 'resolveFamilyVariation', () => {
	beforeEach( () => {
		mockProductsState.products = { 10: baseVariableProduct };
		mockProductsState.productVariations = {
			20: variation20,
			21: variation21,
		};
	} );

	it( 'maps a family draft with attributes to the matching variation', () => {
		const draft = {
			id: 10,
			quantity: 1,
			variation: [
				{ attribute: 'Color', value: 'red' },
				{ attribute: 'Logo', value: 'yes' },
			],
		} as DraftItem;
		expect( resolveFamilyVariation( baseVariableProduct, draft ) ).toBe(
			variation21
		);
	} );

	it( 'maps an attrs-less draft carrying a variation id directly (the id-direct rung)', () => {
		const draft = { id: 20, quantity: 1 } as DraftItem;
		expect( resolveFamilyVariation( baseVariableProduct, draft ) ).toBe(
			variation20
		);
	} );

	it( 'yields null for a base-parent draft with an empty variation array', () => {
		const draft = { id: 10, quantity: 1, variation: [] } as DraftItem;
		expect(
			resolveFamilyVariation( baseVariableProduct, draft )
		).toBeNull();
	} );

	it( 'yields null for a base-parent draft with no variation field at all', () => {
		const draft = { id: 10, quantity: 1 } as DraftItem;
		expect(
			resolveFamilyVariation( baseVariableProduct, draft )
		).toBeNull();
	} );

	it( 'yields null when the specified attributes match no variation', () => {
		const draft = {
			id: 10,
			quantity: 1,
			variation: [ { attribute: 'Color', value: 'green' } ],
		} as DraftItem;
		expect(
			resolveFamilyVariation( baseVariableProduct, draft )
		).toBeNull();
	} );

	it( 'returns null when no draft is given', () => {
		expect(
			resolveFamilyVariation( baseVariableProduct, undefined )
		).toBeNull();
	} );
} );

describe( 'deriveFamilyVariationAttributes', () => {
	it( "derives a fully-concrete variation's attribute set from base.variations[]", () => {
		const result = deriveFamilyVariationAttributes(
			baseVariableProduct,
			21,
			undefined
		);
		expect( result ).toEqual( [
			{ attribute: 'Color', value: 'red' },
			{ attribute: 'Logo', value: 'yes' },
		] );
	} );

	it( "preserves the existing family draft's value for an any attribute", () => {
		const existingDraft = {
			id: 20,
			quantity: 1,
			variation: [ { attribute: 'Logo', value: 'existing-value' } ],
		} as DraftItem;
		const result = deriveFamilyVariationAttributes(
			baseVariableProduct,
			20,
			existingDraft
		);
		expect( result ).toEqual( [
			{ attribute: 'Color', value: 'blue' },
			{ attribute: 'Logo', value: 'existing-value' },
		] );
	} );

	it( 'omits an any attribute with no derivable value, never inventing one', () => {
		const result = deriveFamilyVariationAttributes(
			baseVariableProduct,
			20,
			undefined
		);
		expect( result ).toEqual( [ { attribute: 'Color', value: 'blue' } ] );
	} );

	it( 'omits an any attribute whose existing draft value is itself falsy (falsy-canonicalized)', () => {
		const existingDraft = {
			id: 20,
			quantity: 1,
			variation: [ { attribute: 'Logo', value: '' } ],
		} as DraftItem;
		const result = deriveFamilyVariationAttributes(
			baseVariableProduct,
			20,
			existingDraft
		);
		expect( result ).toEqual( [ { attribute: 'Color', value: 'blue' } ] );
	} );

	it( "returns an empty array when variationId names none of base's variations", () => {
		expect(
			deriveFamilyVariationAttributes(
				baseVariableProduct,
				999,
				undefined
			)
		).toEqual( [] );
	} );
} );

describe( 'effectiveVariationAttributes', () => {
	it( "returns the variation's concrete meta attributes given an empty specified set", () => {
		const result = effectiveVariationAttributes(
			baseVariableProduct,
			21,
			[]
		);
		expect( result ).toEqual( [
			{ attribute: 'Color', value: 'red' },
			{ attribute: 'Logo', value: 'yes' },
		] );
	} );

	it( 'returns the specified values when they complete every attribute', () => {
		const specified = [
			{ attribute: 'Color', value: 'blue' },
			{ attribute: 'Logo', value: 'gold' },
		];
		const result = effectiveVariationAttributes(
			baseVariableProduct,
			20,
			specified
		);
		expect( result ).toEqual( specified );
	} );

	it( 'returns the incomplete signal given an unspecified "any" attribute', () => {
		const result = effectiveVariationAttributes(
			baseVariableProduct,
			20,
			[]
		);
		expect( result ).toBeUndefined();
	} );

	it( 'passes the specified attributes through unchanged for a non-variation id', () => {
		const specified = [ { attribute: 'Color', value: 'blue' } ];
		const result = effectiveVariationAttributes(
			baseVariableProduct,
			10,
			specified
		);
		expect( result ).toBe( specified );
	} );

	it( 'passes the specified attributes through unchanged for absent family data', () => {
		const specified = [ { attribute: 'Color', value: 'blue' } ];
		const result = effectiveVariationAttributes( undefined, 20, specified );
		expect( result ).toBe( specified );
	} );
} );

describe( 'writeDraft', () => {
	beforeEach( () => {
		mockProductsState.products = {
			10: baseVariableProduct,
			30: simpleProduct,
		};
		mockProductsState.productVariations = {
			20: variation20,
			21: variation21,
		};
	} );

	it( 'materializes exactly one draft composed from the seed on an untouched surface, applying the edited property and leaving unspecified fields at their seeded defaults', () => {
		mockServerState = {
			draftSeeds: {
				[ GLOBAL_DRAFT_KEY ]: {
					30: {
						id: 30,
						quantity: 1,
						'my-plugin/note': 'hello',
					} as DraftItem,
				},
			},
		};
		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toBeUndefined();

		writeDraft( 30, 'quantity', 5 );

		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{ id: 30, quantity: 5, 'my-plugin/note': 'hello' },
		] );
	} );

	it( 'merges into an existing live draft instead of duplicating', () => {
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
			{ id: 30, quantity: 1 } as DraftItem,
		];

		writeDraft( 30, 'quantity', 7 );

		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{ id: 30, quantity: 7 },
		] );
	} );

	it( 'merges a write targeting a family id onto the existing family draft rather than creating a second one', () => {
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
			{ id: 10, quantity: 2 } as DraftItem,
		];

		writeDraft( 20, 'quantity', 9 );

		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{ id: 10, quantity: 9 },
		] );
	} );

	it( 're-files the draft under the matched variation id on a variation write, carrying quantity and extension props across unchanged', () => {
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
			{ id: 10, quantity: 3, 'my-plugin/note': 'hi' } as DraftItem,
		];

		writeDraft( 10, 'variation', [
			{ attribute: 'Color', value: 'red' },
			{ attribute: 'Logo', value: 'yes' },
		] );

		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{
				id: 21,
				quantity: 3,
				'my-plugin/note': 'hi',
				variation: [
					{ attribute: 'Color', value: 'red' },
					{ attribute: 'Logo', value: 'yes' },
				],
			},
		] );
	} );

	it( 're-files the draft under the base parent id when the written attributes match no variation', () => {
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
			{ id: 21, quantity: 3 } as DraftItem,
		];

		writeDraft( 21, 'variation', [
			{ attribute: 'Color', value: 'green' },
		] );

		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{
				id: 10,
				quantity: 3,
				variation: [ { attribute: 'Color', value: 'green' } ],
			},
		] );
	} );

	it( 'falls back to the parent-keyed seed for quantity when materializing at a resolved variation id with no variation-keyed seed', () => {
		mockServerState = {
			draftSeeds: {
				[ GLOBAL_DRAFT_KEY ]: {
					10: { id: 10, quantity: 4 } as DraftItem,
				},
			},
		};

		writeDraft( 20, 'my-plugin/note', 'hi' );

		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{ id: 20, quantity: 4, 'my-plugin/note': 'hi' },
		] );
	} );

	it( 'materializes a quantity-less draft with a dev warning', () => {
		process.env.NODE_ENV = 'development';

		writeDraft( 40, 'my-plugin/note', 'hi' );

		expect( warnSpy ).toHaveBeenCalled();
		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{ id: 40, 'my-plugin/note': 'hi' },
		] );
	} );

	it( 'stays silent in production for the same quantity-less materialization', () => {
		process.env.NODE_ENV = 'production';

		writeDraft( 40, 'my-plugin/note', 'hi' );

		expect( warnSpy ).not.toHaveBeenCalled();
	} );

	it( "rejects a write that would change an existing draft's id in place (dev warn, state unchanged)", () => {
		mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] = [
			{ id: 30, quantity: 1 } as DraftItem,
		];

		writeDraft( 30, 'id', 999 );

		expect( warnSpy ).toHaveBeenCalled();
		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{ id: 30, quantity: 1 },
		] );
	} );

	it( 'degrades a variation write on an unrecognized id to writing variation unchanged, attempting no id migration', () => {
		writeDraft( 999, 'variation', [
			{ attribute: 'Color', value: 'blue' },
		] );

		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toEqual( [
			{ id: 999, variation: [ { attribute: 'Color', value: 'blue' } ] },
		] );
	} );

	it( 'resolves the nearest declared draft key rather than always the global key', () => {
		mockCartContext = { draftKey: 'single-product/42' };

		writeDraft( 30, 'quantity', 2 );

		expect( mockCartState.draftItems[ 'single-product/42' ] ).toEqual( [
			{ id: 30, quantity: 2 },
		] );
		expect( mockCartState.draftItems[ GLOBAL_DRAFT_KEY ] ).toBeUndefined();
	} );
} );
