import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { ADMIN, execute, refused } from '../lib/abilities.mjs';
import { callTool, payloadOf } from '../lib/inspector.mjs';

const ABILITY = 'woocommerce/system-status-tool-run';

describe( ABILITY, () => {
	test( 'runs a runnable tool and returns its message', async () => {
		assert.deepEqual(
			await execute( ABILITY, { id: 'clear_template_cache' } ),
			{ id: 'clear_template_cache', message: 'Template cache cleared.' }
		);
	} );

	test( 'every tool it accepts is listed as runnable', async () => {
		const { tools } = await execute(
			'woocommerce/system-status-tools-query'
		);
		const { enum: accepted } = payloadOf(
			await callTool( ADMIN, 'mcp-adapter-get-ability-info', {
				ability_name: ABILITY,
			} )
		).input_schema.properties.id;

		assert.deepEqual(
			tools
				.filter( ( { runnable } ) => runnable )
				.map( ( { id } ) => id )
				.sort(),
			[ ...accepted ].sort()
		);
	} );

	test( 'refuses a tool that is not runnable', () =>
		refused( ABILITY, { id: 'reset_roles' }, /input\[id\]/ ) );
} );
