import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/payment-gateways-query';

// The offline gateways every store has, so no fixture is needed.
const OFFLINE = [ 'bacs', 'cheque', 'cod' ];

describe( ABILITY, () => {
	test( 'lists the offline gateways', async () => {
		const ids = ( await execute( ABILITY ) ).gateways.map(
			( { id } ) => id
		);

		assert.deepEqual(
			OFFLINE.filter( ( id ) => ! ids.includes( id ) ),
			[]
		);
	} );

	test( 'finds one gateway by its ID', async () => {
		const { gateways } = await execute( ABILITY, { id: 'cheque' } );

		assert.deepEqual(
			gateways.map( ( { id } ) => id ),
			[ 'cheque' ]
		);
	} );

	// Gateway settings can hold credentials, so nothing beyond these fields may reach a client.
	test( 'returns the display fields only', async () => {
		for ( const gateway of ( await execute( ABILITY ) ).gateways ) {
			assert.deepEqual(
				Object.keys( gateway ).sort(),
				[ 'description', 'enabled', 'id', 'method_title', 'title' ],
				gateway.id
			);
		}
	} );

	test( 'refuses an unknown gateway', () =>
		refused(
			ABILITY,
			{ id: 'not-a-gateway' },
			/Payment gateway not found/
		) );
} );
