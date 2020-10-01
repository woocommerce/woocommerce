/**
 * Internal dependencies
 */
import {
	getAttributes,
	getVariationAttributes,
	getVariationsMatchingSelectedAttributes,
	getVariationMatchingSelectedAttributes,
	getActiveSelectControlOptions,
} from '../utils';

const rawAttributeData = [
	{
		id: 1,
		name: 'Color',
		taxonomy: 'pa_color',
		has_variations: true,
		terms: [
			{
				id: 22,
				name: 'Blue',
				slug: 'blue',
			},
			{
				id: 23,
				name: 'Green',
				slug: 'green',
			},
			{
				id: 24,
				name: 'Red',
				slug: 'red',
			},
		],
	},
	{
		id: 0,
		name: 'Logo',
		taxonomy: null,
		has_variations: true,
		terms: [
			{
				id: 0,
				name: 'Yes',
				slug: 'Yes',
			},
			{
				id: 0,
				name: 'No',
				slug: 'No',
			},
		],
	},
	{
		id: 0,
		name: 'Non-variable attribute',
		taxonomy: null,
		has_variations: false,
		terms: [
			{
				id: 0,
				name: 'Test',
				slug: 'Test',
			},
			{
				id: 0,
				name: 'Test 2',
				slug: 'Test 2',
			},
		],
	},
];

const rawVariations = [
	{
		id: 35,
		attributes: [
			{
				name: 'Color',
				value: 'blue',
			},
			{
				name: 'Logo',
				value: 'Yes',
			},
		],
	},
	{
		id: 28,
		attributes: [
			{
				name: 'Color',
				value: 'red',
			},
			{
				name: 'Logo',
				value: 'No',
			},
		],
	},
	{
		id: 29,
		attributes: [
			{
				name: 'Color',
				value: 'green',
			},
			{
				name: 'Logo',
				value: 'No',
			},
		],
	},
	{
		id: 30,
		attributes: [
			{
				name: 'Color',
				value: 'blue',
			},
			{
				name: 'Logo',
				value: 'No',
			},
		],
	},
];

describe( 'Testing utils', () => {
	describe( 'Testing getAttributes()', () => {
		it( 'returns empty object if there are no attributes', () => {
			const attributes = getAttributes( null );
			expect( attributes ).toStrictEqual( {} );
		} );
		it( 'returns list of attributes when given valid data', () => {
			const attributes = getAttributes( rawAttributeData );
			expect( attributes ).toStrictEqual( {
				Color: {
					id: 1,
					name: 'Color',
					taxonomy: 'pa_color',
					has_variations: true,
					terms: [
						{
							id: 22,
							name: 'Blue',
							slug: 'blue',
						},
						{
							id: 23,
							name: 'Green',
							slug: 'green',
						},
						{
							id: 24,
							name: 'Red',
							slug: 'red',
						},
					],
				},
				Logo: {
					id: 0,
					name: 'Logo',
					taxonomy: null,
					has_variations: true,
					terms: [
						{
							id: 0,
							name: 'Yes',
							slug: 'Yes',
						},
						{
							id: 0,
							name: 'No',
							slug: 'No',
						},
					],
				},
			} );
		} );
	} );
	describe( 'Testing getVariationAttributes()', () => {
		it( 'returns empty object if there are no variations', () => {
			const variationAttributes = getVariationAttributes( null );
			expect( variationAttributes ).toStrictEqual( {} );
		} );
		it( 'returns list of attribute names and value pairs when given valid data', () => {
			const variationAttributes = getVariationAttributes( rawVariations );
			expect( variationAttributes ).toStrictEqual( {
				'id:35': {
					id: 35,
					attributes: {
						Color: 'blue',
						Logo: 'Yes',
					},
				},
				'id:28': {
					id: 28,
					attributes: {
						Color: 'red',
						Logo: 'No',
					},
				},
				'id:29': {
					id: 29,
					attributes: {
						Color: 'green',
						Logo: 'No',
					},
				},
				'id:30': {
					id: 30,
					attributes: {
						Color: 'blue',
						Logo: 'No',
					},
				},
			} );
		} );
	} );
	describe( 'Testing getVariationsMatchingSelectedAttributes()', () => {
		const attributes = getAttributes( rawAttributeData );
		const variationAttributes = getVariationAttributes( rawVariations );

		it( 'returns all variations, in the correct order, if no selections have been made yet', () => {
			const selectedAttributes = {};
			const matches = getVariationsMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( [ 35, 28, 29, 30 ] );
		} );

		it( 'returns correct subset of variations after a selection', () => {
			const selectedAttributes = {
				Color: 'blue',
			};
			const matches = getVariationsMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( [ 35, 30 ] );
		} );

		it( 'returns correct subset of variations after all selections', () => {
			const selectedAttributes = {
				Color: 'blue',
				Logo: 'No',
			};
			const matches = getVariationsMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( [ 30 ] );
		} );

		it( 'returns no results if selection does not match or is invalid', () => {
			const selectedAttributes = {
				Color: 'brown',
			};
			const matches = getVariationsMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( [] );
		} );
	} );
	describe( 'Testing getVariationMatchingSelectedAttributes()', () => {
		const attributes = getAttributes( rawAttributeData );
		const variationAttributes = getVariationAttributes( rawVariations );

		it( 'returns first match if no selections have been made yet', () => {
			const selectedAttributes = {};
			const matches = getVariationMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( 35 );
		} );

		it( 'returns first match after single selection', () => {
			const selectedAttributes = {
				Color: 'blue',
			};
			const matches = getVariationMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( 35 );
		} );

		it( 'returns correct match after all selections', () => {
			const selectedAttributes = {
				Color: 'blue',
				Logo: 'No',
			};
			const matches = getVariationMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( 30 );
		} );

		it( 'returns no match if invalid', () => {
			const selectedAttributes = {
				Color: 'brown',
			};
			const matches = getVariationMatchingSelectedAttributes(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( matches ).toStrictEqual( 0 );
		} );
	} );
	describe( 'Testing getActiveSelectControlOptions()', () => {
		const attributes = getAttributes( rawAttributeData );
		const variationAttributes = getVariationAttributes( rawVariations );

		it( 'returns all possible options if no selections have been made yet', () => {
			const selectedAttributes = {};
			const controlOptions = getActiveSelectControlOptions(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( controlOptions ).toStrictEqual( {
				Color: [
					{
						value: 'blue',
						label: 'Blue',
					},
					{
						value: 'green',
						label: 'Green',
					},
					{
						value: 'red',
						label: 'Red',
					},
				],
				Logo: [
					{
						value: 'Yes',
						label: 'Yes',
					},
					{
						value: 'No',
						label: 'No',
					},
				],
			} );
		} );

		it( 'returns only valid options if color is selected', () => {
			const selectedAttributes = {
				Color: 'green',
			};
			const controlOptions = getActiveSelectControlOptions(
				attributes,
				variationAttributes,
				selectedAttributes
			);
			expect( controlOptions ).toStrictEqual( {
				Color: [
					{
						value: 'blue',
						label: 'Blue',
					},
					{
						value: 'green',
						label: 'Green',
					},
					{
						value: 'red',
						label: 'Red',
					},
				],
				Logo: [
					{
						value: 'No',
						label: 'No',
					},
				],
			} );
		} );
	} );
} );
