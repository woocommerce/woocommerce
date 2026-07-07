/**
 * Internal dependencies
 */
import type { Store, RawShopperListItem } from '../shopper-lists';

/**
 * Tests for the `woocommerce/shopper-lists` store's `findListItem` state
 * method — the UNIFIED list-row matcher that replaced the wishlist block's
 * bespoke `matchVariationItem`.
 *
 * `findListItem` reuses the products store's `variationMatchesSelection` as
 * the ONE STRUCTURAL matcher, so a selection pairs with a saved row by the
 * same structural rules the cart uses to resolve a variation: an "any"/absent
 * variation attribute never constrains, extra selected attributes are
 * ignored, attribute names compare normalized.
 *
 * VALUE normalization (trim + lowercase, both sides) happens at the LIST
 * boundary only: rows store term DISPLAY NAMES ("Red") while selections carry
 * slugs ("red"), so the boundary bridges case-only drift while the structural
 * matcher itself stays exact — the products-store (slug-to-slug) path keeps
 * exact value comparison, asserted in products.test.ts.
 *
 * The cases marked "semantic drift" below are ported from the deleted
 * `match-variation-item.ts` test. They pin the UNIFIED semantics:
 *   - exact-length: extra selected attributes are now IGNORED (was: mismatch).
 *   - case: values still match case-insensitively at THIS boundary (kept from
 *     the old matcher — dropping it would break display-name-vs-slug pairing),
 *     but via boundary normalization, not a bespoke comparator.
 */

