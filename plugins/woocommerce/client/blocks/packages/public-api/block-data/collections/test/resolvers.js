/**
 * External dependencies
 */
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getCollection, getCollectionHeader } from '../resolvers';
import { receiveCollection } from '../actions';
import { STORE_KEY as SCHEMA_STORE_KEY } from '../../schema/constants';
import { STORE_KEY } from '../constants';
import { apiFetchWithHeadersControl } from '../../shared-controls';

jest.mock( '@wordpress/data' );

describe( 'getCollection', () => {
	describe( 'yields with expected responses', () => {
		let fulfillment;
		const testArgs = [
			'wc/blocks',
			'products',
			{ foo: 'bar' },
			[ 20, 30 ],
		];
		const rewind = () => ( fulfillment = getCollection( ...testArgs ) );
		test( 'with getRoute call invoked to retrieve route', () => {
			rewind();
			fulfillment.next();
			expect( controls.resolveSelect ).toHaveBeenCalledWith(
				SCHEMA_STORE_KEY,
				'getRoute',
				testArgs[ 0 ],
				testArgs[ 1 ],
				testArgs[ 3 ]
			);
		} );
		test(
			'when no route is retrieved, yields receiveCollection and ' +
				'returns',
			() => {
				const { value } = fulfillment.next();
				const expected = receiveCollection(
					'wc/blocks',
					'products',
					'?foo=bar',
					[ 20, 30 ],
					{
						items: [],
						headers: {
							get: () => undefined,
							has: () => undefined,
						},
					}
				);
				expect( value.type ).toBe( expected.type );
				expect( value.namespace ).toBe( expected.namespace );
				expect( value.resourceName ).toBe( expected.resourceName );
				expect( value.queryString ).toBe( expected.queryString );
				expect( value.ids ).toEqual( expected.ids );
				expect( Object.keys( value.response ) ).toEqual(
					Object.keys( expected.response )
				);
				const { done } = fulfillment.next();
				expect( done ).toBe( true );
			}
		);
		test(
			'when route is retrieved, yields apiFetchWithHeaders control action with ' +
				'expected route',
			() => {
				rewind();
				fulfillment.next();
				const { value } = fulfillment.next( 'https://example.org' );
				expect( value ).toEqual(
					apiFetchWithHeadersControl( {
						path: 'https://example.org?foo=bar',
					} )
				);
			}
		);
		test(
			'when apiFetchWithHeaders does not return a valid response, ' +
				'yields expected action',
			() => {
				const { value } = fulfillment.next( {} );
				expect( value ).toEqual(
					receiveCollection(
						'wc/blocks',
						'products',
						'?foo=bar',
						[ 20, 30 ],
						{ items: [], headers: undefined }
					)
				);
			}
		);
		test(
			'when apiFetch returns a valid response, yields expected ' +
				'action',
			() => {
				rewind();
				fulfillment.next();
				fulfillment.next( 'https://example.org' );
				const { value } = fulfillment.next( {
					response: [ '42', 'cheeseburgers' ],
					headers: { foo: 'bar' },
				} );
				expect( value ).toEqual(
					receiveCollection(
						'wc/blocks',
						'products',
						'?foo=bar',
						[ 20, 30 ],
						{
							items: [ '42', 'cheeseburgers' ],
							headers: { foo: 'bar' },
						}
					)
				);
				const { done } = fulfillment.next();
				expect( done ).toBe( true );
			}
		);
	} );
} );

describe( 'getCollection with a Last-Modified header', () => {
	const testArgs = [ 'wc/store/v1', 'products', { per_page: 1 }, [] ];
	const httpDate = 'Tue, 20 Oct 2026 02:19:18 GMT';
	const timestamp = 1792462758;

	const fetchWithHeader = ( lastModified ) => {
		const headers = {
			get: ( name ) =>
				name === 'last-modified' ? lastModified : undefined,
			has: ( name ) => name === 'last-modified',
		};
		const fulfillment = getCollection( ...testArgs );
		fulfillment.next();
		fulfillment.next( 'https://example.org/wc/store/v1/products' );
		const { value } = fulfillment.next( { response: [], headers } );
		return { value, headers };
	};

	const runInvalidation = ( invalidation, storedLastModified ) => {
		invalidation.next();
		invalidation.next( storedLastModified );
		invalidation.next();
		invalidation.next();
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'stores the time of an HTTP-date header as a Unix timestamp', () => {
		const { value } = fetchWithHeader( httpDate );
		runInvalidation( value, 0 );
		expect( controls.dispatch ).toHaveBeenCalledWith(
			STORE_KEY,
			'receiveLastModified',
			timestamp
		);
	} );

	it( 'invalidates the store when an HTTP-date header is later than the stored time', () => {
		const { value } = fetchWithHeader( httpDate );
		runInvalidation( value, timestamp - 60 );
		expect( controls.dispatch ).toHaveBeenCalledWith(
			STORE_KEY,
			'invalidateResolutionForStore'
		);
		expect( controls.dispatch ).toHaveBeenCalledWith(
			STORE_KEY,
			'receiveLastModified',
			timestamp
		);
	} );

	it( 'does not invalidate the store when the header matches the stored time', () => {
		const { value } = fetchWithHeader( httpDate );
		runInvalidation( value, timestamp );
		expect( controls.dispatch ).not.toHaveBeenCalled();
	} );

	it( 'still accepts a Unix timestamp header', () => {
		const { value } = fetchWithHeader( String( timestamp ) );
		runInvalidation( value, timestamp - 60 );
		expect( controls.dispatch ).toHaveBeenCalledWith(
			STORE_KEY,
			'invalidateResolutionForStore'
		);
	} );

	it( 'ignores a header it cannot read', () => {
		const { value, headers } = fetchWithHeader( 'not a date' );
		const expected = receiveCollection(
			'wc/store/v1',
			'products',
			'?per_page=1',
			[],
			{ items: [], headers }
		);
		expect( value.type ).toBe( expected.type );
		expect( value.response ).toEqual( expected.response );
	} );
} );

describe( 'getCollectionHeader', () => {
	let fulfillment;
	const rewind = ( ...testArgs ) =>
		( fulfillment = getCollectionHeader( ...testArgs ) );
	it( 'yields expected select control when called with less args', () => {
		rewind( 'x-wp-total', '/wc/blocks', 'products' );
		const { value } = fulfillment.next();
		expect( value ).toEqual(
			controls.resolveSelect(
				STORE_KEY,
				'getCollection',
				'/wc/blocks',
				'products'
			)
		);
	} );
	it( 'yields expected select control when called with all args', () => {
		const args = [
			'x-wp-total',
			'/wc/blocks',
			'products/attributes',
			{ sort: 'ASC' },
			[ 10 ],
		];
		rewind( ...args );
		const { value } = fulfillment.next();
		expect( value ).toEqual(
			controls.resolveSelect(
				STORE_KEY,
				'/wc/blocks',
				'products/attributes',
				{ sort: 'ASC' },
				[ 10 ]
			)
		);
		const { done } = fulfillment.next();
		expect( done ).toBe( true );
	} );
} );
