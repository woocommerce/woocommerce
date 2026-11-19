/**
 * External dependencies
 */
const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const test = require( 'node:test' );

test( 'switches checkout pages and themes in either order without reactivating the active theme', () => {
	const result = execFileSync(
		'php',
		[ path.join( __dirname, 'fixtures/class-checkout-mode-fixture.php' ) ],
		{ encoding: 'utf8' }
	);

	assert.deepEqual( JSON.parse( result ), [
		[ '32', '33' ],
		[ 12, 13 ],
		[ 'blocks', 'twentytwentyfive', [ '32', '33' ] ],
		[ 'legacy', 'storefront', [ 12, 13 ] ],
		[ 'legacy', 'storefront' ],
		[ 'twentytwentyfive', 'storefront' ],
	] );
} );
