const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { spawnSync } = require( 'node:child_process' );
const { test: nodeTest } = require( 'node:test' );

module.exports = function checkI18n( { root, domain } ) {
	const wrongDomain =
		domain === 'wrong-domain' ? 'other-domain' : 'wrong-domain';
	for ( const [ name, source, passes ] of [
		[
			'plugin domain',
			"__( 'Hello', " + JSON.stringify( domain ) + ' );',
			true,
		],
		[
			'WooCommerce domain',
			"_n( 'Item', 'Items', count( [ 1, 2 ] ), 'woocommerce' );",
			true,
		],
		[ 'comments', "// __( 'Not a function call' );", true ],
		[ 'missing domain', "__( 'Hello' );", false ],
		[ 'wrong domain', `__( 'Hello', '${ wrongDomain }' );`, false ],
		[
			'ignored wrong domain',
			`// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch\n__( 'Hello', '${ wrongDomain }' );`,
			false,
		],
		[
			'disabled domain check',
			"// phpcs:disable WordPress.WP.I18n\n__( 'Hello', $domain );",
			false,
		],
		[ 'variable domain', "__( 'Hello', $domain );", false ],
		[ 'interpolated domain', '__( \'Hello\', "woo-$domain" );', false ],
	] ) {
		nodeTest( 'checks translation text domains: ' + name, ( context ) => {
			const directory = fs.mkdtempSync(
				path.join( os.tmpdir(), 'woo-i18n-' )
			);
			context.after( () =>
				fs.rmSync( directory, { recursive: true, force: true } )
			);
			const parent = path.join( directory, 'tests', 'checkout' );
			fs.mkdirSync( parent, { recursive: true } );
			const filename = path.join( parent, 'fixture.php' );
			fs.writeFileSync( filename, '<?php\n' + source + '\n' );
			// Exercise the consumer's ruleset, including its annotation policy.
			const result = spawnSync(
				'php',
				[
					'vendor/bin/phpcs',
					'--standard=tools/i18n.xml',
					'--report=full',
					'-s',
					filename,
				],
				{ cwd: root, encoding: 'utf8' }
			);
			assert.ifError( result.error );
			assert.equal( result.signal, null );
			const output = result.stdout + result.stderr;
			assert.equal( result.status === 0, passes, output );

			// The full report prints the source code in parentheses.
			const hasDomainDiagnostic = /\(WordPress\.WP\.I18n\.\w+\)/.test(
				result.stdout
			);
			assert.equal( hasDomainDiagnostic, ! passes, output );
		} );
	}
};
