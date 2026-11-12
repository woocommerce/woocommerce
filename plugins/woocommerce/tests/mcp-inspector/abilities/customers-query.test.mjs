import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/customers-query';

describe( ABILITY, () => {
	let customers;
	before( async () => {
		customers = await seed( 'customers' );
	} );

	const query = ( parameters = {} ) =>
		execute( ABILITY, {
			include: Object.values( customers ),
			...parameters,
		} );
	const idsOf = ( data ) => data.customers.map( ( { id } ) => id );

	test( 'returns a customer with their orders and no personal data', async () => {
		const [ buyer ] = (
			await execute( ABILITY, { include: [ customers.buyer ] } )
		).customers;

		assert.deepEqual(
			{ ...buyer, date_modified: undefined },
			{
				id: customers.buyer,
				role: 'customer',
				is_paying_customer: true,
				order_count: 1,
				total_spent: '25.00',
				date_created: '2024-01-01T09:00:00+00:00',
				date_modified: undefined,
			}
		);
	} );

	test( 'lists customers only, newest first', async () => {
		assert.deepEqual( idsOf( await query() ), [
			customers.browser,
			customers.buyer,
		] );
	} );

	test( 'lists another role or every role when asked', async () => {
		assert.deepEqual( idsOf( await query( { role: 'subscriber' } ) ), [
			customers.subscriber,
		] );
		assert.deepEqual(
			idsOf( await query( { role: 'all', order: 'asc' } ) ),
			[ customers.buyer, customers.browser, customers.subscriber ]
		);
	} );

	test( 'leaves out excluded customers', async () => {
		assert.deepEqual(
			idsOf( await query( { exclude: [ customers.browser ] } ) ),
			[ customers.buyer ]
		);
	} );

	test( 'pages through the customers', async () => {
		const data = await query( { per_page: 1, page: 2 } );

		assert.deepEqual( idsOf( data ), [ customers.buyer ] );
		assert.deepEqual(
			{ total: data.total, total_pages: data.total_pages },
			{ total: 2, total_pages: 2 }
		);
	} );
} );
