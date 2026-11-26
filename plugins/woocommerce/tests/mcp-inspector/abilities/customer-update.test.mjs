import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/customer-update';

// The tests run in order and change the same customer.
describe( ABILITY, () => {
	let customer;
	let guest;
	before( async () => {
		customer = await seed( 'customer', { key: 'customer-update' } );
		( { guest } = await seed( 'customers' ) );
	} );

	test( 'changes the name and address fields it is given, and names them', async () => {
		assert.deepEqual(
			await execute( ABILITY, {
				id: customer.customer_id,
				first_name: 'MCP',
				last_name: 'Updated',
				billing: { city: 'Berlin', country: 'DE' },
			} ),
			{
				customer: {
					id: customer.customer_id,
					user_id: customer.id,
					updated_fields: [
						'first_name',
						'last_name',
						'billing.city',
						'billing.country',
					],
				},
			}
		);
	} );

	test( 'updates the name that customers-query searches', async () => {
		assert.deepEqual(
			(
				await execute( 'woocommerce/customers-query', {
					search: 'MCP Updated',
					include: [ customer.customer_id ],
				} )
			).customers.map( ( { id } ) => id ),
			[ customer.customer_id ]
		);
	} );

	test( 'refuses values the customer already has', () =>
		refused(
			ABILITY,
			{ id: customer.customer_id, first_name: 'MCP' },
			/already has every value sent/
		) );

	test( 'refuses an update without fields', () =>
		refused(
			ABILITY,
			{ id: customer.customer_id },
			/No change was sent/
		) );

	test( 'refuses a country that does not exist', () =>
		refused(
			ABILITY,
			{ id: customer.customer_id, billing: { country: 'XX' } },
			/"XX" is not a valid country/
		) );

	test( 'refuses an invalid billing email', () =>
		refused(
			ABILITY,
			{ id: customer.customer_id, billing: { email: 'nope' } },
			/billing email is not a valid email address/
		) );

	test( 'refuses a guest, who has no account', () =>
		refused(
			ABILITY,
			{ id: guest.id, first_name: 'MCP' },
			new RegExp( `Customer ${ guest.id } is a guest` )
		) );

	test( 'refuses an unknown customer', () =>
		refused(
			ABILITY,
			{ id: 999999999, first_name: 'MCP' },
			/Customer 999999999 does not exist/
		) );
} );
