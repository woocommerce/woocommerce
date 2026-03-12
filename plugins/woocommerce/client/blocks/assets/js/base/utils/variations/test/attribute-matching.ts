/**
 * Internal dependencies
 */
import {
	normalizeAttributeName,
	attributeNamesMatch,
	getVariationAttributeValue,
	findMatchingVariation,
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

describe( 'findMatchingVariation', () => {
	const product = {
		id: 1,
		type: 'variable',
		variations: [
			{
				id: 101,
				attributes: [
					{ name: 'Color', value: 'Blue' },
					{ name: 'Size', value: 'Small' },
				],
			},
			{
				id: 102,
				attributes: [
					{ name: 'Color', value: 'Blue' },
					{ name: 'Size', value: 'Large' },
				],
			},
			{
				id: 103,
				attributes: [
					{ name: 'Color', value: 'Red' },
					{ name: 'Size', value: 'Small' },
				],
			},
		],
	};

	it( 'returns null when product has no variations', () => {
		const productNoVariations = { id: 1, type: 'variable', variations: [] };
		const selectedAttributes = [ { attribute: 'Color', value: 'Blue' } ];
		expect(
			findMatchingVariation( productNoVariations, selectedAttributes )
		).toBeNull();
	} );

	it( 'returns null when no attributes are selected', () => {
		expect( findMatchingVariation( product, [] ) ).toBeNull();
	} );

	it( 'finds exact match with all attributes', () => {
		const selectedAttributes = [
			{ attribute: 'Color', value: 'Blue' },
			{ attribute: 'Size', value: 'Large' },
		];
		const result = findMatchingVariation( product, selectedAttributes );
		expect( result?.id ).toBe( 102 );
	} );

	it( 'matches with attribute prefix in selected attributes', () => {
		const selectedAttributes = [
			{ attribute: 'attribute_pa_color', value: 'Blue' },
			{ attribute: 'attribute_pa_size', value: 'Small' },
		];
		const result = findMatchingVariation( product, selectedAttributes );
		expect( result?.id ).toBe( 101 );
	} );

	it( 'returns null when no variation matches', () => {
		const selectedAttributes = [
			{ attribute: 'Color', value: 'Green' },
			{ attribute: 'Size', value: 'Small' },
		];
		expect(
			findMatchingVariation( product, selectedAttributes )
		).toBeNull();
	} );

	describe( 'multi-word attribute names', () => {
		const productWithMultiWord = {
			id: 3,
			type: 'variable',
			variations: [
				{
					id: 301,
					attributes: [
						{ name: 'Color', value: 'Blue' },
						{ name: 'numeric size', value: '42' },
					],
				},
				{
					id: 302,
					attributes: [
						{ name: 'Color', value: 'Red' },
						{ name: 'numeric size', value: '44' },
					],
				},
			],
		};

		it( 'matches when selected attributes use hyphenated slugs', () => {
			const result = findMatchingVariation( productWithMultiWord, [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_numeric-size', value: '42' },
			] );
			expect( result?.id ).toBe( 301 );
		} );

		it( 'matches when Store API uses hyphens instead of spaces', () => {
			const productWithHyphens = {
				id: 4,
				type: 'variable',
				variations: [
					{
						id: 401,
						attributes: [ { name: 'numeric-size', value: '42' } ],
					},
				],
			};
			const result = findMatchingVariation( productWithHyphens, [
				{ attribute: 'attribute_pa_numeric-size', value: '42' },
			] );
			expect( result?.id ).toBe( 401 );
		} );
	} );

	describe( 'Any attribute handling', () => {
		const productWithAny = {
			id: 2,
			type: 'variable',
			variations: [
				{
					id: 201,
					attributes: [
						{ name: 'Color', value: null }, // "Any" color
						{ name: 'Size', value: 'Small' },
					],
				},
				{
					id: 202,
					attributes: [
						{ name: 'Color', value: 'Blue' },
						{ name: 'Size', value: null }, // "Any" size
					],
				},
			],
		};

		it( 'matches variation with "Any" attribute when value is selected', () => {
			const selectedAttributes = [
				{ attribute: 'Color', value: 'Red' },
				{ attribute: 'Size', value: 'Small' },
			];
			const result = findMatchingVariation(
				productWithAny,
				selectedAttributes
			);
			expect( result?.id ).toBe( 201 );
		} );

		it( 'does not match "Any" attribute when selected value is null', () => {
			const selectedAttributes = [
				{ attribute: 'Color', value: null },
				{ attribute: 'Size', value: 'Small' },
			];
			expect(
				findMatchingVariation( productWithAny, selectedAttributes )
			).toBeNull();
		} );

		it( 'does not match "Any" attribute when attribute is not selected', () => {
			const selectedAttributes = [
				{ attribute: 'Size', value: 'Small' },
			];
			expect(
				findMatchingVariation( productWithAny, selectedAttributes )
			).toBeNull();
		} );
	} );
} );
