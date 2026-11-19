const assert = require( 'node:assert/strict' );
const { spawnSync } = require( 'node:child_process' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );

const toolingBin = path.join( __dirname, '..', 'bin' );

function extension( context ) {
	const directory = fs.mkdtempSync( path.join( os.tmpdir(), 'woo-tools-' ) );
	context.after( () =>
		fs.rmSync( directory, { recursive: true, force: true } )
	);
	fs.writeFileSync(
		path.join( directory, 'package.json' ),
		JSON.stringify( {
			name: 'example-extension',
			config: { woo: { zip: { build: '', root: 'plugin-root' } } },
		} )
	);
	return directory;
}

function run( directory, command, args, env = {} ) {
	const result = spawnSync( command, args, {
		cwd: directory,
		env: { ...process.env, ...env },
		encoding: 'utf8',
	} );
	assert.ifError( result.error );
	assert.equal( result.status, 0, result.stderr );
	return result.stdout;
}

test( 'builds the plugin ZIP without npm and prepares that ZIP through a linked launcher', ( context ) => {
	const directory = extension( context );
	fs.mkdirSync( path.join( directory, 'bin/hooks' ), { recursive: true } );
	fs.writeFileSync(
		path.join( directory, 'example-extension.php' ),
		'<?php\n'
	);
	fs.writeFileSync(
		path.join( directory, 'exclude.lst' ),
		'# ignored\r\n/local-only.txt\r\n/bin/*\r\n'
	);
	fs.writeFileSync( path.join( directory, 'local-only.txt' ), 'skip me' );
	fs.writeFileSync(
		path.join( directory, 'bin/hooks/build-zip-stage.sh' ),
		'printf staged > "$1/staged.txt"\n'
	);
	fs.writeFileSync(
		path.join( directory, 'bin/hooks/e2e-prepare.sh' ),
		'printf "%s/%s/%s" "$WP_VERSION" "$WC_VERSION" "$E2E_PHP_VERSION" > deploy/versions\n'
	);
	fs.writeFileSync(
		path.join( directory, 'bin/link_plugins.js' ),
		'require("node:fs").writeFileSync("deploy/link-ran", "");\n'
	);
	const zip = 'deploy/example-extension.zip';
	run( directory, 'bash', [ path.join( toolingBin, 'build-zip.sh' ) ] );
	const contents = run( directory, 'unzip', [ '-Z1', zip ] ).split( '\n' );
	assert.ok( contents.includes( 'plugin-root/example-extension.php' ) );
	assert.ok( contents.includes( 'plugin-root/staged.txt' ) );
	assert.ok( ! contents.includes( 'plugin-root/local-only.txt' ) );
	assert.ok(
		! contents.some( ( name ) => name.startsWith( 'plugin-root/bin/' ) )
	);

	const link = path.join( directory, 'woo-extension-tooling' );
	fs.symlinkSync( path.join( toolingBin, 'woo-extension-tooling.js' ), link );
	const invalid = spawnSync( link, [ 'e2e', 'prepare' ], {
		cwd: directory,
		env: { ...process.env, E2E_PLUGIN_ZIP: zip, E2E_PHP_VERSION: '8.2.1' },
		encoding: 'utf8',
	} );
	assert.ifError( invalid.error );
	assert.equal( invalid.status, 1 );
	assert.match( invalid.stderr, /Invalid E2E_PHP_VERSION/ );
	assert.ok( ! fs.existsSync( path.join( directory, 'deploy/link-ran' ) ) );

	run( directory, link, [ 'e2e', 'prepare' ], {
		E2E_PLUGIN_ZIP: zip,
		E2E_WP_VERSION: '7.0.1',
		E2E_WC_VERSION: '11.3.0-rc.1',
		E2E_PHP_VERSION: '8.2',
	} );
	assert.equal(
		fs.readFileSync( path.join( directory, 'deploy/versions' ), 'utf8' ),
		'7.0.1/11.3.0-rc.1/8.2'
	);
	assert.equal(
		fs.readFileSync(
			path.join( directory, 'deploy/e2e-plugin/plugin-root/staged.txt' ),
			'utf8'
		),
		'staged'
	);
	assert.ok( fs.existsSync( path.join( directory, 'deploy/link-ran' ) ) );
} );

