/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	deepEqual,
	isEmptyValue,
	getDraftExtensionProps,
	draftPropsMatchLineExtensions,
	lineHasUnaccountedContent,
	isGenericExactPair,
	type DraftItem,
} from '../cart-item-matching';

/**
 * Build a minimal cart line for matcher tests. Only the fields the matcher
 * reads (`id`, `key`, `extensions`, `item_data`) matter here.
 */
function makeLine( overrides: Partial< CartItem > ): CartItem {
	return {
		key: 'k',
		id: 1,
		type: 'simple',
		quantity: 1,
		extensions: {},
		item_data: [],
		...overrides,
	} as CartItem;
}

describe( 'cart-item-matching — pure ladder helpers', () => {
	describe( 'deepEqual', () => {
		it( 'compares primitives, arrays (order-sensitive) and plain objects (order-insensitive)', () => {
			expect( deepEqual( 1, 1 ) ).toBe( true );
			expect( deepEqual( 'a', 'a' ) ).toBe( true );
			expect( deepEqual( 'a', 'b' ) ).toBe( false );
			expect( deepEqual( [ 1, 2 ], [ 1, 2 ] ) ).toBe( true );
			expect( deepEqual( [ 1, 2 ], [ 2, 1 ] ) ).toBe( false );
			expect( deepEqual( { a: 1, b: 2 }, { b: 2, a: 1 } ) ).toBe( true );
			expect( deepEqual( { a: 1 }, { a: 1, b: 2 } ) ).toBe( false );
			expect(
				deepEqual( { a: [ { x: 1 } ] }, { a: [ { x: 1 } ] } )
			).toBe( true );
		} );

		it( 'does not treat [] and {} as equal', () => {
			expect( deepEqual( [], {} ) ).toBe( false );
		} );
	} );

	describe( 'isEmptyValue', () => {
		it( 'normalizes undefined/null/""/[]/{} as empty', () => {
			expect( isEmptyValue( undefined ) ).toBe( true );
			expect( isEmptyValue( null ) ).toBe( true );
			expect( isEmptyValue( '' ) ).toBe( true );
			expect( isEmptyValue( [] ) ).toBe( true );
			expect( isEmptyValue( {} ) ).toBe( true );
		} );

		it( 'treats non-empty scalars/arrays/objects as non-empty', () => {
			expect( isEmptyValue( 0 ) ).toBe( false );
			expect( isEmptyValue( 'x' ) ).toBe( false );
			expect( isEmptyValue( [ 1 ] ) ).toBe( false );
			expect( isEmptyValue( { a: 1 } ) ).toBe( false );
		} );

		it( 'is DEEP: an object/array whose members are all recursively empty is empty', () => {
			// A touched-then-cleared draft prop and a nested empty shape both
			// read as empty so pairing stays forgiving.
			expect( isEmptyValue( { ns: { field: '' } } ) ).toBe( true );
			expect( isEmptyValue( { ns: {} } ) ).toBe( true );
			expect( isEmptyValue( { a: null, b: undefined } ) ).toBe( true );
			expect( isEmptyValue( [ {} ] ) ).toBe( true );
			expect( isEmptyValue( [ { field: '' }, [] ] ) ).toBe( true );
		} );

		it( 'stays non-empty when any nested member carries a value', () => {
			expect( isEmptyValue( { ns: { field: 'A' } } ) ).toBe( false );
			expect( isEmptyValue( { ns: { field: 0 } } ) ).toBe( false );
			expect( isEmptyValue( [ { field: 'A' } ] ) ).toBe( false );
		} );
	} );

	describe( 'getDraftExtensionProps', () => {
		it( 'returns only namespaced props, excluding reserved envelope keys', () => {
			const draft: DraftItem = {
				id: 100,
				quantity: 2,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'green' },
				],
				'my-plugin/gift-note': 'A',
				'other/flag': true,
			};
			expect( getDraftExtensionProps( draft ) ).toEqual( {
				'my-plugin/gift-note': 'A',
				'other/flag': true,
			} );
		} );

		it( 'returns an empty object for a bare draft', () => {
			expect( getDraftExtensionProps( { id: 5, quantity: 1 } ) ).toEqual(
				{}
			);
		} );

		it( 'excludes the reserved `draftKey` routing field (never an extension prop, never POSTed)', () => {
			const draft = {
				id: 5,
				quantity: 1,
				draftKey: 'custom-key',
				'my-plugin/note': 'A',
			} as unknown as DraftItem;
			expect( getDraftExtensionProps( draft ) ).toEqual( {
				'my-plugin/note': 'A',
			} );
		} );
	} );

	describe( 'draftPropsMatchLineExtensions', () => {
		it( 'matches when the draft prop deep-equals the line extension', () => {
			const line = makeLine( {
				extensions: { 'my-plugin': { note: 'A' } },
			} );
			expect(
				draftPropsMatchLineExtensions(
					{ 'my-plugin': { note: 'A' } },
					line
				)
			).toBe( true );
		} );

		it( 'fails when the draft prop differs from the line extension', () => {
			const line = makeLine( {
				extensions: { 'my-plugin': { note: 'A' } },
			} );
			expect(
				draftPropsMatchLineExtensions(
					{ 'my-plugin': { note: 'B' } },
					line
				)
			).toBe( false );
		} );

		it( 'treats absent line extension and empty draft prop as equal', () => {
			const line = makeLine( { extensions: {} } );
			expect(
				draftPropsMatchLineExtensions( { 'my-plugin': '' }, line )
			).toBe( true );
		} );

		it( 'a non-empty draft prop against an absent line extension fails', () => {
			const line = makeLine( { extensions: {} } );
			expect(
				draftPropsMatchLineExtensions( { 'my-plugin': 'A' }, line )
			).toBe( false );
		} );
	} );

	describe( 'lineHasUnaccountedContent (presence heuristic)', () => {
		it( 'flags a line with a non-empty extension the draft has no prop for', () => {
			const line = makeLine( {
				extensions: { 'my-plugin': { note: 'A' } },
			} );
			expect( lineHasUnaccountedContent( {}, line ) ).toBe( true );
		} );

		it( 'does not flag a line whose non-empty extension the draft accounts for', () => {
			const line = makeLine( {
				extensions: { 'my-plugin': { note: 'A' } },
			} );
			expect(
				lineHasUnaccountedContent(
					{ 'my-plugin': { note: 'A' } },
					line
				)
			).toBe( false );
		} );

		it( 'flags a line with visible (non-hidden) item_data', () => {
			const line = makeLine( {
				item_data: [ { key: 'Gift note', value: 'A' } ],
			} );
			expect( lineHasUnaccountedContent( {}, line ) ).toBe( true );
		} );

		it( 'does not flag a line whose item_data is all hidden', () => {
			const line = makeLine( {
				item_data: [ { key: '_internal', value: 'x', hidden: true } ],
			} );
			expect( lineHasUnaccountedContent( {}, line ) ).toBe( false );
		} );

		it( 'does not flag a plain line with no extensions or item_data', () => {
			expect( lineHasUnaccountedContent( {}, makeLine( {} ) ) ).toBe(
				false
			);
		} );
	} );

	describe( 'isGenericExactPair', () => {
		it( 'pairs when props match and there is no unaccounted content', () => {
			const line = makeLine( {
				extensions: { 'my-plugin': 'A' },
			} );
			expect( isGenericExactPair( { 'my-plugin': 'A' }, line ) ).toBe(
				true
			);
		} );

		it( 'does not pair when the line carries unaccounted content', () => {
			const line = makeLine( {
				extensions: { 'my-plugin': 'A', 'other-plugin': 'X' },
			} );
			// Draft only accounts for my-plugin; other-plugin is unaccounted.
			expect( isGenericExactPair( { 'my-plugin': 'A' }, line ) ).toBe(
				false
			);
		} );

		it( 'pairs a line exposing `{ ns: { field: "" } }` with a prop-less draft (deep-empty normalization)', () => {
			// A note-less line whose extension callback echoes an empty-shaped
			// projection must still pair with a draft that never touched the
			// field. Deep `isEmptyValue` treats the nested empty as absent.
			const line = makeLine( {
				extensions: { 'wc-gift-note-demo': { 'gift-note': '' } },
			} );
			expect( isGenericExactPair( {}, line ) ).toBe( true );
		} );

		it( 'pairs a touched-then-cleared draft prop `{ ns: { field: "" } }` with a bare line `{}`', () => {
			// The shopper typed a note then cleared it: the draft holds
			// `{ ns: { field: '' } }`. The line carries nothing under the
			// namespace. Both normalize empty → they pair.
			const line = makeLine( { extensions: {} } );
			expect(
				isGenericExactPair(
					{ 'wc-gift-note-demo': { 'gift-note': '' } },
					line
				)
			).toBe( true );
		} );
	} );
} );
