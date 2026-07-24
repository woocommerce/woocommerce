/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { resolveProduct } from '../product-resolution';

describe( 'resolveProduct', () => {
	it( 'returns null when the id names no product or variation', () => {
		const result = resolveProduct( {}, {}, { id: 999 } );

		expect( result ).toBeNull();
	} );

	it( 'returns the product itself for a simple product', () => {
		const simpleProduct = {
			id: 1,
			type: 'simple',
		} as ProductResponseItem;

		const result = resolveProduct( { 1: simpleProduct }, {}, { id: 1 } );

		expect( result ).toBe( simpleProduct );
	} );

	it( 'returns the matched variation when selectedAttributes match and the variation is populated', () => {
		const variableProduct = {
			id: 1,
			type: 'variable',
			variations: [
				{
					id: 10,
					attributes: [ { name: 'Color', value: 'red' } ],
				},
			],
		} as unknown as ProductResponseItem;
		const populatedVariation = {
			id: 10,
			name: 'Red Variation',
		} as ProductResponseItem;

		const result = resolveProduct(
			{ 1: variableProduct },
			{ 10: populatedVariation },
			{
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			}
		);

		expect( result ).toBe( populatedVariation );
	} );

	it( 'returns null when attributes match but the matched variation is not populated', () => {
		const variableProduct = {
			id: 1,
			type: 'variable',
			variations: [
				{
					id: 10,
					attributes: [ { name: 'Color', value: 'red' } ],
				},
			],
		} as unknown as ProductResponseItem;

		const result = resolveProduct(
			{ 1: variableProduct },
			{}, // variations intentionally empty.
			{
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			}
		);

		expect( result ).toBeNull();
	} );

	it( 'returns the parent product unchanged when it is variable and no attributes are selected', () => {
		const variableProduct = {
			id: 1,
			type: 'variable',
			variations: [
				{
					id: 10,
					attributes: [ { name: 'Color', value: 'red' } ],
				},
			],
		} as unknown as ProductResponseItem;

		expect( resolveProduct( { 1: variableProduct }, {}, { id: 1 } ) ).toBe(
			variableProduct
		);
		expect(
			resolveProduct(
				{ 1: variableProduct },
				{},
				{ id: 1, selectedAttributes: [] }
			)
		).toBe( variableProduct );
	} );

	it( 'returns null when selected attributes match no variation', () => {
		const variableProduct = {
			id: 1,
			type: 'variable',
			variations: [
				{
					id: 10,
					attributes: [ { name: 'Color', value: 'red' } ],
				},
			],
		} as unknown as ProductResponseItem;

		const result = resolveProduct(
			{ 1: variableProduct },
			{ 10: { id: 10 } as ProductResponseItem },
			{
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			}
		);

		expect( result ).toBeNull();
	} );

	it( 'returns the variation directly when id names a variation', () => {
		const variation = {
			id: 50,
			name: 'Direct Variation',
		} as ProductResponseItem;

		const result = resolveProduct( {}, { 50: variation }, { id: 50 } );

		expect( result ).toBe( variation );
	} );

	it( 'returns the variation directly and ignores selectedAttributes when id names a variation', () => {
		const variation = {
			id: 50,
			name: 'Direct Variation',
		} as ProductResponseItem;

		const result = resolveProduct(
			{},
			{ 50: variation },
			{
				id: 50,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			}
		);

		expect( result ).toBe( variation );
	} );

	it( 'prefers the variation map over the product map when an id exists in both', () => {
		const product = {
			id: 50,
			type: 'simple',
			name: 'Product 50',
		} as ProductResponseItem;
		const variation = {
			id: 50,
			name: 'Variation 50',
		} as ProductResponseItem;

		const result = resolveProduct(
			{ 50: product },
			{ 50: variation },
			{ id: 50 }
		);

		expect( result ).toBe( variation );
	} );

	describe( 'attribute matching (variable products)', () => {
		it( 'matches when selected attributes carry the attribute_/attribute_pa_ prefix', () => {
			const variableProduct = {
				id: 3,
				type: 'variable',
				variations: [
					{
						id: 301,
						attributes: [
							{ name: 'Color', value: 'Blue' },
							{ name: 'Size', value: 'Small' },
						],
					},
					{
						id: 302,
						attributes: [
							{ name: 'Color', value: 'Blue' },
							{ name: 'Size', value: 'Large' },
						],
					},
				],
			} as unknown as ProductResponseItem;
			const populatedVariation301 = {
				id: 301,
				name: 'Blue Small',
			} as ProductResponseItem;
			const populatedVariation302 = {
				id: 302,
				name: 'Blue Large',
			} as ProductResponseItem;

			const result = resolveProduct(
				{ 3: variableProduct },
				{ 301: populatedVariation301, 302: populatedVariation302 },
				{
					id: 3,
					selectedAttributes: [
						{ attribute: 'attribute_pa_color', value: 'Blue' },
						{ attribute: 'attribute_pa_size', value: 'Small' },
					],
				}
			);

			expect( result ).toBe( populatedVariation301 );
		} );

		it( 'matches when selected attributes use hyphenated multi-word slugs', () => {
			const variableProduct = {
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
				],
			} as unknown as ProductResponseItem;
			const populatedVariation = {
				id: 301,
				name: 'Blue 42',
			} as ProductResponseItem;

			const result = resolveProduct(
				{ 3: variableProduct },
				{ 301: populatedVariation },
				{
					id: 3,
					selectedAttributes: [
						{ attribute: 'attribute_pa_color', value: 'Blue' },
						{ attribute: 'attribute_pa_numeric-size', value: '42' },
					],
				}
			);

			expect( result ).toBe( populatedVariation );
		} );

		describe( '"Any" attribute handling', () => {
			it( 'matches a variation with an "Any" attribute when a value is selected for it', () => {
				const variableProduct = {
					id: 2,
					type: 'variable',
					variations: [
						{
							id: 201,
							attributes: [
								{ name: 'Color', value: null },
								{ name: 'Size', value: 'Small' },
							],
						},
					],
				} as unknown as ProductResponseItem;
				const populatedVariation = {
					id: 201,
					name: 'Any Color Small',
				} as ProductResponseItem;

				const result = resolveProduct(
					{ 2: variableProduct },
					{ 201: populatedVariation },
					{
						id: 2,
						selectedAttributes: [
							{ attribute: 'Color', value: 'Red' },
							{ attribute: 'Size', value: 'Small' },
						],
					}
				);

				expect( result ).toBe( populatedVariation );
			} );

			it( 'does not match an "Any" attribute when the selected value is null', () => {
				const variableProduct = {
					id: 2,
					type: 'variable',
					variations: [
						{
							id: 201,
							attributes: [
								{ name: 'Color', value: null },
								{ name: 'Size', value: 'Small' },
							],
						},
					],
				} as unknown as ProductResponseItem;

				const result = resolveProduct(
					{ 2: variableProduct },
					{},
					{
						id: 2,
						selectedAttributes: [
							{
								attribute: 'Color',
								value: null as unknown as string,
							},
							{ attribute: 'Size', value: 'Small' },
						],
					}
				);

				expect( result ).toBeNull();
			} );

			it( 'does not match an "Any" attribute when it is not selected at all', () => {
				const variableProduct = {
					id: 2,
					type: 'variable',
					variations: [
						{
							id: 201,
							attributes: [
								{ name: 'Color', value: null },
								{ name: 'Size', value: 'Small' },
							],
						},
					],
				} as unknown as ProductResponseItem;

				const result = resolveProduct(
					{ 2: variableProduct },
					{},
					{
						id: 2,
						selectedAttributes: [
							{ attribute: 'Size', value: 'Small' },
						],
					}
				);

				expect( result ).toBeNull();
			} );
		} );
	} );
} );
