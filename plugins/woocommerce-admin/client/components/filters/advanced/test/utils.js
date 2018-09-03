/**
 * @format
 */

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import {
	getUrlKey,
	getSearchFilterValue,
	getActiveFiltersFromQuery,
	getUrlValue,
	getQueryFromActiveFilters,
} from '../utils';

describe( 'getUrlKey', () => {
	it( 'should return a correctly formatted string', () => {
		const key = getUrlKey( 'key', 'rule' );
		expect( key ).toBe( 'key_rule' );
	} );

	it( 'should return a correctly formatted string with no rule', () => {
		const key = getUrlKey( 'key' );
		expect( key ).toBe( 'key' );
	} );
} );

describe( 'getSearchFilterValue', () => {
	it( 'should convert url query param into value readable by Search component', () => {
		const str = '1,2,3';
		const values = getSearchFilterValue( str );
		expect( Array.isArray( values ) ).toBeTruthy();
		expect( values[ 0 ] ).toBe( '1' );
		expect( values[ 1 ] ).toBe( '2' );
		expect( values[ 2 ] ).toBe( '3' );
	} );

	it( 'should convert an empty string into an empty array', () => {
		const str = '';
		const values = getSearchFilterValue( str );
		expect( Array.isArray( values ) ).toBeTruthy();
		expect( values.length ).toBe( 0 );
	} );
} );

describe( 'getActiveFiltersFromQuery', () => {
	const config = {
		with_select: {
			rules: [ { value: 'is' } ],
			input: {
				component: 'SelectControl',
				options: [ { value: 'pending' } ],
			},
		},
		with_search: {
			rules: [ { value: 'includes' } ],
			input: {
				component: 'Search',
			},
		},
		with_no_rules: {
			input: {
				component: 'SelectControl',
				options: [ { value: 'pending' } ],
			},
		},
	};

	it( 'should return activeFilters from a query', () => {
		const query = {
			with_select_is: 'pending',
			with_search_includes: '',
			with_no_rules: 'pending',
		};

		const activeFilters = getActiveFiltersFromQuery( query, config );
		expect( Array.isArray( activeFilters ) ).toBeTruthy();
		expect( activeFilters.length ).toBe( 3 );

		// with_select
		const with_select = activeFilters[ 0 ];
		expect( with_select.key ).toBe( 'with_select' );
		expect( with_select.rule ).toBe( 'is' );
		expect( with_select.value ).toBe( 'pending' );

		// with_search
		const with_search = activeFilters[ 1 ];
		expect( with_search.key ).toBe( 'with_search' );
		expect( with_search.rule ).toBe( 'includes' );
		expect( with_search.value ).toEqual( [] );

		// with_search
		const with_no_rules = activeFilters[ 2 ];
		expect( with_no_rules.key ).toBe( 'with_no_rules' );
		expect( with_no_rules.rule ).toBeUndefined();
		expect( with_no_rules.value ).toEqual( 'pending' );
	} );

	it( 'should ignore irrelevant query parameters', () => {
		const query = {
			with_select: 'pending', // no rule associated
			status: 45,
		};

		const activeFilters = getActiveFiltersFromQuery( query, config );
		expect( activeFilters.length ).toBe( 0 );
	} );

	it( 'should return an empty array with no relevant parameters', () => {
		const query = {};

		const activeFilters = getActiveFiltersFromQuery( query, config );
		expect( Array.isArray( activeFilters ) ).toBe( true );
		expect( activeFilters.length ).toBe( 0 );
	} );
} );

describe( 'getUrlValue', () => {
	it( 'should pass through a string', () => {
		const value = getUrlValue( 'my string' );
		expect( value ).toBe( 'my string' );
	} );

	it( 'should return null for a non-string value', () => {
		const value = getUrlValue( {} );
		expect( value ).toBeNull();
	} );

	it( 'should return null for an empty array', () => {
		const value = getUrlValue( [] );
		expect( value ).toBeNull();
	} );

	it( 'should return comma separated values when given an array', () => {
		const value = getUrlValue( [ 1, 2, 3 ] );
		expect( value ).toBe( '1,2,3' );
	} );
} );

describe( 'getQueryFromActiveFilters', () => {
	it( 'should return a query object from activeFilters', () => {
		const activeFilters = [
			{ key: 'status', rule: 'is', value: 'open' },
			{
				key: 'things',
				rule: 'includes',
				value: [ 1, 2, 3 ],
			},
			{ key: 'customer', value: 'new' },
		];

		const query = getQueryFromActiveFilters( activeFilters );
		expect( query.status_is ).toBe( 'open' );
		expect( query.things_includes ).toBe( '1,2,3' );
		expect( query.customer ).toBe( 'new' );
	} );

	it( 'should remove parameters from the previous filters', () => {
		const nextFilters = [];
		const previousFilters = [
			{ key: 'status', rule: 'is', value: 'open' },
			{
				key: 'things',
				rule: 'includes',
				value: [ 1, 2, 3 ],
			},
			{ key: 'customer', value: 'new' },
		];

		const query = getQueryFromActiveFilters( nextFilters, previousFilters );
		expect( query.status_is ).toBeUndefined();
		expect( query.things_includes ).toBeUndefined();
		expect( query.customer ).toBeUndefined();
	} );
} );
