import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/settings-groups-query';

describe( ABILITY, () => {
	test( 'lists the settings pages first, then the sections under them', async () => {
		const { groups } = await execute( ABILITY, { per_page: 100 } );
		const ids = groups.map( ( { id } ) => id );

		assert.deepEqual( ids.slice( 0, 4 ), [
			'wc_admin',
			'general',
			'products',
			'tax',
		] );
		assert.deepEqual(
			groups.find( ( { id } ) => id === 'email_customer_invoice' ),
			{
				id: 'email_customer_invoice',
				label: 'Order details',
				parent_id: 'email',
			}
		);
	} );

	test( 'returns only the groups it includes, and skips unknown ones', async () => {
		assert.deepEqual(
			await execute( ABILITY, {
				include: [ 'general', 'email_new_order', 'mcp-missing' ],
			} ),
			{
				groups: [
					{ id: 'general', label: 'General', parent_id: '' },
					{
						id: 'email_new_order',
						label: 'New order',
						parent_id: 'email',
					},
				],
				total: 2,
				total_pages: 1,
				page: 1,
				per_page: 20,
			}
		);
	} );

	test( 'pages through the groups', async () => {
		const data = await execute( ABILITY, { per_page: 2, page: 2 } );

		assert.deepEqual(
			data.groups.map( ( { id } ) => id ),
			[ 'products', 'tax' ]
		);
		assert.equal( data.total_pages, Math.ceil( data.total / 2 ) );
	} );
} );
