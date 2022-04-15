/**
 * Internal dependencies
 */
import { getTotalCountResourceName } from '../utils';

describe( 'getTotalCountResourceName()', () => {
	it( "Ignores query params that don't affect total counts", () => {
		const fullQuery = {
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'title', 'status', 'image', 'quantity', 'price' ],
			status: 'publish',
		};

		const slimQuery = {
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
			status: 'publish',
		};

		expect( getTotalCountResourceName( 'test', fullQuery ) ).toEqual(
			getTotalCountResourceName( 'test', slimQuery )
		);
	} );

	it( 'Accounts for query params that do affect total counts', () => {
		const firstQuery = {
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'title', 'status', 'image', 'quantity', 'price' ],
			status: 'publish',
		};

		const secondQuery = {
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
			status: 'draft',
		};

		expect( getTotalCountResourceName( 'test', firstQuery ) ).not.toEqual(
			getTotalCountResourceName( 'test', secondQuery )
		);
	} );
} );