type MockStore = { state: Store[ 'state' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {} as Store[ 'state' ];

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		// products.ts (loaded transitively for `variationMatchesSelection`)
		// reads its own `woocommerce/products` context; nothing here needs it,
		// so it resolves to null.
		getContext: jest.fn( () => null ),
		store: jest.fn( ( name: string, definition?: { state?: object } ) => {
			// Merge each store definition's state descriptors (getters/methods)
			// onto the shared mock state object, exactly like the cart-envelope
			// test. We only care about the shopper-lists registration; the
			// products registration (from the transitive import) is harmless.
			if ( name === 'woocommerce/shopper-lists' && definition?.state ) {
				for ( const key of Object.keys( definition.state ) ) {
					const descriptor = Object.getOwnPropertyDescriptor(
						definition.state,
						key
					);
					if ( descriptor ) {
						Object.defineProperty( mockState, key, descriptor );
					}
				}
				mockRegisteredStore = { state: mockState };
				return mockRegisteredStore;
			}
			// Any other registration (products, store-notices) — return a bare
			// stub; `findListItem` never reads them.
			return { state: {}, actions: {} };
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '../store-notices', () => ( {} ), { virtual: true } );

/** Load the store fresh and hand back its (mock) registration. */
function loadStore(): MockStore {
	let mod: MockStore | null = null;
	jest.isolateModules( () => {
		require( '../shopper-lists' );
		mod = mockRegisteredStore;
	} );
	return mod as unknown as MockStore;
}

/** Seed a list's items on the reactive state slot. */
function seedList(
	store: MockStore,
	slug: string,
	items: Partial< RawShopperListItem >[]
): void {
	store.state.lists = {
		[ slug ]: {
			items: items as RawShopperListItem[],
			isLoading: false,
		},
	};
}

describe( 'woocommerce/shopper-lists — findListItem (unified matcher)', () => {
	const SLUG = 'wishlist';

	// Two rows share id=42 (same variation product, different "any"-slot
	// values) — the classic disambiguation case. Stored `value`s use the term
	// DISPLAY name ("Red"/"Large"), as the Store API returns them.
	const redLarge: Partial< RawShopperListItem > = {
		key: 'red-large',
		id: 42,
		variation: [
			{
				raw_attribute: 'attribute_color',
				attribute: 'Color',
				value: 'Red',
			},
			{
				raw_attribute: 'attribute_size',
				attribute: 'Size',
				value: 'Large',
			},
		],
	};
	const redSmall: Partial< RawShopperListItem > = {
		key: 'red-small',
		id: 42,
		variation: [
			{
				raw_attribute: 'attribute_color',
				attribute: 'Color',
				value: 'Red',
			},
			{
				raw_attribute: 'attribute_size',
				attribute: 'Size',
				value: 'Small',
			},
		],
	};

	let store: MockStore;
	beforeEach( () => {
		mockRegisteredStore = null;
		for ( const key of Object.keys( mockState ) ) {
			delete ( mockState as Record< string, unknown > )[ key ];
		}
		store = loadStore();
	} );

	it( 'returns null when the list slug is unknown', () => {
		expect(
			store.state.findListItem( { slug: 'missing', id: 42 } )
		).toBeNull();
	} );

	it( 'returns null when no row has the id', () => {
		seedList( store, SLUG, [ redLarge ] );
		expect( store.state.findListItem( { slug: SLUG, id: 99 } ) ).toBeNull();
	} );

	it( 'matches a no-options product by id alone (empty selection)', () => {
		const simple: Partial< RawShopperListItem > = {
			key: 'simple',
			id: 7,
			variation: [],
		};
		seedList( store, SLUG, [ simple ] );
		expect( store.state.findListItem( { slug: SLUG, id: 7 } )?.key ).toBe(
			'simple'
		);
	} );

	it( 'disambiguates two "any"-slot rows by the picked attributes', () => {
		seedList( store, SLUG, [ redLarge, redSmall ] );
		// Realistic shapes: the selection carries SLUGS ("red"/"large") while
		// the rows store display names ("Red"/"Large"). Boundary normalization
		// bridges the casing; the structural match picks exactly the Large row.
		const match = store.state.findListItem( {
			slug: SLUG,
			id: 42,
			variation: [
				{ attribute: 'Color', value: 'red' },
				{ attribute: 'Size', value: 'large' },
			],
		} );
		expect( match?.key ).toBe( 'red-large' );
	} );

	// --- SEMANTIC-DRIFT REGRESSION CASES (ported, outcomes updated) --------

	it( 'DRIFT (exact-length): extra selected attributes are now IGNORED', () => {
		// Old matchVariationItem required stored.length === selected.length, so
		// picking only Color against a Color+Size row was a MISMATCH. Under the
		// unified rules the matcher iterates the STORED attributes: a row with a
		// non-"any" Size the selection omits fails rule 1 — but a row whose
		// extra attribute the selection DOES cover, plus extra picks, matches.
		//
		// Concretely: a row with just Color=Red, selection Color=Red + Size=Large
		// → the extra Size pick is ignored → MATCH (was: mismatch on length).
		const colorOnly: Partial< RawShopperListItem > = {
			key: 'color-only',
			id: 42,
			variation: [
				{
					raw_attribute: 'attribute_color',
					attribute: 'Color',
					value: 'Red',
				},
			],
		};
		seedList( store, SLUG, [ colorOnly ] );
		const match = store.state.findListItem( {
			slug: SLUG,
			id: 42,
			variation: [
				{ attribute: 'Color', value: 'Red' },
				{ attribute: 'Size', value: 'Large' },
			],
		} );
		expect( match?.key ).toBe( 'color-only' );
	} );

	it( 'DRIFT (exact-length): a stored non-"any" attr the selection omits still fails', () => {
		// The other half of the length divergence: the matcher iterates stored
		// attrs, so a row requiring Size that the selection does not provide is
		// excluded (rule 1) — the same "not a match" the old length check gave,
		// but for the principled reason.
		seedList( store, SLUG, [ redLarge ] );
		const match = store.state.findListItem( {
			slug: SLUG,
			id: 42,
			variation: [ { attribute: 'Color', value: 'Red' } ],
		} );
		expect( match ).toBeNull();
	} );

	it( 'DRIFT (case): list matching stays CASE-INSENSITIVE via boundary normalization', () => {
		// The datasets genuinely differ: rows store term display names ("Red",
		// via get_term_by('slug')->name) while selections carry slugs ("red").
		// The old matcher bridged this with a bespoke case-insensitive
		// comparator; the unified design keeps the bridge but moves it to the
		// LIST boundary (trim + lowercase on both sides) so the shared
		// structural matcher stays exact. Slug "red" must keep matching
		// display "Red" — a regression here silently empties every wishlist
		// star for "any"-slot variations.
		seedList( store, SLUG, [ redLarge ] );
		const match = store.state.findListItem( {
			slug: SLUG,
			id: 42,
			variation: [
				{ attribute: 'Color', value: 'red' },
				{ attribute: 'Size', value: 'large' },
			],
		} );
		expect( match?.key ).toBe( 'red-large' );

		// A value difference beyond case remains a mismatch, of course.
		expect(
			store.state.findListItem( {
				slug: SLUG,
				id: 42,
				variation: [
					{ attribute: 'Color', value: 'blue' },
					{ attribute: 'Size', value: 'large' },
				],
			} )
		).toBeNull();
	} );

	it( 'normalizes attribute NAMES (prefix/dashes/case) like the store', () => {
		// Name comparison stays tolerant via the store's attributeNamesMatch:
		// "attribute_pa_color" and "Color" normalize equal (it strips the
		// `attribute_pa_` prefix, swaps dashes for spaces, lower-cases).
		seedList( store, SLUG, [ redLarge ] );
		const match = store.state.findListItem( {
			slug: SLUG,
			id: 42,
			variation: [
				{ attribute: 'attribute_pa_color', value: 'Red' },
				{ attribute: 'attribute_pa_size', value: 'Large' },
			],
		} );
		expect( match?.key ).toBe( 'red-large' );
	} );

	it( 'treats a missing stored variation array as empty (id-only match)', () => {
		const noVariation: Partial< RawShopperListItem > = {
			key: 'no-variation',
			id: 42,
		};
		seedList( store, SLUG, [ noVariation ] );

		// With no stored attrs the row matches vacuously — a selection cannot
		// constrain what the row does not declare. (Old matcher returned false
		// when a non-empty selection was given; unified semantics ignores extra
		// picks, so it matches.)
		expect(
			store.state.findListItem( {
				slug: SLUG,
				id: 42,
				variation: [ { attribute: 'Color', value: 'Red' } ],
			} )?.key
		).toBe( 'no-variation' );

		// And with no picks it also matches on id alone.
		expect(
			store.state.findListItem( { slug: SLUG, id: 42, variation: [] } )
				?.key
		).toBe( 'no-variation' );
	} );

	it( 'returns the first matching row when several qualify', () => {
		// Two no-options rows share an id (variation []); an empty selection
		// satisfies both vacuously, so findListItem returns the first
		// (Array.prototype.find semantics). Concrete-attribute rows could not
		// stand in here: under the unified rules a row with a non-"any" stored
		// attribute is NOT satisfied by an empty selection (rule 1).
		const firstTwin: Partial< RawShopperListItem > = {
			key: 'twin-1',
			id: 42,
			variation: [],
		};
		const secondTwin: Partial< RawShopperListItem > = {
			key: 'twin-2',
			id: 42,
			variation: [],
		};
		seedList( store, SLUG, [ firstTwin, secondTwin ] );
		expect(
			store.state.findListItem( { slug: SLUG, id: 42, variation: [] } )
				?.key
		).toBe( 'twin-1' );
	} );
} );
