/**
 * @format
 */

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { getUrlKey, getActiveFiltersFromQuery, getQueryFromActiveFilters } from '../utils';

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

describe( 'getActiveFiltersFromQuery', () => {
	it( 'should return activeFilters from a query', () => {
		const query = {
			with_select_is: 'pending',
			with_search_includes: '1,2,3',
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
		expect( with_search.value ).toEqual( '1,2,3' );

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

describe( 'getQueryFromActiveFilters', () => {
	it( 'should return a query object from activeFilters', () => {
		const activeFilters = [
			{ key: 'status', rule: 'is', value: 'open' },
			{
				key: 'things',
				rule: 'includes',
				value: '1,2,3',
			},
			{ key: 'customer', value: 'new' },
		];

		const query = {};
		const nextQuery = getQueryFromActiveFilters( activeFilters, query, config );
		expect( nextQuery.status_is ).toBe( 'open' );
		expect( nextQuery.things_includes ).toBe( '1,2,3' );
		expect( nextQuery.customer ).toBe( 'new' );
	} );

	it( 'should remove parameters from the previous filters', () => {
		const activeFilters = [];
		const query = {
			with_select_is: 'complete',
			with_search_includes: '45',
		};

		const nextQuery = getQueryFromActiveFilters( activeFilters, query, config );
		expect( nextQuery.with_select_is ).toBeUndefined();
		expect( nextQuery.with_search_includes ).toBeUndefined();
	} );
} );
