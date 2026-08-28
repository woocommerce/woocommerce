/**
 * Internal dependencies
 */
import { hasEmptySearchResults, isEmptyDueToSearch } from '../utils';

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

	it( 'returns false when the endpoint resolves the search under another limit too', () => {
		// The Categories report single category view sends the term and the category,
		// so the API is still the one deciding what matched.
		expect(
			hasEmptySearchResults( { search: 'kingston' }, [
				'products',
				'categories',
			] )
		).toBe( false );
	} );

	it( 'ignores limit properties other than the search subject', () => {
		// The resolved IDs land on the first property. A filter carried alongside says
		// nothing about whether the term matched.
		expect(
			hasEmptySearchResults( { search: 'clothing', products: '1,2' }, [
				'categories',
				'products',
			] )
		).toBe( true );
	} );

	it( 'returns true when the limit property is present but empty', () => {
		expect(
			hasEmptySearchResults( { search: 'kingston', coupons: '' }, [
				'coupons',
			] )
		).toBe( true );
	} );
} );

describe( 'isEmptyDueToSearch', () => {
	it( 'returns false when there is no search', () => {
		expect( isEmptyDueToSearch( {}, [ 'products' ] ) ).toBe( false );
	} );

	it( 'returns true for an endpoint that resolves the search itself', () => {
		// The API answers the same way whether the term matched no product or matched
		// products without sales in the period, so the search is the best explanation.
		expect(
			isEmptyDueToSearch( { search: 'kingston' }, [ 'products' ] )
		).toBe( true );
	} );

	it( 'returns true when a client resolved search matched nothing', () => {
		expect(
			isEmptyDueToSearch( { search: 'kingston' }, [ 'coupons' ] )
		).toBe( true );
	} );

	it( 'returns false when a client resolved search produced IDs', () => {
		// The search matched, so an empty report is down to the date range.
		expect(
			isEmptyDueToSearch( { search: 'kingston', coupons: '1,2,3' }, [
				'coupons',
			] )
		).toBe( false );
	} );

	it( 'returns true for a product request that also carries a category', () => {
		// The single category view resolves the search server side, so an empty report
		// during a search is attributed to the term.
		expect(
			isEmptyDueToSearch(
				{ search: 'kingston', products: '1,2', categories: '5' },
				[ 'products', 'categories' ]
			)
		).toBe( true );
	} );
} );