test( 'rejects invalid ZIP root folder names', ( context ) => {
	const directory = extension( context );
	const manifest = path.join( directory, 'package.json' );
	const pkg = JSON.parse( fs.readFileSync( manifest, 'utf8' ) );
	fs.writeFileSync( path.join( directory, 'exclude.lst' ), '' );
	for ( const name of [
		'',
		'.',
		'..',
		'-root',
		'parent/child',
		'parent\\child',
	] ) {
		pkg.config.woo.zip.root = name;
		fs.writeFileSync( manifest, JSON.stringify( pkg ) );
		const result = spawnSync(
			'bash',
			[ path.join( toolingBin, 'build-zip.sh' ) ],
			{
				cwd: directory,
				encoding: 'utf8',
			}
		);
		assert.equal( result.status, 1, JSON.stringify( name ) );
		assert.ok(
			result.stderr.includes( 'zip.root must be a single folder name' ),
			result.stderr
		);
		assert.ok( ! fs.existsSync( path.join( directory, 'deploy' ) ) );
	}
} );

test( 'uses the staging hook and its inputs from the selected commit', ( context ) => {
	const directory = extension( context );
	const hook = 'bin/hooks/build-zip-stage.sh';
	const input = 'hook-input.txt';
	fs.mkdirSync( path.join( directory, 'bin/hooks' ), { recursive: true } );
	fs.writeFileSync( path.join( directory, 'exclude.lst' ), '/bin/*\n' );
	fs.writeFileSync( path.join( directory, input ), 'committed input' );
	fs.writeFileSync(
		path.join( directory, hook ),
		'cat hook-input.txt > "$1/staged.txt"\n'
	);
	run( directory, 'git', [ 'init', '-q' ] );
	run( directory, 'git', [
		'add',
		'--',
		'package.json',
		'exclude.lst',
		hook,
		input,
	] );
	run( directory, 'git', [
		'-c',
		'user.name=Extension Tooling Test',
		'-c',
		'user.email=extension-tooling@example.test',
		'-c',
		'core.hooksPath=/dev/null',
		'-c',
		'commit.gpgsign=false',
		'commit',
		'-qm',
		'Stage fixture files',
	] );
	fs.writeFileSync( path.join( directory, hook ), 'exit 12\n' );
	fs.writeFileSync( path.join( directory, input ), 'uncommitted input' );
	const args = [ path.join( toolingBin, 'build-zip.sh' ), 'HEAD' ];
	const manifest = path.join( directory, 'package.json' );
	const originalManifest = fs.readFileSync( manifest, 'utf8' );
	for ( const [ build, warning ] of [
		[ 'build', 'Packaging a commit needs a plugin without a build step' ],
		[ '', 'package.json differs from' ],
	] ) {
		const pkg = JSON.parse( originalManifest );
		pkg.version = '1.0.1';
		pkg.config.woo.zip.build = build;
		fs.writeFileSync( manifest, JSON.stringify( pkg ) );
		const rejected = spawnSync( 'bash', args, {
			cwd: directory,
			encoding: 'utf8',
		} );
		assert.equal( rejected.status, 1, rejected.stderr );
		assert.ok( rejected.stderr.includes( warning ), rejected.stderr );
		assert.ok( ! fs.existsSync( path.join( directory, 'deploy' ) ) );
	}
	fs.writeFileSync( manifest, originalManifest );
	run( directory, 'bash', args );
	assert.equal(
		run( directory, 'unzip', [
			'-p',
			'deploy/example-extension.zip',
			'plugin-root/staged.txt',
		] ),
		'committed input'
	);
} );

test( 'forwards XML and test arguments to a pre-provisioned PHPUnit site', ( context ) => {
	const directory = extension( context );
	fs.mkdirSync( path.join( directory, 'vendor' ) );
	fs.writeFileSync(
		path.join( directory, 'vendor/autoload.php' ),
		'<?php\n'
	);
	fs.writeFileSync(
		path.join( directory, 'pnpm' ),
		'#!/bin/sh\nprintf "%s\\n" "$@"\n',
		{ mode: 0o755 }
	);
	const output = run(
		directory,
		'bash',
		[
			path.join( toolingBin, 'phpunit.sh' ),
			'direct',
			'phpunit.xml',
			'--filter',
			'test with spaces',
		],
		{ PATH: directory + path.delimiter + process.env.PATH }
	);
	assert.deepEqual( output.trim().split( '\n' ), [
		'run',
		'wp-env:test',
		'run',
		'cli',
		`--env-cwd=wp-content/plugins/${ path.basename( directory ) }`,
		'--',
		'php',
		'vendor/bin/phpunit',
		'-c',
		'phpunit.xml',
		'--fail-on-empty-test-suite',
		'--filter',
		'test with spaces',
	] );
} );

