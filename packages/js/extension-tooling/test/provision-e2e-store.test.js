const assert = require( 'node:assert/strict' );
const { spawnSync } = require( 'node:child_process' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );

const script = path.join( __dirname, '../bin/provision-e2e-store.sh' );

function run( context, args = [], fail = '' ) {
	const directory = fs.mkdtempSync( path.join( os.tmpdir(), 'woo-store-' ) );
	context.after( () =>
		fs.rmSync( directory, { recursive: true, force: true } )
	);
	const log = path.join( directory, 'commands.log' );
	fs.writeFileSync( log, '' );
	fs.writeFileSync(
		path.join( directory, 'wp' ),
		`#!/usr/bin/env bash
printf '%s\\n' "$*" >> "$WP_LOG"
case "$*" in
	"$WP_FAIL"*) [[ -z "$WP_FAIL" ]] || exit 4 ;;
esac
case "$*" in
	'option get woocommerce_cart_page_id') echo 20 ;;
	'option get woocommerce_checkout_page_id') echo 21 ;;
	'post create'*'--post_name=cart '*) echo 30 ;;
	'post create'*'--post_name=checkout '*) echo 31 ;;
esac
`,
		{ mode: 0o755 }
	);
	const result = spawnSync( 'bash', [ script, ...args ], {
		cwd: directory,
		env: {
			...process.env,
			PATH: `${ directory }${ path.delimiter }${ process.env.PATH }`,
			WP_LOG: log,
			WP_FAIL: fail,
		},
		encoding: 'utf8',
	} );
	assert.ifError( result.error );
	const commands = fs.readFileSync( log, 'utf8' ).trim().split( '\n' );
	return { ...result, commands };
}

test( 'provisions the classic and block checkout pages before importing extension data', ( context ) => {
	const result = run( context );
	assert.equal( result.status, 0, result.stderr );
	assert.ok(
		result.commands.includes(
			'post update 20 --post_title=Blocks cart --post_name=blocks-cart'
		)
	);
	assert.ok(
		result.commands.includes(
			'post update 21 --post_title=Blocks checkout --post_name=blocks-checkout'
		)
	);
	const shortcodePages = result.commands.filter( ( command ) =>
		command.startsWith( 'post create ' )
	);
	assert.equal( shortcodePages.length, 2 );
	assert.ok( shortcodePages[ 0 ].endsWith( '[woocommerce_cart]' ) );
	assert.ok( shortcodePages[ 1 ].endsWith( '[woocommerce_checkout]' ) );
	assert.deepEqual( result.commands.slice( -6 ), [
		'option set tests_legacy_woocommerce_cart_page_id 30',
		'option set tests_legacy_woocommerce_checkout_page_id 31',
		'option set e2e_tests_mode legacy',
		'wc docker-e2e provision',
		'action-scheduler run',
		'config set WP_DEBUG true --raw',
	] );
	assert.ok(
		! result.commands.some( ( command ) =>
			command.startsWith( 'option update woocommerce_weight_unit ' )
		)
	);
	assert.ok(
		! result.commands.some( ( command ) =>
			command.startsWith( 'option update woocommerce_dimension_unit ' )
		)
	);
} );

test( 'applies Product Bundles metric units when requested', ( context ) => {
	const result = run( context, [ '--metric-units' ] );
	assert.equal( result.status, 0, result.stderr );
	const weight = 'option update woocommerce_weight_unit kg';
	const index = result.commands.indexOf( weight );
	assert.notEqual( index, -1 );
	assert.equal(
		result.commands[ index + 1 ],
		'option update woocommerce_dimension_unit cm'
	);
} );

test( 'rejects invalid arguments before running WP-CLI', ( context ) => {
	for ( const args of [
		[ '--metric-unit' ],
		[ '--metric-units', 'unexpected' ],
	] ) {
		const result = run( context, args );
		assert.equal( result.status, 1 );
		assert.ok( result.stderr.startsWith( 'Usage:' ) );
		assert.equal( result.commands.join( '' ), '' );
	}
} );

test( 'stops before changing the site if the test provisioning plugin is absent', ( context ) => {
	const result = run( context, [], 'eval' );
	assert.equal( result.status, 4 );
	assert.equal( result.commands.length, 1 );
	assert.ok( result.commands[ 0 ].startsWith( 'eval ' ) );
} );

test( 'stops when WP-CLI fails to create the classic cart page', ( context ) => {
	const result = run( context, [], 'post create' );
	assert.equal( result.status, 4 );
	assert.ok(
		! result.commands.some( ( command ) =>
			command.startsWith( 'option set ' )
		)
	);
	assert.ok( ! result.commands.includes( 'wc docker-e2e provision' ) );
} );
