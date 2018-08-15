/*
* @format
*/

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import selectors from '../selectors';
import { select } from '@wordpress/data';

const {
	getReportRevenueStats,
	isReportRevenueStatsRequesting,
	isReportRevenueStatsError,
} = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

describe( 'getReportRevenueStats()', () => {
	it( 'returns null when no report data is available', () => {
		const state = deepFreeze( {} );
		expect( getReportRevenueStats( state ) ).toEqual( null );
	} );
	it( 'returns stored report information', () => {
		const report = {
			totals: {
				orders_count: 10,
				num_items_sold: 9,
			},
			interval: [ 0, 1, 2 ],
		};
		const state = deepFreeze( {
			reports: {
				revenue: {
					stats: {
						queries: {
							'{}': { ...report },
						},
					},
				},
			},
		} );
		expect( getReportRevenueStats( state ) ).toEqual( report );
	} );
} );

describe( 'isReportRevenueStatsRequesting()', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getReportRevenueStats'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isReportRevenueStatsRequesting();
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isReportRevenueStatsRequesting();
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isReportRevenueStatsRequesting();
		expect( result ).toBe( true );
	} );
} );

describe( 'isReportRevenueStatsError()', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isReportRevenueStatsError( state ) ).toEqual( false );
	} );
	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			reports: {
				revenue: {
					stats: {
						queries: {
							'{}': ERROR,
						},
					},
				},
			},
		} );
		expect( isReportRevenueStatsError( state ) ).toEqual( true );
	} );
} );
