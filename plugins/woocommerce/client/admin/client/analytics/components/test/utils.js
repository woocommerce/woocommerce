/**
 * Internal dependencies
 */
import { hasEmptySearchResults } from '../utils';

describe( 'hasEmptySearchResults', () => {
	it( 'returns false when there is no search', () => {
		expect( hasEmptySearchResults( {}, [ 'coupons' ] ) ).toBe( false );
	} );

	it( 'returns false when the endpoint resolves the search itself', () => {
		// The term is sent through as `search`, so the API decides what it matches
		// and an empty ID list on the client means nothing.
		expect(
			hasEmptySearchResults( { search: 'kingston' }, [ 'products' ] )
		).toBe( false );
	} );

	it( 'returns true when a client resolved search matched nothing', () => {
		expect(
			hasEmptySearchResults( { search: 'kingston' }, [ 'coupons' ] )
		).toBe( true );
	} );

	it( 'returns false when a client resolved search produced IDs', () => {
		expect(
			hasEmptySearchResults( { search: 'kingston', coupons: '1,2,3' }, [
				'coupons',
			] )
		).toBe( false );
	} );

	it( 'returns true when a request limited by several properties matched nothing', () => {
		// The Categories report limits its product requests by both properties, so it
		// still resolves the search to IDs even though `products` is one of them.
		expect(
			hasEmptySearchResults( { search: 'kingston' }, [
				'products',
				'categories',
			] )
		).toBe( true );
	} );

	it( 'returns true when the searched property is empty but another one has a value', () => {
		expect(
			hasEmptySearchResults( { search: 'kingston', categories: '5' }, [
				'products',
				'categories',
			] )
		).toBe( true );
	} );

	it( 'returns false when every limit property has a value', () => {
		expect(
			hasEmptySearchResults(
				{ search: 'kingston', products: '1,2', categories: '5' },
				[ 'products', 'categories' ]
			)
		).toBe( false );
	} );

	it( 'returns true when the limit property is present but empty', () => {
		expect(
			hasEmptySearchResults( { search: 'kingston', coupons: '' }, [
				'coupons',
			] )
		).toBe( true );
	} );
} );
