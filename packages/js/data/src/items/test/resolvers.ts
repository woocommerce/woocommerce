/**
 * Internal dependencies
 */
import { getItems } from '../resolvers';
import { setItems, setItemsTotalCount, setError } from '../actions';

describe( 'getItems resolver', () => {
	it( 'fetches a single page when total count matches the first page', () => {
		const query = { search: 'kingston', per_page: 100 };
		const generator = getItems( 'products', query );

		// First request is dispatched.
		generator.next();
		// Resolver receives the first page response.
		const items = [
			{ id: 1, name: 'one' },
			{ id: 2, name: 'two' },
		];
		const setTotalAction = generator.next( {
			items,
			totalCount: items.length,
		} ).value;

		expect( setTotalAction ).toEqual(
			setItemsTotalCount( 'products', query, items.length )
		);

		const setItemsAction = generator.next().value;
		expect( setItemsAction ).toEqual(
			setItems( 'products', query, items )
		);

		expect( generator.next().done ).toBe( true );
	} );

	it( 'paginates additional pages when the first page does not cover total count', () => {
		const query = { search: 'kingston', per_page: 2 };
		const generator = getItems( 'products', query );

		// Page 1 request.
		generator.next();

		const pageOne = [
			{ id: 1, name: 'one' },
			{ id: 2, name: 'two' },
		];
		// First response yields total count of 5 → resolver must fetch pages 2 and 3.
		generator.next( { items: pageOne, totalCount: 5 } );

		const pageTwo = [
			{ id: 3, name: 'three' },
			{ id: 4, name: 'four' },
		];
		// Page 2 response.
		generator.next( { items: pageTwo, totalCount: 5 } );

		const pageThree = [ { id: 5, name: 'five' } ];
		// Page 3 response → after this we set the accumulated items.
		const setTotalAction = generator.next( {
			items: pageThree,
			totalCount: 5,
		} ).value;

		expect( setTotalAction ).toEqual(
			setItemsTotalCount( 'products', query, 5 )
		);

		const setItemsAction = generator.next().value;
		expect( setItemsAction ).toEqual(
			setItems( 'products', query, [
				...pageOne,
				...pageTwo,
				...pageThree,
			] )
		);

		expect( generator.next().done ).toBe( true );
	} );

	it( 'does not paginate when the caller requested a specific page', () => {
		const query = { search: 'kingston', per_page: 2, page: 2 };
		const generator = getItems( 'products', query );

		generator.next();
		const items = [ { id: 10, name: 'ten' } ];
		const setTotalAction = generator.next( {
			items,
			totalCount: 50,
		} ).value;

		expect( setTotalAction ).toEqual(
			setItemsTotalCount( 'products', query, 50 )
		);
		const setItemsAction = generator.next().value;
		expect( setItemsAction ).toEqual(
			setItems( 'products', query, items )
		);
		expect( generator.next().done ).toBe( true );
	} );

	it( 'does not paginate for unbounded (per_page = -1) requests', () => {
		const query = { search: 'kingston', per_page: -1 };
		const generator = getItems( 'products', query );

		generator.next();
		const items = [ { id: 1, name: 'one' } ];
		const setTotalAction = generator.next( {
			items,
			totalCount: items.length,
		} ).value;

		expect( setTotalAction ).toEqual(
			setItemsTotalCount( 'products', query, items.length )
		);
		const setItemsAction = generator.next().value;
		expect( setItemsAction ).toEqual(
			setItems( 'products', query, items )
		);
		expect( generator.next().done ).toBe( true );
	} );

	it( 'dispatches setError when the request throws', () => {
		const query = { search: 'kingston', per_page: 100 };
		const generator = getItems( 'products', query );

		generator.next();
		const error = new Error( 'boom' );
		const action = generator.throw( error ).value;
		expect( action ).toEqual( setError( 'products', query, error ) );
		expect( generator.next().done ).toBe( true );
	} );
} );
