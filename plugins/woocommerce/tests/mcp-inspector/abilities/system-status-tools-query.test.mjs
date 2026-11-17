import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/system-status-tools-query';

describe( ABILITY, () => {
	test( 'lists the tools and which of them can run', async () => {
		const { tools } = await execute( ABILITY );
		const runnable = Object.fromEntries(
			tools.map( ( { id, runnable: canRun } ) => [ id, canRun ] )
		);

		assert.equal( runnable.clear_transients, true );
		assert.equal( runnable.recount_terms, true );
		assert.equal( runnable.reset_roles, false );
		assert.equal( runnable.clear_sessions, false );
	} );

	test( 'returns only the given tools and skips unknown IDs', async () => {
		const { tools } = await execute( ABILITY, {
			include: [ 'recount_terms', 'reset_roles', 'not-a-tool' ],
		} );

		assert.deepEqual(
			tools.map( ( { id } ) => id ),
			[ 'recount_terms', 'reset_roles' ]
		);
	} );
} );
