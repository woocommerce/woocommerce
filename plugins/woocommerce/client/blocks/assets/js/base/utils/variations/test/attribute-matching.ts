/**
 * Internal dependencies
 */
import {
	getVariationAttributeValue,
	findMatchingVariation,
} from '../attribute-matching';
import type { AttributeSlugToLabel } from '../attribute-matching';

const slugToLabel: AttributeSlugToLabel = {
	attribute_pa_color: 'Color',
	attribute_pa_size: 'Size',
};

describe( 'getVariationAttributeValue', () => {
	const variation = {
		id: 123,
		attributes: [
			{ name: 'Color', value: 'Blue' },
			{ name: 'Size', value: 'Large' },
		],
	};

	it( 'finds attribute value by slug using mapping', () => {
		expect(
			getVariationAttributeValue(
				variation,
				'attribute_pa_color',
				slugToLabel
			)
		).toBe( 'Blue' );
	} );

	it( 'returns undefined for unknown slug', () => {
		expect(
			getVariationAttributeValue(
				variation,
				'attribute_pa_material',
				slugToLabel
			)
		).toBeUndefined();
	} );

	it( 'returns undefined when slug not in mapping', () => {
		expect(
			getVariationAttributeValue( variation, 'Color', slugToLabel )
		).toBeUndefined();
	} );

	it( 'handles special characters where slug differs from label', () => {
		const variationWithUmlaut = {
			id: 456,
			attributes: [ { name: 'Größe', value: 'xl' } ],
		};
		const specialMapping: AttributeSlugToLabel = {
			attribute_pa_groesse: 'Größe',
		};
		expect(
			getVariationAttributeValue(
				variationWithUmlaut,
				'attribute_pa_groesse',
				specialMapping
			)
		).toBe( 'xl' );
	} );

	it( 'handles custom slug that differs from label', () => {
		const variationWithCustom = {
			id: 789,
			attributes: [ { name: 'Shirt Type', value: 'polo' } ],
		};
		const customMapping: AttributeSlugToLabel = {
			attribute_pa_tshirt_type: 'Shirt Type',
		};
		expect(
			getVariationAttributeValue(
				variationWithCustom,
				'attribute_pa_tshirt_type',
				customMapping
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
		expect(
			findMatchingVariation(
				productNoVariations,
				[ { attribute: 'attribute_pa_color', value: 'Blue' } ],
				slugToLabel
			)
		).toBeNull();
	} );

	it( 'returns null when no attributes are selected', () => {
		expect( findMatchingVariation( product, [], slugToLabel ) ).toBeNull();
	} );

	it( 'finds exact match with all attributes', () => {
		const result = findMatchingVariation(
			product,
			[
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Large' },
			],
			slugToLabel
		);
		expect( result?.id ).toBe( 102 );
	} );

	it( 'returns null when no variation matches', () => {
		expect(
			findMatchingVariation(
				product,
				[
					{ attribute: 'attribute_pa_color', value: 'Green' },
					{ attribute: 'attribute_pa_size', value: 'Small' },
				],
				slugToLabel
			)
		).toBeNull();
	} );

	describe( 'attributes with special characters in labels', () => {
		const specialMapping: AttributeSlugToLabel = {
			attribute_pa_groesse: 'Größe',
			attribute_pa_cafe_style: 'Café Style',
		};

		const productWithSpecialChars = {
			id: 3,
			type: 'variable',
			variations: [
				{
					id: 301,
					attributes: [
						{ name: 'Größe', value: 'xl' },
						{ name: 'Café Style', value: 'latte' },
					],
				},
			],
		};

		it( 'matches by slug-to-label mapping regardless of special characters', () => {
			const result = findMatchingVariation(
				productWithSpecialChars,
				[
					{ attribute: 'attribute_pa_groesse', value: 'xl' },
					{ attribute: 'attribute_pa_cafe_style', value: 'latte' },
				],
				specialMapping
			);
			expect( result?.id ).toBe( 301 );
		} );
	} );

	describe( 'multi-attribute product with transliterated slugs', () => {
		// This is the exact scenario that broke with normalization:
		// German store with "Größe" (slug: groesse) + "Modell" (slug: modell)
		// with hyphenated term values like "bmc-kaius-01".
		const germanMapping: AttributeSlugToLabel = {
			attribute_pa_groesse: 'Größe',
			attribute_pa_modell: 'Modell',
		};

		const germanProduct = {
			id: 5,
			type: 'variable',
			variations: [
				{
					id: 501,
					attributes: [
						{ name: 'Größe', value: 'xl' },
						{ name: 'Modell', value: 'bmc-kaius-01' },
					],
				},
				{
					id: 502,
					attributes: [
						{ name: 'Größe', value: 'm' },
						{ name: 'Modell', value: 'bmc-urs-urs01' },
					],
				},
			],
		};

		it( 'matches variation with umlaut attribute and hyphenated term values', () => {
			const result = findMatchingVariation(
				germanProduct,
				[
					{ attribute: 'attribute_pa_groesse', value: 'xl' },
					{ attribute: 'attribute_pa_modell', value: 'bmc-kaius-01' },
				],
				germanMapping
			);
			expect( result?.id ).toBe( 501 );
		} );

		it( 'matches second variation correctly', () => {
			const result = findMatchingVariation(
				germanProduct,
				[
					{ attribute: 'attribute_pa_groesse', value: 'm' },
					{
						attribute: 'attribute_pa_modell',
						value: 'bmc-urs-urs01',
					},
				],
				germanMapping
			);
			expect( result?.id ).toBe( 502 );
		} );

		it( 'returns null when values do not match any variation', () => {
			const result = findMatchingVariation(
				germanProduct,
				[
					{ attribute: 'attribute_pa_groesse', value: 'xl' },
					{
						attribute: 'attribute_pa_modell',
						value: 'bmc-urs-urs01',
					},
				],
				germanMapping
			);
			expect( result ).toBeNull();
		} );
	} );

	describe( 'attribute with accented characters', () => {
		const accentMapping: AttributeSlugToLabel = {
			attribute_pa_cafe_style: 'Café Style',
		};

		const accentProduct = {
			id: 6,
			type: 'variable',
			variations: [
				{
					id: 601,
					attributes: [ { name: 'Café Style', value: 'latte' } ],
				},
				{
					id: 602,
					attributes: [ { name: 'Café Style', value: 'espresso' } ],
				},
			],
		};

		it( 'matches when slug was transliterated from accented label', () => {
			const result = findMatchingVariation(
				accentProduct,
				[
					{
						attribute: 'attribute_pa_cafe_style',
						value: 'espresso',
					},
				],
				accentMapping
			);
			expect( result?.id ).toBe( 602 );
		} );
	} );

	describe( 'attribute with manually customized slug', () => {
		// Admin can set slug to anything, e.g. label "Shirt Type" slug "tshirt-type"
		const customSlugMapping: AttributeSlugToLabel = {
			attribute_pa_tshirt_type: 'Shirt Type',
		};

		const customSlugProduct = {
			id: 7,
			type: 'variable',
			variations: [
				{
					id: 701,
					attributes: [ { name: 'Shirt Type', value: 'polo' } ],
				},
			],
		};

		it( 'matches when slug does not resemble the label at all', () => {
			const result = findMatchingVariation(
				customSlugProduct,
				[
					{
						attribute: 'attribute_pa_tshirt_type',
						value: 'polo',
					},
				],
				customSlugMapping
			);
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
			const result = findMatchingVariation(
				productWithAny,
				[
					{ attribute: 'attribute_pa_color', value: 'Red' },
					{ attribute: 'attribute_pa_size', value: 'Small' },
				],
				slugToLabel
			);
			expect( result?.id ).toBe( 201 );
		} );

		it( 'does not match "Any" attribute when selected value is null', () => {
			expect(
				findMatchingVariation(
					productWithAny,
					[
						{ attribute: 'attribute_pa_color', value: null },
						{ attribute: 'attribute_pa_size', value: 'Small' },
					],
					slugToLabel
				)
			).toBeNull();
		} );

		it( 'does not match "Any" attribute when attribute is not selected', () => {
			expect(
				findMatchingVariation(
					productWithAny,
					[ { attribute: 'attribute_pa_size', value: 'Small' } ],
					slugToLabel
				)
			).toBeNull();
		} );
	} );
} );
