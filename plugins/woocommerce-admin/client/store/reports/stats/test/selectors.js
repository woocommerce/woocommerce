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

const { getReportStats, isReportStatsRequesting, isReportStatsError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const endpointName = 'revenue';

describe( 'getReportStats()', () => {
	it( 'returns null when no report data is available', () => {
		const state = deepFreeze( {} );
		expect( getReportStats( state, endpointName ) ).toEqual( null );
	} );
	it( 'returns stored report information by endpoint and query combination', () => {
		const report = {
			totals: {
				orders_count: 10,
				num_items_sold: 9,
			},
			interval: [ 0, 1, 2 ],
		};
		const state = deepFreeze( {
			reports: {
				stats: {
					revenue: {
						'{}': { ...report },
					},
				},
			},
		} );
		expect( getReportStats( state, endpointName ) ).toEqual( report );
	} );
} );

describe( 'isReportStatsRequesting()', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getReportStats'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isReportStatsRequesting( endpointName );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isReportStatsRequesting( endpointName );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isReportStatsRequesting( endpointName );
		expect( result ).toBe( true );
	} );
} );

describe( 'isReportStatsError()', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isReportStatsError( state, endpointName ) ).toEqual( false );
	} );
	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			reports: {
				stats: {
					revenue: {
						'{}': ERROR,
					},
				},
			},
		} );
		expect( isReportStatsError( state, endpointName ) ).toEqual( true );
	} );
} );
