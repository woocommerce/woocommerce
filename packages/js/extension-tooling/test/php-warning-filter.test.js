/**
 * External dependencies
 */
const assert = require( 'node:assert/strict' );
const test = require( 'node:test' );

/**
 * Internal dependencies
 */
const filterPhpWarnings = require( '../src/php-warning-filter' );

test( 'returns PHP issues for the supplied plugin paths in log order', () => {
	const paths = [ '/plugins/test-extension/', '/workspace/checkout/' ];
	const issues = [
		'PHP Fatal error: something in /plugins/test-extension/a.php',
		'PHP Parse error: something in /workspace/checkout/b.php',
		'PHP Warning: something in /plugins/test-extension/c.php',
		'PHP Notice: something in /workspace/checkout/d.php',
		'PHP Deprecated: something in /plugins/test-extension/e.php',
	];
	const log = [
		'PHP Warning: something in /plugins/test-extension-other/a.php',
		...issues,
		'Informational message in /plugins/test-extension/a.php',
		'PHP Warning: undefined value 42 in /wp-includes/core.php',
		'',
	].join( '\n' );

	assert.deepEqual( filterPhpWarnings( log, paths ), issues );
	assert.deepEqual( filterPhpWarnings( log, [] ), [] );
	assert.deepEqual( filterPhpWarnings( log, [ '' ] ), [] );
	assert.deepEqual( filterPhpWarnings( log, [ '', ...paths ] ), issues );
	assert.deepEqual( filterPhpWarnings( log, [ undefined, 42, '   ' ] ), [] );
	assert.deepEqual(
		filterPhpWarnings( log, [ undefined, ...paths ] ),
		issues
	);
	assert.deepEqual( filterPhpWarnings( '', paths ), [] );
} );
