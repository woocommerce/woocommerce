/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import { buildMergedProductEditData, getProductEditFields } from './utils';

describe( 'product edit utils', () => {
	const buildProduct = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		( {
			id: 12,
			name: 'Beanie',
			status: 'draft',
			categories: [],
			tags: [],
			images: [],
			...overrides,
		} as unknown as ProductEntityRecord );

	it( 'returns the original values for a single selected product', () => {
		const product = buildProduct( {
			name: 'Hoodie',
			categories: [ { id: 15, name: 'Clothing' } ],
		} );

		expect( buildMergedProductEditData( [ product ] ) ).toEqual(
			expect.objectContaining( {
				name: 'Hoodie',
				categories: [ { id: 15, name: 'Clothing' } ],
			} )
		);
	} );

	it( 'preserves shared values in a bulk selection', () => {
		const products = [
			buildProduct( {
				id: 1,
				name: 'Beanie',
				status: 'publish',
			} ),
			buildProduct( {
				id: 2,
				name: 'Beanie',
				status: 'publish',
			} ),
		];

		expect( buildMergedProductEditData( products ) ).toEqual(
			expect.objectContaining( {
				name: 'Beanie',
				status: 'publish',
			} )
		);
	} );

	it( 'uses neutral empty values for mixed bulk field values', () => {
		const products = [
			buildProduct( {
				id: 1,
				name: 'Beanie',
				categories: [ { id: 15, name: 'Clothing' } ],
			} ),
			buildProduct( {
				id: 2,
				name: 'Hoodie',
				categories: [ { id: 22, name: 'Accessories' } ],
			} ),
		];

		expect( buildMergedProductEditData( products ) ).toEqual(
			expect.objectContaining( {
				name: '',
				categories: [],
			} )
		);
	} );

	it( 'excludes summary and count fields from the edit field list', () => {
		const editFieldIds = getProductEditFields( [
			{ id: 'name' },
			{ id: 'images_count' },
			{ id: 'price_summary' },
			{ id: 'linked_products_count' },
			{ id: 'sku' },
		] as Field< ProductEntityRecord >[] ).map( ( field ) => field.id );

		expect( editFieldIds ).not.toEqual(
			expect.arrayContaining( [
				'images_count',
				'price_summary',
				'inventory_summary',
				'organization_summary',
				'visibility_summary',
				'downloadable_count',
				'shipping_summary',
				'linked_products_count',
			] )
		);
	} );
} );
