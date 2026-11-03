import assert from 'node:assert/strict';
import { readdirSync } from 'node:fs';
import { describe, test } from 'node:test';
import {
	callTool,
	inspect,
	mapWithLimit,
	payloadOf,
} from './inspector.mjs';
import { assertMatchesSnapshot, snapshotPath, SNAPSHOT_DIR } from './snapshot.mjs';
import {
	collectProperties,
	estimateTokens,
	namesDefault,
	restatesEnum,
} from './contract.mjs';

const ADMIN = 'admin';
const CUSTOMER = 'customer';
const NAMESPACE = 'woocommerce/';
const CONCURRENCY = Number( process.env.MCP_INSPECTOR_CONCURRENCY ?? 4 );

// Estimated tokens of what `mcp-adapter-get-ability-info` returns for one ability. Placeholder until agreed with Core reviewers.
const TOKEN_BUDGET = 3000;
const TOKEN_BUDGET_EXCEPTIONS = {};

const infoSnapshot = ( name ) => `ability-info/${ name.replace( '/', '-' ) }`;

const toolsList = await inspect( { user: ADMIN, method: 'tools/list' } );

const discovered = payloadOf(
	await callTool( ADMIN, 'mcp-adapter-discover-abilities' )
).abilities.filter( ( ability ) => ability.name.startsWith( NAMESPACE ) );

const infos = await mapWithLimit( discovered, CONCURRENCY, async ( { name } ) =>
	payloadOf(
		await callTool( ADMIN, 'mcp-adapter-get-ability-info', {
			ability_name: name,
		} )
	)
);

test( 'the default server lists its meta tools', () => {
	assertMatchesSnapshot( 'tools_list', toolsList );
} );

test( 'the default server discovers the WooCommerce abilities', () => {
	assert.ok( discovered.length > 0, 'No WooCommerce ability is exposed over MCP.' );
	assertMatchesSnapshot( 'discover-abilities', discovered );
} );

describe( 'ability info', () => {
	for ( const info of infos ) {
		describe( info.name, () => {
			const fields = {
				...collectProperties( 'input', info.input_schema ),
				...collectProperties( 'output', info.output_schema ),
			};

			test( 'matches its snapshot', () => {
				assertMatchesSnapshot( infoSnapshot( info.name ), info );
			} );

			test( 'no description lists its own enum values', () => {
				const offenders = Object.entries( fields )
					.filter( ( [ , field ] ) => restatesEnum( field ) )
					.map( ( [ path, field ] ) => `${ path }: ${ field.description }` );

				assert.deepEqual( offenders, [], 'Delete the list; the schema already carries it.' );
			} );

			test( 'no description names its default', () => {
				const offenders = Object.entries( fields )
					.filter( ( [ , field ] ) => namesDefault( field ) )
					.map( ( [ path, field ] ) => `${ path }: ${ field.description }` );

				assert.deepEqual( offenders, [], "Declare it as the schema's `default` instead." );
			} );

			test( 'stays within the token budget', () => {
				const budget = TOKEN_BUDGET_EXCEPTIONS[ info.name ] ?? TOKEN_BUDGET;
				const tokens = estimateTokens( info );

				assert.ok( tokens <= budget, `About ${ tokens } tokens, over the budget of ${ budget }.` );
			} );
		} );
	}
} );

test( 'every ability info snapshot belongs to a discovered ability', () => {
	const expected = new Set(
		discovered.map( ( { name } ) => snapshotPath( infoSnapshot( name ) ) )
	);
	const stale = readdirSync( `${ SNAPSHOT_DIR }/ability-info` )
		.map( ( file ) => `${ SNAPSHOT_DIR }/ability-info/${ file }` )
		.filter( ( file ) => ! expected.has( file ) );

	assert.deepEqual( stale, [], 'Delete the snapshots of abilities that are no longer exposed.' );
} );

describe( 'readonly abilities without required input run with no input', { concurrency: CONCURRENCY }, () => {
	const readonly = infos.filter(
		( info ) =>
			info.meta?.annotations?.readonly === true &&
			! info.input_schema?.required?.length
	);

	for ( const { name } of readonly ) {
		// The adapter requires `parameters`, so `{}` is the emptiest input an MCP client can send.
		test( name, async () => {
			const response = await callTool( ADMIN, 'mcp-adapter-execute-ability', {
				ability_name: name,
				parameters: {},
			} );
			const payload = payloadOf( response );

			assert.equal( response.isError, false, JSON.stringify( payload ) );
			assert.equal( payload.success, true, JSON.stringify( payload ) );
		} );
	}
} );

describe( 'every ability refuses a customer', { concurrency: CONCURRENCY }, () => {
	for ( const { name } of infos ) {
		test( name, async () => {
			const response = await callTool( CUSTOMER, 'mcp-adapter-execute-ability', {
				ability_name: name,
				parameters: {},
			} );

			assert.equal( response.isError, true, JSON.stringify( payloadOf( response ) ) );
			assert.match( String( payloadOf( response ) ), /permission/i );
		} );
	}
} );
