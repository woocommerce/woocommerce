/**
 * Internal dependencies
 */
import {
	getActiveFiltersFromQuery,
	getDefaultOptionValue,
	getQueryFromActiveFilters,
	getUrlKey,
} from '../filters';

const config = {
	with_select: {
		labels: { add: 'Order Status' },
		rules: [ { value: 'is' } ],
		input: {
			component: 'SelectControl',
			options: [ { value: 'pending' } ],
		},
	},
	with_search: {
		labels: { add: 'Search' },
		rules: [ { value: 'includes' } ],
		input: {
			component: 'Search',
		},
	},
	with_no_rules: {
		labels: { add: 'Order Status' },
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
		// eslint-disable-next-line camelcase
		const with_select = activeFilters[ 0 ];
		expect( with_select.key ).toBe( 'with_select' );
		expect( with_select.rule ).toBe( 'is' );
		expect( with_select.value ).toBe( 'pending' );

		// with_search
		// eslint-disable-next-line camelcase
		const with_search = activeFilters[ 1 ];
		expect( with_search.key ).toBe( 'with_search' );
		expect( with_search.rule ).toBe( 'includes' );
		expect( with_search.value ).toEqual( '1,2,3' );

		// with_search
		// eslint-disable-next-line camelcase
		const with_no_rules = activeFilters[ 2 ];
		expect( with_no_rules.key ).toBe( 'with_no_rules' );
		expect( with_no_rules.rule ).toBeUndefined();
		expect( with_no_rules.value ).toEqual( 'pending' );
	} );

	it( 'should handle multiple filter instances', () => {
		const filterConfig = {
			status: {
				allowMultiple: true,
				input: {
					component: 'SelectControl',
				},
			},
			attribute: {
				allowMultiple: true,
				rules: [
					{
						value: 'is',
					},
					{
						value: 'is_not',
					},
				],
				input: {
					component: 'ProductAttribute',
				},
			},
		};
		const query = {
			status: [ 'pending', 'processing' ],
			attribute_is: [ [ 1, 2 ] ],
			attribute_is_not: [
				[ 1, 3 ],
				[ 2, 4 ],
			],
		};

		const activeFilters = getActiveFiltersFromQuery( query, filterConfig );

		expect( activeFilters ).toEqual( [
			{
				key: 'status',
				value: 'pending',
			},
			{
				key: 'status',
				value: 'processing',
			},
			{
				key: 'attribute',
				rule: 'is',
				value: [ 1, 2 ],
			},
			{
				key: 'attribute',
				rule: 'is_not',
				value: [ 1, 3 ],
			},
			{
				key: 'attribute',
				rule: 'is_not',
				value: [ 2, 4 ],
			},
		] );
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
		const nextQuery = getQueryFromActiveFilters(
			activeFilters,
			query,
			config
		);
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

		const nextQuery = getQueryFromActiveFilters(
			activeFilters,
			query,
			config
		);
		expect( nextQuery.with_select_is ).toBeUndefined();
		expect( nextQuery.with_search_includes ).toBeUndefined();
	} );

	it( 'should only reflect complete filters with multiple values', () => {
		const activeFilters = [
			{
				key: 'valid_date',
				rule: 'between',
				value: [ '2018-04-04', '2018-04-10' ],
			},
			{
				key: 'invalid_date_1',
				rule: 'between',
				value: [ '2018-04-04', undefined ],
			},
			{ key: 'invalid_date_2', rule: 'between', value: '2018-04-04' },
		];
		const query = {};
		const nextQuery = getQueryFromActiveFilters(
			activeFilters,
			query,
			config
		);

		expect( nextQuery.valid_date_between ).toBeDefined();
		expect( nextQuery.invalid_date_1_between ).toBeUndefined();
		expect( nextQuery.invalid_date_2_between ).toBeUndefined();
	} );

	it( 'should handle filters with multiple instances', () => {
		const filterConfig = {
			status: {
				allowMultiple: true,
				input: {
					component: 'SelectControl',
				},
			},
			attribute: {
				allowMultiple: true,
				rules: [
					{
						value: 'is',
					},
					{
						value: 'is_not',
					},
				],
				input: {
					component: 'ProductAttribute',
				},
			},
		};
		const activeFilters = [
			{
				key: 'status',
				value: 'pending',
			},
			{
				key: 'status',
				value: 'processing',
			},
			{
				key: 'attribute',
				rule: 'is',
				value: [ 1, 2 ],
			},
			{
				key: 'attribute',
				rule: 'is_not',
				value: [ 1, 3 ],
			},
			{
				key: 'attribute',
				rule: 'is_not',
				value: [ 2, 4 ],
			},
		];
		const query = {};
		const nextQuery = getQueryFromActiveFilters(
			activeFilters,
			query,
			filterConfig
		);

		expect( nextQuery ).toEqual( {
			status: [ 'pending', 'processing' ],
			attribute_is: [ [ 1, 2 ] ],
			attribute_is_not: [
				[ 1, 3 ],
				[ 2, 4 ],
			],
		} );
	} );
} );

describe( 'getDefaultOptionValue', () => {
	it( 'should return the default option value', () => {
		const options = [ { value: 'new' }, { value: 'returning' } ];
		const currentFilter = {
			labels: { add: 'Customer type' },
			input: {
				component: 'SelectControl',
				options,
				defaultOption: 'returning',
			},
		};
		const value = getDefaultOptionValue( currentFilter, options );
		expect( value ).toBe( 'returning' );
	} );

	it( 'should return the first option value when no default option', () => {
		const options = [ { value: 'new' }, { value: 'returning' } ];
		const currentFilter = {
			labels: { add: 'Customer type' },
			input: {
				component: 'SelectControl',
				options,
			},
		};
		const value = getDefaultOptionValue( currentFilter, options );
		expect( value ).toBe( 'new' );
	} );

	it( 'should return undefined when no options are provided', () => {
		const options = [];
		const currentFilter = {
			labels: { add: 'Product' },
			input: {
				component: 'Search',
			},
		};
		const value = getDefaultOptionValue( currentFilter, options );
		expect( value ).toBeUndefined();
	} );
} );
