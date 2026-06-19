/**
 * Internal dependencies
 */
import {
	buildReportsFeesQueryFromView,
	serializeReportsBalanceQuery,
	serializeReportsFeesExportQuery,
	serializeReportsFeesListQuery,
	serializeReportsFeesSummaryQuery,
} from '../reports/query';

describe( 'WooPayments Reports query helpers', () => {
	const timezonePattern = /^[+-]\d{2}:\d{2}$/;

	it( 'serializes Balance currency in lowercase', () => {
		expect(
			serializeReportsBalanceQuery( {
				date_start: '2026-06-01T00:00:00Z',
				date_end: '2026-06-19T23:59:59Z',
				currency: 'USD',
			} )
		).toBe(
			'date_start=2026-06-01T00%3A00%3A00Z&date_end=2026-06-19T23%3A59%3A59Z&currency=usd'
		);
	} );

	it( 'maps DataViews state to default Fees REST query params', () => {
		expect( buildReportsFeesQueryFromView( { type: 'table' } ) ).toEqual( {
			page: 1,
			per_page: 25,
			sort: 'date',
			direction: 'desc',
			user_timezone: expect.stringMatching( timezonePattern ),
		} );
	} );

	it( 'maps DataViews pagination, sorting, search, and filters to Fees query params', () => {
		expect(
			buildReportsFeesQueryFromView( {
				type: 'table',
				page: 3,
				perPage: 10,
				sort: {
					field: 'payment_method',
					direction: 'asc',
				},
				search: 'txn_123',
				filters: [
					{
						field: 'date',
						operator: 'is',
						value: [ '2026-06-01', '2026-06-19' ],
					},
					{
						field: 'payment_method',
						operator: 'is',
						value: 'card',
					},
					{
						field: 'type',
						operator: 'isAny',
						value: [ 'charge', 'refund' ],
					},
				],
			} )
		).toEqual( {
			page: 3,
			per_page: 10,
			sort: 'source',
			direction: 'asc',
			date_between: [ '2026-06-01', '2026-06-19' ],
			payment_method_type: 'card',
			type: [ 'charge', 'refund' ],
			search: [ 'txn_123' ],
			user_timezone: expect.stringMatching( timezonePattern ),
		} );
	} );

	it( 'normalizes a scalar type filter to the Reports array schema', () => {
		expect(
			buildReportsFeesQueryFromView( {
				type: 'table',
				filters: [
					{
						field: 'type',
						operator: 'is',
						value: 'charge',
					},
				],
			} )
		).toEqual(
			expect.objectContaining( {
				type: [ 'charge' ],
			} )
		);
	} );

	it( 'keeps export-only identity params out of list and summary query strings', () => {
		const query = {
			page: 2,
			per_page: 50,
			sort: 'date',
			direction: 'desc' as const,
			type: [ 'charge' ],
			user_timezone: '+03:00',
			user_email: 'merchant@example.com',
			locale: 'en_US',
		};

		expect( serializeReportsFeesListQuery( query ) ).toBe(
			'page=2&per_page=50&sort=date&direction=desc&type%5B%5D=charge&user_timezone=%2B03%3A00'
		);
		expect( serializeReportsFeesSummaryQuery( query ) ).toBe(
			'type%5B%5D=charge&user_timezone=%2B03%3A00'
		);
		expect( serializeReportsFeesExportQuery( query ) ).toBe(
			'type%5B%5D=charge&user_timezone=%2B03%3A00&user_email=merchant%40example.com&locale=en_US'
		);
	} );
} );
