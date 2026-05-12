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

	it( 'maps the base view query params', () => {
		expect( buildProductListQuery( baseView ) ).toEqual(
			expect.objectContaining( {
				per_page: 25,
				page: 3,
				order: 'asc',
				orderby: 'title',
				_embed: 1,
				search_name_or_sku: 'hoodie',
			} )
		);
	} );

	it( 'maps supported filters to the v4 product query', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'product_status',
					operator: 'is',
					value: 'draft',
				},
				{
					field: 'type',
					operator: 'isAny',
					value: [ 'simple', 'variable' ],
				},
				{
					field: 'categories',
					operator: 'isAny',
					value: [ '12', 13 ],
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
				status: 'draft',
				include_types: [ 'simple', 'variable' ],
				category: '12,13',
				stock_status: 'outofstock',
				min_price: '10',
				max_price: '25',
			} )
		);
	} );

	it( 'maps exclusion filters for supported types and categories', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'type',
					operator: 'isNone',
					value: [ 'grouped' ],
				},
				{
					field: 'categories',
					operator: 'isNone',
					value: [ '9', 11 ],
				},
			],
		} as View );

		expect( query.exclude_types ).toEqual( [ 'grouped' ] );
		expect( query.exclude_category ).toEqual( [ 9, 11 ] );
	} );

	it( 'maps an exact price filter to both min and max price', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'price',
					operator: 'is',
					value: '15',
				},
			],
		} as View );

		expect( query.min_price ).toBe( '15' );
		expect( query.max_price ).toBe( '15' );
	} );

	it( 'maps one-sided price filters', () => {
		const minimumOnlyQuery = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'price',
					operator: 'greaterThanOrEqual',
					value: '15',
				},
			],
		} as View );

		const maximumOnlyQuery = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'price',
					operator: 'lessThanOrEqual',
					value: 25,
				},
			],
		} as View );

		expect( minimumOnlyQuery.min_price ).toBe( '15' );
		expect( minimumOnlyQuery.max_price ).toBeUndefined();
		expect( maximumOnlyQuery.min_price ).toBeUndefined();
		expect( maximumOnlyQuery.max_price ).toBe( '25' );
	} );

	it( 'maps stock filters from a selected stock status', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'stock',
					operator: 'is',
					value: 'onbackorder',
				},
			],
		} as View );

		expect( query.stock_status ).toBe( 'onbackorder' );
	} );

	it( 'maps the stock_quantity greaterThanOrEqual filter to min_stock_quantity', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'stock_quantity',
					operator: 'greaterThanOrEqual',
					value: 5,
				},
			],
		} as View );

		expect( query.min_stock_quantity ).toEqual( '5' );
		expect( query.max_stock_quantity ).toBeUndefined();
	} );

	it( 'maps the stock_quantity lessThanOrEqual filter to max_stock_quantity', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'stock_quantity',
					operator: 'lessThanOrEqual',
					value: 20,
				},
			],
		} as View );

		expect( query.max_stock_quantity ).toEqual( '20' );
		expect( query.min_stock_quantity ).toBeUndefined();
	} );

	it( 'maps the stock_quantity between filter to both min and max', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'stock_quantity',
					operator: 'between',
					value: [ 5, 20 ],
				},
			],
		} as View );

		expect( query.min_stock_quantity ).toEqual( '5' );
		expect( query.max_stock_quantity ).toEqual( '20' );
	} );
} );
