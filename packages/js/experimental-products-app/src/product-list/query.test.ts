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
				_embed: 1,
				search_name_or_sku: 'hoodie',
			} )
		);
	} );

	it( 'does not map disabled sort fields to query params', () => {
		const nameSortQuery = buildProductListQuery( baseView );
		const priceSortQuery = buildProductListQuery( {
			...baseView,
			sort: {
				field: 'price',
				direction: 'desc',
			},
		} as View );

		expect( nameSortQuery.order ).toBeUndefined();
		expect( nameSortQuery.orderby ).toBeUndefined();
		expect( priceSortQuery.order ).toBeUndefined();
		expect( priceSortQuery.orderby ).toBeUndefined();
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

	it( 'maps stock filters from multiple selected stock statuses', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'stock',
					operator: 'isAny',
					value: [ 'instock', 'onbackorder' ],
				},
			],
		} as View );

		expect( query.stock_status ).toEqual( [ 'instock', 'onbackorder' ] );
	} );

	it( 'ignores empty stock filters until a value is selected', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'stock',
					operator: 'isAny',
					value: [],
				},
			],
		} as View );

		expect( query.stock_status ).toBeUndefined();
	} );

	it( 'ignores empty taxonomy filters until a value is selected', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'brands',
					operator: 'isAny',
					value: undefined,
				},
				{
					field: 'categories',
					operator: 'isAny',
					value: '',
				},
				{
					field: 'tags',
					operator: 'isAny',
					value: [],
				},
			],
		} as View );

		expect( query.brand ).toBeUndefined();
		expect( query.category ).toBeUndefined();
		expect( query.tag ).toBeUndefined();
	} );

	it( 'maps the tags isAny filter to the tag query param', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'tags',
					operator: 'isAny',
					value: [ '5', 7 ],
				},
			],
		} as View );

		expect( query.tag ).toEqual( '5,7' );
	} );

	it( 'maps the tags isNone filter to exclude_tag', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'tags',
					operator: 'isNone',
					value: [ '5', 7 ],
				},
			],
		} as View );

		expect( query.exclude_tag ).toEqual( [ 5, 7 ] );
	} );

	it( 'maps the brands isAny filter to the brand query param', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'brands',
					operator: 'isAny',
					value: [ '8', 9 ],
				},
			],
		} as View );

		expect( query.brand ).toEqual( '8,9' );
	} );

	it( 'ignores shipping_class filters', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'shipping_class',
					operator: 'isAny',
					value: [ '3', 4 ],
				},
			],
		} as View );

		expect( query.shipping_class ).toBeUndefined();
	} );

	it( 'maps the stock_quantity is filter to both min and max', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [ { field: 'stock_quantity', operator: 'is', value: 5 } ],
		} as View );

		expect( query.min_stock_quantity ).toEqual( '5' );
		expect( query.max_stock_quantity ).toEqual( '5' );
	} );

	it( 'maps the stock_quantity greaterThan filter to min_stock_quantity + 1', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{ field: 'stock_quantity', operator: 'greaterThan', value: 5 },
			],
		} as View );

		expect( query.min_stock_quantity ).toEqual( '6' );
		expect( query.max_stock_quantity ).toBeUndefined();
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

	it( 'maps the stock_quantity lessThan filter to max_stock_quantity - 1', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{ field: 'stock_quantity', operator: 'lessThan', value: 20 },
			],
		} as View );

		expect( query.max_stock_quantity ).toEqual( '19' );
		expect( query.min_stock_quantity ).toBeUndefined();
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

	it( 'leaves stock_quantity bounds unset for the isNot operator (no server-side support)', () => {
		const query = buildProductListQuery( {
			...baseView,
			filters: [
				{
					field: 'stock_quantity',
					operator: 'isNot',
					value: 5,
				},
			],
		} as View );

		expect( query.min_stock_quantity ).toBeUndefined();
		expect( query.max_stock_quantity ).toBeUndefined();
	} );
} );
