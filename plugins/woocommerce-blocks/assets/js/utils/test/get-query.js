/**
 * Internal dependencies
 */
import getQuery from '../get-query';

describe( 'getQuery', () => {
	test( 'should set per_page as a result of row * col', () => {
		let query = getQuery( {
			columns: 4,
			rows: 3,
		} );
		expect( query.per_page ).toBe( 12 );

		query = getQuery( {
			columns: 1,
			rows: 3,
		} );
		expect( query.per_page ).toBe( 3 );

		query = getQuery( {
			columns: 4,
			rows: 1,
		} );
		expect( query.per_page ).toBe( 4 );
	} );

	describe( 'for different query orders', () => {
		const attributes = {
			columns: 4,
			rows: 3,
			orderby: 'date',
		};
		test( 'should order by date when using "date"', () => {
			const query = getQuery( attributes );
			expect( query.orderby ).toBe( 'date' );
			expect( query.order ).toBeUndefined();
		} );

		test( 'should order by price, DESC when "price_desc"', () => {
			attributes.orderby = 'price_desc';
			const query = getQuery( attributes );
			expect( query.orderby ).toBe( 'price' );
			expect( query.order ).toBe( 'desc' );
		} );

		test( 'should order by price, ASC when "price_asc"', () => {
			attributes.orderby = 'price_asc';
			const query = getQuery( attributes );
			expect( query.orderby ).toBe( 'price' );
			expect( query.order ).toBe( 'asc' );
		} );

		test( 'should order by title, ASC when "title"', () => {
			attributes.orderby = 'title';
			const query = getQuery( attributes );
			expect( query.orderby ).toBe( 'title' );
			expect( query.order ).toBe( 'asc' );
		} );

		test( 'should order by menu_order, ASC when "menu_order"', () => {
			attributes.orderby = 'menu_order';
			const query = getQuery( attributes );
			expect( query.orderby ).toBe( 'menu_order' );
			expect( query.order ).toBe( 'asc' );
		} );

		test( 'should order by popularity when "popularity"', () => {
			attributes.orderby = 'popularity';
			const query = getQuery( attributes );
			expect( query.orderby ).toBe( 'popularity' );
			expect( query.order ).toBeUndefined();
		} );
	} );

	describe( 'for category queries', () => {
		const attributes = {
			columns: 4,
			rows: 3,
			orderby: 'date',
		};
		test( 'should return a general query with no category', () => {
			const query = getQuery( attributes );
			expect( query ).toEqual( {
				orderby: 'date',
				per_page: 12,
				status: 'publish',
			} );
		} );

		test( 'should return an empty category query', () => {
			attributes.categories = [];
			const query = getQuery( attributes );
			expect( query ).toEqual( {
				category: '',
				orderby: 'date',
				per_page: 12,
				status: 'publish',
			} );
		} );

		test( 'should return a category query with one category', () => {
			attributes.categories = [ 1 ];
			const query = getQuery( attributes );
			expect( query ).toEqual( {
				category: '1',
				orderby: 'date',
				per_page: 12,
				status: 'publish',
			} );
		} );

		test( 'should return a category query with two categories', () => {
			attributes.categories = [ 1, 2 ];
			const query = getQuery( attributes );
			expect( query ).toEqual( {
				category: '1,2',
				orderby: 'date',
				per_page: 12,
				status: 'publish',
			} );
		} );
	} );
} );