test( 'returns a failing exit status when a command is terminated by a signal', ( context ) => {
	const directory = extension( context );
	fs.writeFileSync(
		path.join( directory, 'bash' ),
		'#!/bin/sh\nkill -PIPE $$\n',
		{ mode: 0o755 }
	);
	const result = spawnSync(
		path.join( toolingBin, 'woo-extension-tooling.js' ),
		[ 'phplint' ],
		{
			cwd: directory,
			env: {
				...process.env,
				PATH: directory + path.delimiter + process.env.PATH,
			},
		}
	);
	assert.equal( result.status, 128 + os.constants.signals.SIGPIPE );
} );

test( 'passes PHPCS findings to SARB and preserves tool errors', ( context ) => {
	const directory = extension( context );
	const vendorBin = path.join( directory, 'vendor/bin' );
	fs.mkdirSync( vendorBin, { recursive: true } );
	fs.writeFileSync(
		path.join( vendorBin, 'phpcs' ),
		'<?php echo getenv("PHPCS_REPORT"); exit(3);'
	);
	fs.writeFileSync(
		path.join( vendorBin, 'sarb' ),
		'<?php echo stream_get_contents(STDIN);'
	);
	const report = '{"files": {}}';

	for ( const command of [ 'phpcs.sh', 'phpcs-baseline.sh' ] ) {
		const args = [ path.join( toolingBin, command ) ];
		assert.equal(
			run( directory, 'bash', args, { PHPCS_REPORT: report } ),
			report
		);
		const diagnostic = 'ERROR: the coding standard is not installed.';
		const result = spawnSync( 'bash', args, {
			cwd: directory,
			env: { ...process.env, PHPCS_REPORT: diagnostic },
			encoding: 'utf8',
		} );
		assert.equal( result.status, 3, result.stderr );
		assert.equal( result.stdout, diagnostic );
	}
} );

