import assert from 'node:assert/strict';
import { callTool, payloadOf } from './inspector.mjs';

export const ADMIN = 'admin';
export const CUSTOMER = 'customer';

/**
 * Calls an ability the way an MCP client does, through the adapter's `mcp-adapter-execute-ability` meta tool.
 */
export async function call( ability, parameters = {}, { user = ADMIN } = {} ) {
	const response = await callTool( user, 'mcp-adapter-execute-ability', {
		ability_name: ability,
		parameters,
	} );
	return {
		isError: response.isError === true,
		payload: payloadOf( response ),
	};
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
