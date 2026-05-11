/**
 * Internal dependencies
 */
import {
	normalizeAttributeName,
	attributeNamesMatch,
	getVariationAttributeValue,
} from '../attribute-matching';

describe( 'normalizeAttributeName', () => {
	it( 'strips attribute_ prefix', () => {
		expect( normalizeAttributeName( 'attribute_color' ) ).toBe( 'color' );
	} );

	it( 'strips attribute_pa_ prefix', () => {
		expect( normalizeAttributeName( 'attribute_pa_color' ) ).toBe(
			'color'
		);
	} );

	it( 'returns lowercased name without prefix', () => {
		expect( normalizeAttributeName( 'Color' ) ).toBe( 'color' );
	} );

	it( 'replaces hyphens with spaces for multi-word slugs', () => {
		expect( normalizeAttributeName( 'attribute_pa_numeric-size' ) ).toBe(
			'numeric size'
		);
	} );

	it( 'replaces hyphens with spaces without prefix', () => {
		expect( normalizeAttributeName( 'numeric-size' ) ).toBe(
			'numeric size'
		);
	} );
} );

describe( 'attributeNamesMatch', () => {
	it( 'matches case-insensitively', () => {
		expect( attributeNamesMatch( 'Color', 'color' ) ).toBe( true );
	} );

	it( 'matches after stripping prefix', () => {
		expect( attributeNamesMatch( 'attribute_pa_color', 'Color' ) ).toBe(
			true
		);
	} );

	it( 'matches when both have prefixes', () => {
		expect(
			attributeNamesMatch( 'attribute_pa_color', 'attribute_color' )
		).toBe( true );
	} );

	it( 'matches hyphenated slug against spaced label', () => {
		expect(
			attributeNamesMatch( 'attribute_pa_numeric-size', 'numeric size' )
		).toBe( true );
	} );

	it( 'matches hyphenated slug against capitalized spaced label', () => {
		expect(
			attributeNamesMatch( 'attribute_pa_numeric-size', 'Numeric Size' )
		).toBe( true );
	} );

	it( 'matches two hyphenated names', () => {
		expect(
			attributeNamesMatch( 'attribute_pa_numeric-size', 'numeric-size' )
		).toBe( true );
	} );

	it( 'returns false for different names', () => {
		expect( attributeNamesMatch( 'color', 'size' ) ).toBe( false );
	} );
} );

describe( 'getVariationAttributeValue', () => {
	const variation = {
		id: 123,
		attributes: [
			{ name: 'Color', value: 'Blue' },
			{ name: 'Size', value: 'Large' },
		],
	};

	it( 'finds attribute value by exact name', () => {
		expect( getVariationAttributeValue( variation, 'Color' ) ).toBe(
			'Blue'
		);
	} );

	it( 'finds attribute value case-insensitively', () => {
		expect( getVariationAttributeValue( variation, 'color' ) ).toBe(
			'Blue'
		);
	} );

	it( 'finds attribute value when using prefix', () => {
		expect(
			getVariationAttributeValue( variation, 'attribute_pa_color' )
		).toBe( 'Blue' );
	} );

	it( 'returns undefined for non-existent attribute', () => {
		expect(
			getVariationAttributeValue( variation, 'material' )
		).toBeUndefined();
	} );

	describe( 'multi-word attribute names', () => {
		const variationWithSpaces = {
			id: 456,
			attributes: [ { name: 'numeric size', value: '42' } ],
		};

		const variationWithHyphens = {
			id: 789,
			attributes: [ { name: 'numeric-size', value: '44' } ],
		};

		it( 'finds value when slug has hyphens and Store API has spaces', () => {
			expect(
				getVariationAttributeValue(
					variationWithSpaces,
					'attribute_pa_numeric-size'
				)
			).toBe( '42' );
		} );

		it( 'finds value when both use hyphens', () => {
			expect(
				getVariationAttributeValue(
					variationWithHyphens,
					'attribute_pa_numeric-size'
				)
			).toBe( '44' );
		} );

		it( 'finds value when both use spaces', () => {
			expect(
				getVariationAttributeValue(
					variationWithSpaces,
					'numeric size'
				)
			).toBe( '42' );
		} );
	} );
} );
