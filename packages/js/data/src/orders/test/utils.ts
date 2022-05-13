/**
 * Internal dependencies
 */
import { OrderQuery } from '../types';
import { getTotalOrderCountResourceName } from '../utils';

describe( 'getTotalOrderCountResourceName()', () => {
	it( "Ignores query params that don't affect total counts", () => {
		const fullQuery: Partial< OrderQuery > = {
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'status', 'quantity', 'price' ],
			status: 'completed',
		};

		const slimQuery: Partial< OrderQuery > = {
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
			status: 'completed',
		};

		expect( getTotalOrderCountResourceName( fullQuery ) ).toEqual(
			getTotalOrderCountResourceName( slimQuery )
		);
	} );

	it( 'Accounts for query params that do affect total counts', () => {
		const firstQuery: Partial< OrderQuery > = {
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'status', 'quantity', 'price' ],
			status: 'completed',
		};

		const secondQuery: Partial< OrderQuery > = {
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
			status: 'pending',
		};

		expect( getTotalOrderCountResourceName( firstQuery ) ).not.toEqual(
			getTotalOrderCountResourceName( secondQuery )
		);
	} );
} );
