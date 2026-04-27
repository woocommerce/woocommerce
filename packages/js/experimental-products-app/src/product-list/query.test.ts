/**
 * External dependencies
 */
import type { View } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { buildProductListQuery } from './query';

describe( 'buildProductListQuery', () => {
	const baseView = {
		type: 'table',
		page: 3,
		perPage: 25,
		search: 'hoodie',
		sort: {
			field: 'name',
			direction: 'asc',
		},
		filters: [],
	} as View;

	it( 'maps supported filters to the v4 product query', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'product_status',
					operator: 'isAny',
					value: [ 'draft', 'publish' ],
				},
				{
					field: 'categories',
					operator: 'isAny',
					value: [ '12', '13' ],
				},
				{
					field: 'stock',
					operator: 'is',
					value: 'outofstock',
				},
				{
					field: 'price',
					operator: 'between',
					value: [ 10, 25 ],
				},
			],
		} as View );

		expect( query ).toEqual(
			expect.objectContaining( {
				per_page: 25,
				page: 3,
				order: 'asc',
				orderby: 'title',
				search_name_or_sku: 'hoodie',
				include_status: [ 'draft', 'publish' ],
				category: '12,13',
				stock_status: 'outofstock',
				min_price: '10',
				max_price: '25',
			} )
		);
	} );

	it( 'maps exclusion filters for statuses and categories', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'status',
					operator: 'isNone',
					value: [ 'trash' ],
				},
				{
					field: 'categories',
					operator: 'isNone',
					value: [ '9' ],
				},
			],
		} as View );

		expect( query.exclude_status ).toEqual( [ 'trash' ] );
		expect( query.exclude_category ).toEqual( [ 9 ] );
	} );

	it( 'maps one-sided price filters', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'price',
					operator: 'greaterThanOrEqual',
					value: '15',
				},
				{
					field: 'type',
					operator: 'isAny',
					value: [ 'simple', 'variable' ],
				},
			],
		} as View );

		expect( query.min_price ).toBe( '15' );
		expect( query.max_price ).toBeUndefined();
		expect( query.include_types ).toEqual( [ 'simple', 'variable' ] );
	} );

	it( 'maps tags, attributes, shipping classes, and stock quantity filters', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'tags',
					operator: 'isAny',
					value: [ '4', '8' ],
				},
				{
					field: 'attributes',
					operator: 'is',
					value: 'pa_color:22',
				},
				{
					field: 'shipping_class',
					operator: 'isAny',
					value: [ '6' ],
				},
				{
					field: 'stock_quantity',
					operator: 'lessThanOrEqual',
					value: '12',
				},
			],
		} as View );

		expect( query.tag ).toBe( '4,8' );
		expect( query.attribute ).toBe( 'pa_color' );
		expect( query.attribute_term ).toBe( '22' );
		expect( query.shipping_class ).toBe( '6' );
		expect( query.max_stock_quantity ).toBe( '12' );
	} );
} );
