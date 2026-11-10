import assert from 'node:assert/strict';
import { readdirSync } from 'node:fs';
import { describe, test } from 'node:test';
import { ADMIN, CUSTOMER, call } from './lib/abilities.mjs';
import {
	callTool,
	inspect,
	mapWithLimit,
	payloadOf,
} from './lib/inspector.mjs';
import {
	assertMatchesSnapshot,
	snapshotPath,
	SNAPSHOT_DIR,
} from './lib/snapshot.mjs';
import {
	collectProperties,
	estimateTokens,
	namesDefault,
	restatesEnum,
} from './lib/contract.mjs';
import { seed } from './lib/fixtures.mjs';

const NAMESPACE = 'woocommerce/';
const CONCURRENCY = Number( process.env.MCP_INSPECTOR_CONCURRENCY ?? 4 );

// Estimated tokens of what `mcp-adapter-get-ability-info` returns for one ability. Placeholder until agreed with Core reviewers.
const TOKEN_BUDGET = 3000;
// Variations, terms and images take the product writes past the budget.
const TOKEN_BUDGET_EXCEPTIONS = {
	'woocommerce/product-create': 6000,
	'woocommerce/product-update': 6000,
};

// Abilities still without a file in `abilities/`. The list only shrinks: an ability that gains one fails until it is removed here.
const WITHOUT_BEHAVIOUR_TESTS = [];

const ABILITIES_DIR = new URL( './abilities/', import.meta.url );

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
	assert.ok(
		discovered.length > 0,
		'No WooCommerce ability is exposed over MCP.'
	);
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
					.map(
						( [ path, field ] ) =>
							`${ path }: ${ field.description }`
					);

				assert.deepEqual(
					offenders,
					[],
					'Delete the list; the schema already carries it.'
				);
			} );

			test( 'no description names its default', () => {
				const offenders = Object.entries( fields )
					.filter( ( [ , field ] ) => namesDefault( field ) )
					.map(
						( [ path, field ] ) =>
							`${ path }: ${ field.description }`
					);

				assert.deepEqual(
					offenders,
					[],
					"Declare it as the schema's `default` instead."
				);
			} );

			test( 'stays within the token budget', () => {
				const budget =
					TOKEN_BUDGET_EXCEPTIONS[ info.name ] ?? TOKEN_BUDGET;
				const tokens = estimateTokens( info );

				assert.ok(
					tokens <= budget,
					`About ${ tokens } tokens, over the budget of ${ budget }.`
				);
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

	assert.deepEqual(
		stale,
		[],
		'Delete the snapshots of abilities that are no longer exposed.'
	);
} );

test( 'every ability has a behaviour test file', () => {
	const files = readdirSync( ABILITIES_DIR ).filter( ( file ) =>
		file.endsWith( '.test.mjs' )
	);
	const fileOf = ( name ) => `${ name.slice( NAMESPACE.length ) }.test.mjs`;
	const names = discovered.map( ( { name } ) => name );

	assert.deepEqual(
		names.filter(
			( name ) =>
				! files.includes( fileOf( name ) ) &&
				! WITHOUT_BEHAVIOUR_TESTS.includes( name )
		),
		[],
		'Add a test file to abilities/ for each of these abilities.'
	);
	assert.deepEqual(
		WITHOUT_BEHAVIOUR_TESTS.filter( ( name ) =>
			files.includes( fileOf( name ) )
		),
		[],
		'Remove these abilities from WITHOUT_BEHAVIOUR_TESTS; they have a test file now.'
	);
	assert.deepEqual(
		files.filter(
			( file ) => ! names.some( ( name ) => fileOf( name ) === file )
		),
		[],
		'Delete the test files of abilities that are no longer exposed.'
	);
} );

describe(
	'readonly abilities without required input run with no input',
	{ concurrency: CONCURRENCY },
	() => {
		const readonly = infos.filter(
			( info ) =>
				info.meta?.annotations?.readonly === true &&
				! info.input_schema?.required?.length
		);

		for ( const { name, input_schema: inputSchema } of readonly ) {
			// The adapter requires `parameters`, so `{}` is the emptiest input an MCP client can send. A REST GET can
			// send no input at all, which only the root `default` turns into valid input, so the schema has to declare one.
			test( name, async () => {
				assert.ok(
					Object.hasOwn( inputSchema, 'default' ),
					'Declare a `default` on the root input schema, so a call without any input validates.'
				);

				const { isError, payload } = await call( name, {} );

				assert.equal( isError, false, JSON.stringify( payload ) );
				assert.equal(
					payload.success,
					true,
					JSON.stringify( payload )
				);
			} );
		}
	}
);

const order = await seed( 'order', { key: 'contract' } );
const product = await seed( 'product', { sku: 'mcp-contract' } );

// Input is validated before the permission check, and a check that reads a record refuses everyone without one,
// so this input is valid and names a real record.
const PERMISSION_INPUT = {
	'woocommerce/order-add-note': { id: order.id, note: 'Contract' },
	'woocommerce/order-notes-query': { id: order.id },
	'woocommerce/order-send-email': { id: order.id, email: 'invoice' },
	'woocommerce/order-update': { id: order.id },
	'woocommerce/order-update-status': { id: order.id, status: 'processing' },
	'woocommerce/product-attribute-create': { name: 'MCP permission check' },
	'woocommerce/product-delete': { id: product.id },
	'woocommerce/product-terms-query': { taxonomy: 'product_cat' },
	'woocommerce/product-update': { id: product.id },
};

describe(
	'every ability refuses a customer',
	{ concurrency: CONCURRENCY },
	() => {
		for ( const { name } of infos ) {
			test( name, async () => {
				const input = PERMISSION_INPUT[ name ] ?? {};

				assert.ok(
					await seed( 'permission', {
						user: ADMIN,
						ability: name,
						input,
					} ),
					'The admin is refused this input too, so it cannot show that the customer lacks the capability. Add input naming a real record to PERMISSION_INPUT.'
				);

				const { isError, payload } = await call( name, input, {
					user: CUSTOMER,
				} );

				assert.equal( isError, true, JSON.stringify( payload ) );
				assert.match( String( payload ), /permission/i );
			} );
		}
	}
);
