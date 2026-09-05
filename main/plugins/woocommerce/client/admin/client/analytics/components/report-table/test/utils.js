/**
 * Internal dependencies
 */
import { getExportQuery } from '../utils';

describe( 'getExportQuery', () => {
	it( 'preserves all reportQuery fields', () => {
		const reportQuery = { orderby: 'date', order: 'desc', per_page: 25 };

		const result = getExportQuery( reportQuery, {}, [], {} );

		expect( result.orderby ).toBe( 'date' );
		expect( result.order ).toBe( 'desc' );
		expect( result.per_page ).toBe( 25 );
	} );

	it( 'forwards a filter param present in urlQuery but not in reportQuery', () => {
		const reportQuery = { orderby: 'date', order: 'desc' };
		const urlQuery = { currency: 'EUR' };
		const filters = [ { param: 'currency', filters: [] } ];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result.currency ).toBe( 'EUR' );
	} );

	it( 'should not forward a filter param present in urlQuery but not in reportQuery', () => {
		const reportQuery = { orderby: 'date', order: 'desc' };
		const urlQuery = { status: 'completed' };
		const filters = [ { param: 'currency', filters: [] } ];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result.currency ).not.toBeDefined();
		expect( result.status ).not.toBeDefined();
	} );

	it( 'forwards multiple filter params dynamically', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { currency: 'CAD', region: 'NA' };
		const filters = [
			{ param: 'currency', filters: [] },
			{ param: 'region', filters: [] },
		];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result.currency ).toBe( 'CAD' );
		expect( result.region ).toBe( 'NA' );
	} );

	it( 'does not forward urlQuery params not declared in filters', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { currency: 'USD', path: '/analytics/revenue' };
		const filters = [ { param: 'currency', filters: [] } ];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result.currency ).toBe( 'USD' );
		expect( result ).not.toHaveProperty( 'path' );
	} );

	it( 'does not override reportQuery fields with urlQuery values', () => {
		const reportQuery = { orderby: 'net_revenue', order: 'asc' };
		const urlQuery = { orderby: 'date', order: 'desc', currency: 'USD' };
		const filters = [
			{ param: 'orderby', filters: [] },
			{ param: 'currency', filters: [] },
		];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result.orderby ).toBe( 'net_revenue' );
		expect( result.order ).toBe( 'asc' );
		expect( result.currency ).toBe( 'USD' );
	} );

	it( 'forwards params from nested filter sub-params', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { product_id: '42' };
		const filters = [
			{
				param: 'filter',
				filters: [
					{ value: 'single', settings: { param: 'product_id' } },
				],
			},
		];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result.product_id ).toBe( '42' );
	} );

	it( 'forwards params declared in advancedFilters', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { status: 'completed' };
		const advancedFilters = { filters: { status: {} } };

		const result = getExportQuery(
			reportQuery,
			urlQuery,
			[],
			advancedFilters
		);

		expect( result.status ).toBe( 'completed' );
	} );

	it( 'forwards params from subFilters settings', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { products: '42' };
		const filters = [
			{
				param: 'filter',
				filters: [
					{
						value: 'select_product',
						subFilters: [
							{
								settings: { param: 'products' },
							},
						],
					},
				],
			},
		];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result.products ).toBe( '42' );
	} );

	it( 'does not forward empty string urlQuery values', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { currency: '' };
		const filters = [ { param: 'currency', filters: [] } ];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result ).not.toHaveProperty( 'currency' );
	} );

	it( 'does not forward null urlQuery values', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { currency: null };
		const filters = [ { param: 'currency', filters: [] } ];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result ).not.toHaveProperty( 'currency' );
	} );

	it( 'returns a new object and does not mutate reportQuery', () => {
		const reportQuery = { orderby: 'date' };
		const urlQuery = { currency: 'GBP' };
		const filters = [ { param: 'currency', filters: [] } ];

		const result = getExportQuery( reportQuery, urlQuery, filters );

		expect( result ).not.toBe( reportQuery );
		expect( reportQuery ).not.toHaveProperty( 'currency' );
	} );

	it( 'returns an empty object when all inputs are null or undefined', () => {
		expect( () => getExportQuery( null, null, null, null ) ).not.toThrow();

		const result = getExportQuery( null, null, null, null );

		expect( result ).toEqual( {} );
	} );
} );
