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
			include: Object.values( customers ).map( ( { id } ) => id ),
			...parameters,
		} );
	const idsOf = ( data ) => data.customers.map( ( { id } ) => id );

	test( 'returns a registered customer with their orders and no personal data', async () => {
		const { customers: found } = await execute( ABILITY, {
			search: customers.buyer.email,
			search_by: 'email',
		} );

		assert.deepEqual( found, [
			{
				id: customers.buyer.id,
				user_id: customers.buyer.user_id,
				role: 'customer',
				order_count: 1,
				total_spent: '25.00',
				date_registered: '2024-01-01T09:00:00+00:00',
				date_last_active: '2024-03-01T09:00:00+00:00',
				date_last_order: '2024-03-01T09:00:00+00:00',
			},
		] );
	} );

	test( 'returns a guest without a user ID or registration date', async () => {
		assert.deepEqual( ( await query( { type: 'guest' } ) ).customers, [
			{
				id: customers.guest.id,
				order_count: 1,
				total_spent: '12.00',
				date_last_active: '2024-03-02T09:00:00+00:00',
				date_last_order: '2024-03-02T09:00:00+00:00',
			},
		] );
	} );

	test( 'lists the most recently active customers first', async () => {
		assert.deepEqual( idsOf( await query() ), [
			customers.guest.id,
			customers.buyer.id,
			customers.browser.id,
		] );
	} );

	test( 'sorts by total spent', async () => {
		assert.deepEqual(
			idsOf( await query( { orderby: 'total_spent', order: 'asc' } ) ),
			[ customers.browser.id, customers.guest.id, customers.buyer.id ]
		);
	} );

	test( 'lists only registered customers when asked', async () => {
		assert.deepEqual( idsOf( await query( { type: 'registered' } ) ), [
			customers.buyer.id,
			customers.browser.id,
		] );
	} );

	test( 'finds a customer by name or user ID', async () => {
		assert.deepEqual( idsOf( await query( { search: 'MCP Bro' } ) ), [
			customers.browser.id,
		] );
		assert.deepEqual(
			idsOf(
				await execute( ABILITY, { user_id: customers.buyer.user_id } )
			),
			[ customers.buyer.id ]
		);
	} );

	test( 'leaves out excluded customers', async () => {
		assert.deepEqual(
			idsOf(
				await query( {
					exclude: [ customers.guest.id, customers.browser.id ],
				} )
			),
			[ customers.buyer.id ]
		);
	} );

	test( 'pages through the customers', async () => {
		const data = await query( { per_page: 2, page: 2 } );

		assert.deepEqual( idsOf( data ), [ customers.browser.id ] );
		assert.deepEqual(
			{ total: data.total, total_pages: data.total_pages },
			{ total: 3, total_pages: 2 }
		);
	} );
} );
