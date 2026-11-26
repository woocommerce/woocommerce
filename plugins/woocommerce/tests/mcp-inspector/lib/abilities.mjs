import assert from 'node:assert/strict';
import { callTool, payloadOf } from './inspector.mjs';

export const ADMIN = 'admin';
export const CUSTOMER = 'customer';

// Test files run in parallel, and two product saves at once can deadlock. The database rolls back the loser's
// write and the ability fails with this error, so the call is tried again, as a client would.
const CONFLICT = /another change ran at the same time/;
const ATTEMPTS = 3;

/**
 * Calls an ability the way an MCP client does, through the adapter's `mcp-adapter-execute-ability` meta tool.
 */
export async function call( ability, parameters = {}, { user = ADMIN } = {} ) {
	for ( let attempt = 1; ; attempt++ ) {
		const response = await callTool( user, 'mcp-adapter-execute-ability', {
			ability_name: ability,
			parameters,
		} );
		const result = {
			isError: response.isError === true,
			payload: payloadOf( response ),
		};

		if (
			! result.isError ||
			attempt === ATTEMPTS ||
			! CONFLICT.test( String( result.payload ) )
		) {
			return result;
		}
	}
}

/**
 * Fails unless the ability succeeds, and resolves with its output.
 */
export async function execute( ability, parameters, options ) {
	const { isError, payload } = await call( ability, parameters, options );

	assert.equal( isError, false, JSON.stringify( payload ) );
	assert.equal( payload.success, true, JSON.stringify( payload ) );
	return payload.data;
}

/**
 * Fails unless the ability returns an error whose message matches `pattern`.
 */
export async function refused( ability, parameters, pattern, options ) {
	const { isError, payload } = await call( ability, parameters, options );

	assert.equal(
		isError,
		true,
		`Expected an error, got ${ JSON.stringify( payload ) }`
	);
	assert.match( String( payload ), pattern );
}