test( 'uses the exact WordPress archive, test library tag and WooCommerce tag without npm', ( context ) => {
	const directory = extension( context );
	const executable = path.join( directory, 'stub' );
	const wpSource = path.join( directory, 'download/wordpress/wp-includes' );
	fs.mkdirSync( wpSource, { recursive: true } );
	fs.writeFileSync(
		path.join( wpSource, 'version.php' ),
		"<?php $wp_version = '7.0';\n"
	);
	run( directory, 'tar', [
		'-czf',
		'wordpress.tar.gz',
		'-C',
		'download',
		'wordpress',
	] );
	fs.writeFileSync(
		executable,
		`#!/bin/bash
set -eu
printf '%s\\n' "$@" >> "$REQUESTS"
case "$(basename "$0")" in
  curl)
    case "\${!#}" in
      */wordpress-7.0.tar.gz) cat "$FIXTURE/wordpress.tar.gz" ;;
      */wp-tests-config-sample.php)
        printf '%s\\n' "<?php define( 'ABSPATH', dirname( __FILE__ ) . '/src/' );" "define( 'DB_PASSWORD', 'yourpasswordhere' );" ;;
      */core/version-check/1.7/)
        if [[ -n "\${WP_RELEASE_RESPONSE:-}" ]]; then
          printf '%s\\n' "$WP_RELEASE_RESPONSE"
        else
          printf '%s\\n' '{"offers":[{"version":"7.0"}]}'
        fi ;;
      */woocommerce.json) printf '%s\\n' '{"version": "11.2.0"}' ;;
      *) exit 2 ;;
    esac ;;
  svn) mkdir -p "\${!#}" ;;
  git)
    if [[ "$1" == ls-remote ]]; then
      [[ "\${!#}" == refs/tags/11.2.0 ]]
    else
      [[ "$3" == --branch=11.2.0 ]]
      target="\${!#}/plugins/woocommerce"
      mkdir -p "$target/bin"
      printf ' * Version: 11.2.0\\n' > "$target/woocommerce.php"
      printf '<?php\\n' > "$target/bin/generate-feature-config.php"
    fi ;;
  mysql) cat >/dev/null ;;
  mysqladmin|composer) : ;;
  *) exit 2 ;;
esac
`,
		{ mode: 0o755 }
	);
	for ( const name of [
		'curl',
		'svn',
		'git',
		'mysql',
		'mysqladmin',
		'composer',
	] ) {
		fs.symlinkSync( executable, path.join( directory, name ) );
	}
	const wpDirectory = path.join( directory, 'test core' );
	const tests = path.join( directory, 'test library' );
	const requests = path.join( directory, 'requests' );
	const env = {
		...process.env,
		PATH: directory + path.delimiter + process.env.PATH,
		FIXTURE: directory,
		REQUESTS: requests,
		RUN_UNIT_TESTS: '1',
		WP_CORE_DIR: wpDirectory,
		WP_TESTS_DIR: tests,
		WC_CORE_DIR: path.join( directory, 'test woocommerce' ),
	};
	const password = "fixture 'a|b&c\\d";
	const install = [
		path.join( toolingBin, 'install.sh' ),
		'wordpress_test',
		'root',
		password,
		'mysql',
	];
	for ( const versions of [
		[ '7.0', '11.2.0' ],
		[ 'latest', 'latest' ],
	] ) {
		fs.writeFileSync( requests, '' );
		const output = run(
			directory,
			'bash',
			[ ...install, ...versions ],
			env
		);
		assert.ok( output.includes( 'Installed WordPress: 7.0' ) );
		assert.ok( output.includes( 'Installed WooCommerce: 11.2.0' ) );
		const calls = fs.readFileSync( requests, 'utf8' );
		assert.ok(
			calls.includes( 'https://wordpress.org/wordpress-7.0.tar.gz' )
		);
		assert.ok(
			calls.includes(
				'https://develop.svn.wordpress.org/tags/7.0/tests/phpunit/includes/'
			)
		);
		assert.ok( calls.includes( '\nrefs/tags/11.2.0\n' ) );
		assert.equal(
			run( directory, 'php', [
				'-r',
				'require $argv[1]; echo ABSPATH, "\\n", DB_PASSWORD;',
				path.join( tests, 'wp-tests-config.php' ),
			] ),
			`${ wpDirectory }/\n${ password }`
		);
	}

	const relativePaths = {
		WP_CORE_DIR: 'relative sites/test core',
		WP_TESTS_DIR: 'relative test library',
		WC_CORE_DIR: 'relative woocommerce',
	};
	run( directory, 'bash', [ ...install, '7.0', '11.2.0' ], {
		...env,
		...relativePaths,
	} );
	assert.ok(
		fs.existsSync(
			path.join( directory, relativePaths.WC_CORE_DIR, 'woocommerce.php' )
		)
	);
	assert.equal(
		run( directory, 'php', [
			'-r',
			'require $argv[1]; echo ABSPATH;',
			path.join(
				directory,
				relativePaths.WP_TESTS_DIR,
				'wp-tests-config.php'
			),
		] ),
		fs.realpathSync( path.join( directory, relativePaths.WP_CORE_DIR ) ) +
			'/'
	);

	for ( const [ versions, warning, overrides ] of [
		[ [ '7.1-RC1', '11.2.0' ], 'Invalid WordPress version' ],
		[ [ '7.0', '11.2.1' ], 'no matching tag in woocommerce/woocommerce' ],
		[
			[ 'latest', '11.2.0' ],
			'Could not read the latest WordPress version',
		],
		[
			[ '7.0', '11.2.0' ],
			'must be nonempty when set',
			{ WP_TESTS_DIR: '' },
		],
	] ) {
		const result = spawnSync( 'bash', [ ...install, ...versions ], {
			cwd: directory,
			env: { ...env, WP_RELEASE_RESPONSE: 'invalid-json', ...overrides },
			encoding: 'utf8',
		} );
		assert.equal( result.status, 1, result.stderr );
		assert.ok( result.stderr.includes( warning ), result.stderr );
	}

	// A failed extraction can leave wp-settings.php in the new directory.
	fs.writeFileSync(
		path.join( directory, 'tar' ),
		`#!/bin/sh
touch "$WP_CORE_DIR/wp-settings.php"
exit 4
`,
		{ mode: 0o755 }
	);
	const failed = spawnSync( 'bash', [ ...install, '7.0', '11.2.0' ], {
		cwd: directory,
		env,
		encoding: 'utf8',
	} );
	assert.equal( failed.status, 4, failed.stderr );
	assert.ok( fs.existsSync( path.join( wpDirectory, 'wp-settings.php' ) ) );
	assert.ok( ! fs.existsSync( path.join( tests, 'wp-tests-config.php' ) ) );
} );
