/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { formatParams, getStatus } from '../utils';

describe( 'formatParams', () => {
	it( 'returns empty object when skipChecked is false and period is all', () => {
		expect( formatParams( 'YYYY-MM-DD', { label: 'all' }, false ) ).toEqual(
			{}
		);
	} );

	it( 'returns skip_existing param', () => {
		expect( formatParams( 'YYYY-MM-DD', { label: 'all' }, true ) ).toEqual(
			{
				skip_existing: true,
			}
		);
	} );

	it( 'returns correct days param based on period label', () => {
		expect( formatParams( 'YYYY-MM-DD', { label: '30' }, false ) ).toEqual(
			{ days: 30 }
		);
	} );

	it( 'returns correct days param based on period date', () => {
		const date = '2018-01-01';
		const days = Math.floor(
			moment().diff( moment( date, 'YYYY-MM-DD' ), 'days', true )
		);
		expect(
			formatParams( 'YYYY-MM-DD', { label: 'custom', date }, false )
		).toEqual( { days } );
	} );

	it( 'returns both params', () => {
		expect( formatParams( 'YYYY-MM-DD', { label: '30' }, true ) ).toEqual( {
			skip_existing: true,
			days: 30,
		} );
	} );
} );

describe( 'getStatus', () => {
	it( 'returns `initializing` when no progress numbers are defined', () => {
		expect(
			getStatus( {
				customersTotal: 1,
				inProgress: true,
				ordersTotal: 1,
			} )
		).toEqual( 'initializing' );
	} );
	it( 'returns `initializing` when the process is "inProgress" and the cache is not clear', () => {
		expect(
			getStatus( {
				cacheNeedsClearing: true,
				customersProgress: 1,
				customersTotal: 1,
				inProgress: true,
				ordersProgress: 1,
				ordersTotal: 1,
			} )
		).toEqual( 'initializing' );
	} );

	it( 'returns `customers` when importing customers', () => {
		expect(
			getStatus( {
				customersProgress: 0,
				customersTotal: 1,
				inProgress: true,
				ordersProgress: 0,
				ordersTotal: 1,
			} )
		).toEqual( 'customers' );
	} );

	it( 'returns `orders` when importing orders', () => {
		expect(
			getStatus( {
				customersProgress: 1,
				customersTotal: 1,
				inProgress: true,
				ordersProgress: 0,
				ordersTotal: 1,
			} )
		).toEqual( 'orders' );
	} );

	it( 'returns `finalizing` when customers and orders are already imported', () => {
		expect(
			getStatus( {
				customersProgress: 1,
				customersTotal: 1,
				inProgress: true,
				ordersProgress: 1,
				ordersTotal: 1,
			} )
		).toEqual( 'finalizing' );
	} );

	it( 'returns `finished` when customers and orders are already imported and inProgress is false', () => {
		expect(
			getStatus( {
				customersProgress: 1,
				customersTotal: 1,
				inProgress: false,
				ordersProgress: 1,
				ordersTotal: 1,
			} )
		).toEqual( 'finished' );
	} );

	it( 'returns `ready` when there are customers or orders to import', () => {
		expect(
			getStatus( {
				customersProgress: 0,
				customersTotal: 1,
				inProgress: false,
				ordersProgress: 0,
				ordersTotal: 1,
			} )
		).toEqual( 'ready' );
	} );

	it( 'returns `nothing` when there are no customers or orders to import', () => {
		expect(
			getStatus( {
				customersProgress: 0,
				customersTotal: 0,
				inProgress: false,
				ordersProgress: 0,
				ordersTotal: 0,
			} )
		).toEqual( 'nothing' );
	} );
} );
