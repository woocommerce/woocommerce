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
	return { ...result, commands: fs.readFileSync( log, 'utf8' ).trim() };
}

test( 'provisions the classic and block checkout pages before importing extension data', ( context ) => {
	const result = run( context );
	assert.equal( result.status, 0, result.stderr );
	assert.match( result.commands, /post update 20 .*--post_name=blocks-cart/ );
	assert.match(
		result.commands,
		/post update 21 .*--post_name=blocks-checkout/
	);
	assert.match( result.commands, /post create .*\[woocommerce_cart\]/ );
	assert.match( result.commands, /post create .*\[woocommerce_checkout\]/ );
	assert.match(
		result.commands,
		/^option set tests_legacy_woocommerce_cart_page_id 30\noption set tests_legacy_woocommerce_checkout_page_id 31\noption set e2e_tests_mode legacy\nwc docker-e2e provision\naction-scheduler run$/m
	);
	assert.doesNotMatch(
		result.commands,
		/woocommerce_(weight|dimension)_unit/
	);
} );

test( 'applies Product Bundles metric units when requested', ( context ) => {
	const result = run( context, [ '--metric-units' ] );
	assert.equal( result.status, 0, result.stderr );
	assert.match(
		result.commands,
		/^option update woocommerce_weight_unit kg\noption update woocommerce_dimension_unit cm$/m
	);
} );

test( 'stops before changing the site if the test provisioning plugin is absent', ( context ) => {
	const result = run( context, [], 'eval' );
	assert.equal( result.status, 4 );
	assert.match( result.commands, /^eval [^\n]+$/ );
} );

test( 'stops when WP-CLI fails to create the classic cart page', ( context ) => {
	const result = run( context, [], 'post create' );
	assert.equal( result.status, 4 );
	assert.doesNotMatch(
		result.commands,
		/option set|wc docker-e2e provision/
	);
} );
