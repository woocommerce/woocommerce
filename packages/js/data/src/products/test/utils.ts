/**
 * Internal dependencies
 */
import { ProductQuery } from '../types';
import { getTotalProductCountResourceName } from '../utils';

describe( 'getTotalProductCountResourceName()', () => {
	it( "Ignores query params that don't affect total counts", () => {
		const fullQuery: Partial< ProductQuery > = {
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'title', 'status', 'price' ],
			status: 'publish',
		};

		const slimQuery: Partial< ProductQuery > = {
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
			status: 'publish',
		};

		expect( getTotalProductCountResourceName( fullQuery ) ).toEqual(
			getTotalProductCountResourceName( slimQuery )
		);
	} );

	it( 'Accounts for query params that do affect total counts', () => {
		const firstQuery: Partial< ProductQuery > = {
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'title', 'status', 'price' ],
			status: 'publish',
		};

		const secondQuery: Partial< ProductQuery > = {
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
			status: 'draft',
		};

		expect( getTotalProductCountResourceName( firstQuery ) ).not.toEqual(
			getTotalProductCountResourceName( secondQuery )
		);
	} );
} );
