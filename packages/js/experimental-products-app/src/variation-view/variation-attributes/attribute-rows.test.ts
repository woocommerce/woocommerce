/**
 * Internal dependencies
 */
import {
	getProductAttributeRows,
	getVariationAttributeRows,
} from './attribute-rows';
import type { ProductEntityRecord } from '../../fields/types';

function getProduct(
	overrides: Partial< ProductEntityRecord >
): ProductEntityRecord {
	return {
		attributes: [],
		default_attributes: [],
		...overrides,
	} as ProductEntityRecord;
}

describe( 'getVariationAttributeRows', () => {
	it( 'returns only attributes used for variations in product order', () => {
		const rows = getVariationAttributeRows(
			getProduct( {
				attributes: [
					{
						id: 0,
						name: 'Pattern',
						slug: 'pattern',
						options: [ 'Dots' ],
						position: 3,
						variation: false,
						visible: true,
					},
					{
						id: 2,
						name: 'Color',
						slug: 'pa_color',
						options: [ 'Yellow', 'Blue' ],
						position: 2,
						variation: true,
						visible: true,
					},
					{
						id: 1,
						name: 'Theme',
						slug: 'pa_theme',
						options: [ 'Unicorn', 'Pirate' ],
						position: 1,
						variation: true,
						visible: true,
					},
				],
			} )
		);

		expect( rows ).toEqual( [
			expect.objectContaining( {
				attributeId: 1,
				id: 'pa_theme',
				isGlobal: true,
				name: 'Theme',
				slug: 'pa_theme',
				values: [ 'Unicorn', 'Pirate' ],
			} ),
			expect.objectContaining( {
				attributeId: 2,
				id: 'pa_color',
				isGlobal: true,
				name: 'Color',
				slug: 'pa_color',
				values: [ 'Yellow', 'Blue' ],
			} ),
		] );
	} );

	it( 'maps default values by attribute ID, name, and slug', () => {
		const rows = getVariationAttributeRows(
			getProduct( {
				attributes: [
					{
						id: 10,
						name: 'Theme',
						slug: 'pa_theme',
						options: [ 'Unicorn' ],
						position: 1,
						variation: true,
						visible: true,
					},
					{
						id: 0,
						name: 'Material',
						slug: 'material',
						options: [ 'Cotton' ],
						position: 2,
						variation: true,
						visible: true,
					},
					{
						id: 11,
						name: 'Color',
						slug: 'pa_color',
						options: [ 'Yellow' ],
						position: 3,
						variation: true,
						visible: true,
					},
				],
				default_attributes: [
					{ id: 10, name: 'Theme', option: 'Unicorn' },
					{ id: 0, name: 'Material', option: 'Cotton' },
					{ id: 0, name: 'pa_color', option: 'Yellow' },
				],
			} )
		);

		expect( rows.map( ( row ) => row.defaultValue ) ).toEqual( [
			'Unicorn',
			'Cotton',
			'Yellow',
		] );
	} );

	it( 'marks custom attributes as local', () => {
		const rows = getVariationAttributeRows(
			getProduct( {
				attributes: [
					{
						id: 0,
						name: 'Material',
						slug: 'material',
						options: [ 'Cotton' ],
						position: 1,
						variation: true,
						visible: true,
					},
				],
			} )
		);

		expect( rows[ 0 ] ).toEqual(
			expect.objectContaining( {
				attributeId: 0,
				id: 'material',
				isGlobal: false,
				name: 'Material',
				slug: 'material',
			} )
		);
	} );
} );

describe( 'getProductAttributeRows', () => {
	it( 'returns only attributes not used for variations in product order', () => {
		const rows = getProductAttributeRows(
			getProduct( {
				attributes: [
					{
						id: 2,
						name: 'Color',
						slug: 'pa_color',
						options: [ 'Yellow', 'Blue' ],
						position: 2,
						variation: true,
						visible: true,
					},
					{
						id: 0,
						name: 'Pattern',
						slug: 'pattern',
						options: [ 'Dots' ],
						position: 3,
						variation: false,
						visible: true,
					},
					{
						id: 1,
						name: 'Material',
						slug: 'pa_material',
						options: [ 'Cotton' ],
						position: 1,
						variation: false,
						visible: false,
					},
				],
			} )
		);

		expect( rows ).toEqual( [
			expect.objectContaining( {
				attributeId: 1,
				id: 'pa_material',
				isGlobal: true,
				isVisible: false,
				name: 'Material',
				slug: 'pa_material',
				values: [ 'Cotton' ],
			} ),
			expect.objectContaining( {
				attributeId: 0,
				id: 'pattern',
				isGlobal: false,
				isVisible: true,
				name: 'Pattern',
				slug: 'pattern',
				values: [ 'Dots' ],
			} ),
		] );
	} );
} );
