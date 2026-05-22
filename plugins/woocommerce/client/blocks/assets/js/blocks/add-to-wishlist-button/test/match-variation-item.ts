/**
 * Internal dependencies
 */
import { matchVariationItem } from '../match-variation-item';

describe( 'matchVariationItem', () => {
	const redLarge = {
		id: 42,
		variation: [
			{ attribute: 'Color', value: 'Red' },
			{ attribute: 'Size', value: 'Large' },
		],
	};
	const redSmall = {
		id: 42,
		variation: [
			{ attribute: 'Color', value: 'Red' },
			{ attribute: 'Size', value: 'Small' },
		],
	};

	it( 'returns false when the item id differs', () => {
		expect(
			matchVariationItem( redLarge, 99, [
				{ attribute: 'Color', value: 'red' },
				{ attribute: 'Size', value: 'large' },
			] )
		).toBe( false );
	} );

	it( 'matches an "any" variation by attribute values when ids collide', () => {
		// Two wishlist rows share id=42 (same variation product, different
		// "any" slot values). The matcher must pick exactly the one whose
		// attribute set matches the shopper's current selection.
		const selectedLarge = [
			{ attribute: 'Color', value: 'red' },
			{ attribute: 'Size', value: 'large' },
		];

		expect( matchVariationItem( redLarge, 42, selectedLarge ) ).toBe(
			true
		);
		expect( matchVariationItem( redSmall, 42, selectedLarge ) ).toBe(
			false
		);
	} );

	it( 'is case-insensitive on values (slug vs term display name)', () => {
		// Store API returns `value: "Red"`; ATCWO writes `value: "red"`. The
		// comparison must bridge the case difference.
		expect(
			matchVariationItem( redLarge, 42, [
				{ attribute: 'Color', value: 'red' },
				{ attribute: 'Size', value: 'large' },
			] )
		).toBe( true );
	} );

	it( 'returns false when the picked attribute set size differs', () => {
		// Edge: shopper picks just Color; the row has both Color and Size.
		expect(
			matchVariationItem( redLarge, 42, [
				{ attribute: 'Color', value: 'red' },
			] )
		).toBe( false );
	} );

	it( 'treats a missing variation array as empty', () => {
		// Server omits `variation` for items where the saved product is no
		// longer purchasable. Should never spuriously match.
		const noVariation = { id: 42 };
		expect(
			matchVariationItem( noVariation, 42, [
				{ attribute: 'Color', value: 'red' },
			] )
		).toBe( false );

		// With no picked attributes and no stored variation, it's a match.
		expect( matchVariationItem( noVariation, 42, [] ) ).toBe( true );
	} );

	it( 'returns false when an attribute name differs (label mismatch)', () => {
		expect(
			matchVariationItem( redLarge, 42, [
				{ attribute: 'Colour', value: 'red' },
				{ attribute: 'Size', value: 'large' },
			] )
		).toBe( false );
	} );
} );
