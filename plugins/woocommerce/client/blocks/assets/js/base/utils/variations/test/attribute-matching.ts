/**
 * Internal dependencies
 */
import {
	getVariationAttributeValue,
	findMatchingVariation,
} from '../attribute-matching';

describe( 'getVariationAttributeValue', () => {
	const variation = {
		id: 123,
		attributes: [
			{ name: 'Color', slug: 'attribute_pa_color', value: 'Blue' },
			{ name: 'Size', slug: 'attribute_pa_size', value: 'Large' },
		],
	};

	it( 'finds attribute value by slug', () => {
		expect(
			getVariationAttributeValue( variation, 'attribute_pa_color' )
		).toBe( 'Blue' );
	} );

	it( 'returns undefined for unknown slug', () => {
		expect(
			getVariationAttributeValue( variation, 'attribute_pa_material' )
		).toBeUndefined();
	} );

	it( 'does not match by label', () => {
		expect(
			getVariationAttributeValue( variation, 'Color' )
		).toBeUndefined();
	} );

	it( 'handles special characters where slug differs from label', () => {
		const variationWithUmlaut = {
			id: 456,
			attributes: [
				{
					name: 'Größe',
					slug: 'attribute_pa_groesse',
					value: 'xl',
				},
			],
		};
		expect(
			getVariationAttributeValue(
				variationWithUmlaut,
				'attribute_pa_groesse'
			)
		).toBe( 'xl' );
	} );

	it( 'handles custom slug that differs from label', () => {
		const variationWithCustom = {
			id: 789,
			attributes: [
				{
					name: 'Shirt Type',
					slug: 'attribute_pa_tshirt_type',
					value: 'polo',
				},
			],
		};
		expect(
			getVariationAttributeValue(
				variationWithCustom,
				'attribute_pa_tshirt_type'
			)
		).toBe( 'polo' );
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
					{
						name: 'Color',
						slug: 'attribute_pa_color',
						value: 'Blue',
					},
					{
						name: 'Size',
						slug: 'attribute_pa_size',
						value: 'Small',
					},
				],
			},
			{
				id: 102,
				attributes: [
					{
						name: 'Color',
						slug: 'attribute_pa_color',
						value: 'Blue',
					},
					{
						name: 'Size',
						slug: 'attribute_pa_size',
						value: 'Large',
					},
				],
			},
			{
				id: 103,
				attributes: [
					{
						name: 'Color',
						slug: 'attribute_pa_color',
						value: 'Red',
					},
					{
						name: 'Size',
						slug: 'attribute_pa_size',
						value: 'Small',
					},
				],
			},
		],
	};

	it( 'returns null when product has no variations', () => {
		const productNoVariations = { id: 1, type: 'variable', variations: [] };
		expect(
			findMatchingVariation( productNoVariations, [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
			] )
		).toBeNull();
	} );

	it( 'returns null when no attributes are selected', () => {
		expect( findMatchingVariation( product, [] ) ).toBeNull();
	} );

	it( 'finds exact match with all attributes', () => {
		const result = findMatchingVariation( product, [
			{ attribute: 'attribute_pa_color', value: 'Blue' },
			{ attribute: 'attribute_pa_size', value: 'Large' },
		] );
		expect( result?.id ).toBe( 102 );
	} );

	it( 'returns null when no variation matches', () => {
		expect(
			findMatchingVariation( product, [
				{ attribute: 'attribute_pa_color', value: 'Green' },
				{ attribute: 'attribute_pa_size', value: 'Small' },
			] )
		).toBeNull();
	} );

	describe( 'multi-attribute product with transliterated slugs', () => {
		// WordPress sanitize_title converts "Größe" → "groesse" and
		// "Farbe" → "farbe", but wc_attribute_label returns the original.
		const germanProduct = {
			id: 5,
			type: 'variable',
			variations: [
				{
					id: 501,
					attributes: [
						{
							name: 'Größe',
							slug: 'attribute_pa_groesse',
							value: 'xl',
						},
						{
							name: 'Farbe',
							slug: 'attribute_pa_farbe',
							value: 'dunkel-blau',
						},
					],
				},
				{
					id: 502,
					attributes: [
						{
							name: 'Größe',
							slug: 'attribute_pa_groesse',
							value: 'm',
						},
						{
							name: 'Farbe',
							slug: 'attribute_pa_farbe',
							value: 'hell-gruen',
						},
					],
				},
			],
		};

		it( 'matches variation with umlaut attribute and hyphenated term values', () => {
			const result = findMatchingVariation( germanProduct, [
				{ attribute: 'attribute_pa_groesse', value: 'xl' },
				{ attribute: 'attribute_pa_farbe', value: 'dunkel-blau' },
			] );
			expect( result?.id ).toBe( 501 );
		} );

		it( 'matches second variation correctly', () => {
			const result = findMatchingVariation( germanProduct, [
				{ attribute: 'attribute_pa_groesse', value: 'm' },
				{ attribute: 'attribute_pa_farbe', value: 'hell-gruen' },
			] );
			expect( result?.id ).toBe( 502 );
		} );

		it( 'returns null when values do not match any variation', () => {
			const result = findMatchingVariation( germanProduct, [
				{ attribute: 'attribute_pa_groesse', value: 'xl' },
				{ attribute: 'attribute_pa_farbe', value: 'hell-gruen' },
			] );
			expect( result ).toBeNull();
		} );
	} );

	describe( 'attribute with accented characters', () => {
		const accentProduct = {
			id: 6,
			type: 'variable',
			variations: [
				{
					id: 601,
					attributes: [
						{
							name: 'Café Style',
							slug: 'attribute_pa_cafe_style',
							value: 'latte',
						},
					],
				},
				{
					id: 602,
					attributes: [
						{
							name: 'Café Style',
							slug: 'attribute_pa_cafe_style',
							value: 'espresso',
						},
					],
				},
			],
		};

		it( 'matches when slug was transliterated from accented label', () => {
			const result = findMatchingVariation( accentProduct, [
				{ attribute: 'attribute_pa_cafe_style', value: 'espresso' },
			] );
			expect( result?.id ).toBe( 602 );
		} );
	} );

	describe( 'attribute with manually customized slug', () => {
		// Admin can set slug to anything, e.g. label "Shirt Type" slug "tshirt-type"
		const customSlugProduct = {
			id: 7,
			type: 'variable',
			variations: [
				{
					id: 701,
					attributes: [
						{
							name: 'Shirt Type',
							slug: 'attribute_pa_tshirt_type',
							value: 'polo',
						},
					],
				},
			],
		};

		it( 'matches when slug does not resemble the label at all', () => {
			const result = findMatchingVariation( customSlugProduct, [
				{ attribute: 'attribute_pa_tshirt_type', value: 'polo' },
			] );
			expect( result?.id ).toBe( 701 );
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
						{
							name: 'Color',
							slug: 'attribute_pa_color',
							value: null,
						}, // "Any" color
						{
							name: 'Size',
							slug: 'attribute_pa_size',
							value: 'Small',
						},
					],
				},
				{
					id: 202,
					attributes: [
						{
							name: 'Color',
							slug: 'attribute_pa_color',
							value: 'Blue',
						},
						{
							name: 'Size',
							slug: 'attribute_pa_size',
							value: null,
						}, // "Any" size
					],
				},
			],
		};

		it( 'matches variation with "Any" attribute when value is selected', () => {
			const result = findMatchingVariation( productWithAny, [
				{ attribute: 'attribute_pa_color', value: 'Red' },
				{ attribute: 'attribute_pa_size', value: 'Small' },
			] );
			expect( result?.id ).toBe( 201 );
		} );

		it( 'does not match "Any" attribute when selected value is null', () => {
			expect(
				findMatchingVariation( productWithAny, [
					{ attribute: 'attribute_pa_color', value: null },
					{ attribute: 'attribute_pa_size', value: 'Small' },
				] )
			).toBeNull();
		} );

		it( 'does not match "Any" attribute when attribute is not selected', () => {
			expect(
				findMatchingVariation( productWithAny, [
					{ attribute: 'attribute_pa_size', value: 'Small' },
				] )
			).toBeNull();
		} );
	} );
} );
