'use strict';

const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { spawnSync } = require( 'node:child_process' );

const GUTENBERG_PACKAGE_VERSION = '100.0.0';
const OLDER_WORDPRESS_DIST_TAG = 'wp-100.0';
const OLDER_WORDPRESS_PACKAGE_VERSION = '90.0.0';
const PREVIOUS_WORDPRESS_DIST_TAG = 'wp-100.1';
const PREVIOUS_WORDPRESS_PACKAGE_VERSION = '91.0.0';
const LATEST_WORDPRESS_DIST_TAG = 'wp-101.0';
const LATEST_WORDPRESS_PACKAGE_VERSION = '92.0.0';
const mockPackageDependencies = {
	'@wordpress/components': {
		'@wordpress/compose': '^7.0.0',
		'@wordpress/icons': '^10.0.0',
		'@wordpress/private-apis': '^1.0.0',
	},
	'@wordpress/compose': {
		'@wordpress/element': '^6.0.0',
	},
	'@wordpress/data': {
		'@wordpress/icons': '^10.0.0',
		'@wordpress/private-apis': '^1.0.0',
	},
	'@wordpress/core-data': {
		'@wordpress/private-apis': '^1.0.0',
		'@wordpress/sync': '^1.0.0',
	},
	'@wordpress/keycodes': {},
	'@wordpress/sync': {
		'@wordpress/private-apis': '^1.0.0',
	},
};

function mockGetPackageNameFromSpec( packageSpec ) {
	const versionSeparatorIndex = packageSpec.indexOf( '@', 1 );

	return versionSeparatorIndex === -1
		? packageSpec
		: packageSpec.slice( 0, versionSeparatorIndex );
}

jest.mock( 'node:child_process', () => ( {
	spawnSync: jest.fn( ( _command, args ) => {
		if ( args[ 0 ] === 'view' && args[ 2 ] === 'dist-tags' ) {
			return {
				status: 0,
				stdout: JSON.stringify( {
					latest: GUTENBERG_PACKAGE_VERSION,
					[ OLDER_WORDPRESS_DIST_TAG ]:
						OLDER_WORDPRESS_PACKAGE_VERSION,
					[ PREVIOUS_WORDPRESS_DIST_TAG ]:
						PREVIOUS_WORDPRESS_PACKAGE_VERSION,
					[ LATEST_WORDPRESS_DIST_TAG ]:
						LATEST_WORDPRESS_PACKAGE_VERSION,
				} ),
				stderr: '',
			};
		}

		if ( args[ 0 ] === 'view' && args[ 2 ] === 'dependencies' ) {
			return {
				status: 0,
				stdout: JSON.stringify(
					mockPackageDependencies[
						mockGetPackageNameFromSpec( args[ 1 ] )
					] || {}
				),
				stderr: '',
			};
		}

		if ( args[ 0 ] === 'view' ) {
			let version = GUTENBERG_PACKAGE_VERSION;

			if ( args[ 1 ].includes( OLDER_WORDPRESS_DIST_TAG ) ) {
				version = OLDER_WORDPRESS_PACKAGE_VERSION;
			} else if ( args[ 1 ].includes( PREVIOUS_WORDPRESS_DIST_TAG ) ) {
				version = PREVIOUS_WORDPRESS_PACKAGE_VERSION;
			} else if ( args[ 1 ].includes( LATEST_WORDPRESS_DIST_TAG ) ) {
				version = LATEST_WORDPRESS_PACKAGE_VERSION;
			}

			return {
				status: 0,
				stdout: JSON.stringify( version ),
				stderr: '',
			};
		}

		return {
			status: 0,
			stdout: '',
			stderr: '',
		};
	} ),
} ) );

const { prepare } = require( '../cache' );

function createFixtureProject( packageJson ) {
	const directory = fs.mkdtempSync(
		path.join( os.tmpdir(), 'jest-wordpress-version-compat-' )
	);

	fs.writeFileSync(
		path.join( directory, 'package.json' ),
		JSON.stringify( packageJson, null, 2 )
	);

	return directory;
}

function createInstalledPackage( cwd, packageName, version, packageJson = {} ) {
	const packageDirectory = path.join(
		cwd,
		'node_modules',
		...packageName.split( '/' )
	);

	fs.mkdirSync( packageDirectory, { recursive: true } );
	fs.writeFileSync(
		path.join( packageDirectory, 'package.json' ),
		JSON.stringify(
			{ name: packageName, version, ...packageJson },
			null,
			2
		)
	);
}

function readCachePackageJson( cacheDirectory ) {
	return JSON.parse(
		fs.readFileSync( path.join( cacheDirectory, 'package.json' ), 'utf8' )
	);
}

function expectCachePackageJson( cacheDirectory, wpVersion, packageVersions ) {
	expect( readCachePackageJson( cacheDirectory ) ).toEqual( {
		private: true,
		name: `jest-wordpress-version-compat-cache-${ wpVersion }`,
		description:
			'Generated cache for @wordpress package compatibility tests.',
		dependencies: packageVersions,
		overrides: packageVersions,
	} );
}

function expectNpmInstallFromCache( cacheDirectory ) {
	expect( spawnSync ).toHaveBeenCalledWith(
		'npm',
		[
			'install',
			'--prefix',
			cacheDirectory,
			'--package-lock=false',
			'--ignore-scripts',
			'--no-audit',
			'--no-fund',
			'--save-exact',
		],
		expect.objectContaining( {
			encoding: 'utf8',
		} )
	);
}

describe( 'cache', () => {
	beforeEach( () => {
		spawnSync.mockClear();
	} );

	it( 'resolves requested packages from package.json dependencies', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
				'@wordpress/icons': 'catalog:wp-bundled',
				'@wordpress/global-styles-engine': '^1.3.0',
				'@woocommerce/data': 'workspace:*',
			},
			devDependencies: {
				'@wordpress/components': 'catalog:wp-min',
				'@wordpress/browserslist-config': 'next',
			},
		} );

		const result = prepare( {
			cwd,
			cacheRoot: path.join( cwd, '.cache' ),
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.packages ).toEqual( [
			'@wordpress/components',
			'@wordpress/data',
		] );
	} );

	it( 'resolves WordPress dependencies from workspace dependencies', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@woocommerce/experimental': 'workspace:*',
			},
			devDependencies: {
				'@woocommerce/internal-js-tests': 'workspace:*',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );

		createInstalledPackage( cwd, '@woocommerce/experimental', '1.0.0', {
			dependencies: {
				'@woocommerce/components': 'workspace:*',
				'@wordpress/keycodes': 'catalog:wp-min',
			},
		} );
		createInstalledPackage(
			path.join( cwd, 'node_modules', '@woocommerce', 'experimental' ),
			'@woocommerce/components',
			'1.0.0',
			{
				dependencies: {
					'@wordpress/core-data': 'catalog:wp-min',
				},
			}
		);
		createInstalledPackage(
			cwd,
			'@woocommerce/internal-js-tests',
			'1.0.0',
			{
				dependencies: {
					'@wordpress/data': 'catalog:wp-min',
				},
			}
		);

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.packages ).toEqual( [
			'@wordpress/core-data',
			'@wordpress/keycodes',
		] );
		expect( result.cachePackages ).toEqual( [
			'@wordpress/core-data',
			'@wordpress/keycodes',
			'@wordpress/private-apis',
			'@wordpress/sync',
		] );
		expect( result.installedPackages ).toEqual( [
			'@wordpress/core-data',
			'@wordpress/keycodes',
			'@wordpress/private-apis',
			'@wordpress/sync',
		] );
		expect( result.cachePackages ).not.toContain( '@wordpress/data' );
	} );

	it( 'prefers explicit package requests and excludes bundled packages', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );

		const result = prepare( {
			cwd,
			cacheRoot: path.join( cwd, '.cache' ),
			wpVersion: 'latest',
			packages: [
				'@wordpress/element',
				'@wordpress/dataviews',
				'@wordpress/dataviews/wp',
				'@wordpress/icons',
			],
			logger: false,
		} );

		expect( result.packages ).toEqual( [ '@wordpress/element' ] );
	} );

	it( 'does not resolve or install bundled packages', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
				'@wordpress/icons': 'catalog:wp-bundled',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'gutenberg',
			logger: false,
		} );

		expect( result.packages ).toEqual( [ '@wordpress/data' ] );
		expect( result.installedPackages ).toEqual( [
			'@wordpress/data',
			'@wordpress/private-apis',
		] );
		expect( spawnSync ).not.toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [ 'view', '@wordpress/icons@latest' ] ),
			expect.anything()
		);
		expect( spawnSync ).not.toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [
				`@wordpress/icons@${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }`,
			] ),
			expect.anything()
		);
	} );

	it( 'creates a cache package and installs missing packages', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.installedPackages ).toEqual( [
			'@wordpress/data',
			'@wordpress/private-apis',
		] );
		expectCachePackageJson( result.cacheDirectory, 'latest', {
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
		} );
		expectNpmInstallFromCache( path.join( cacheRoot, 'latest' ) );
	} );

	it( 'installs transitive WordPress dependencies for cached WordPress packages', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/components': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest-1',
			logger: false,
		} );

		expect( result.packages ).toEqual( [ '@wordpress/components' ] );
		expect( result.cachePackages ).toEqual( [
			'@wordpress/components',
			'@wordpress/compose',
			'@wordpress/element',
			'@wordpress/private-apis',
		] );
		expect( result.installedPackages ).toEqual( [
			'@wordpress/components',
			'@wordpress/compose',
			'@wordpress/element',
			'@wordpress/private-apis',
		] );
		expect( result.packagePaths ).toEqual( {
			'@wordpress/components': path.join(
				cacheRoot,
				'latest-1',
				'node_modules',
				'@wordpress',
				'components'
			),
			'@wordpress/compose': path.join(
				cacheRoot,
				'latest-1',
				'node_modules',
				'@wordpress',
				'compose'
			),
			'@wordpress/element': path.join(
				cacheRoot,
				'latest-1',
				'node_modules',
				'@wordpress',
				'element'
			),
			'@wordpress/private-apis': path.join(
				cacheRoot,
				'latest-1',
				'node_modules',
				'@wordpress',
				'private-apis'
			),
		} );
		expectCachePackageJson( result.cacheDirectory, 'latest-1', {
			'@wordpress/components': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/compose': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/element': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
		} );
		expectNpmInstallFromCache( path.join( cacheRoot, 'latest-1' ) );
	} );

	it( 'skips npm install when cached packages match the target version', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'latest' );

		createInstalledPackage(
			cacheDirectory,
			'@wordpress/data',
			LATEST_WORDPRESS_PACKAGE_VERSION
		);
		createInstalledPackage(
			cacheDirectory,
			'@wordpress/private-apis',
			LATEST_WORDPRESS_PACKAGE_VERSION
		);

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.installedPackages ).toEqual( [] );
		expect( spawnSync ).not.toHaveBeenCalledWith(
			'npm',
			[
				'install',
				'--prefix',
				cacheDirectory,
				'--package-lock=false',
				'--ignore-scripts',
				'--no-audit',
				'--no-fund',
				'--save-exact',
			],
			expect.anything()
		);
	} );

	it( 'refreshes cached packages when the cached version is stale', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'latest' );

		createInstalledPackage(
			cacheDirectory,
			'@wordpress/data',
			PREVIOUS_WORDPRESS_PACKAGE_VERSION
		);
		createInstalledPackage(
			cacheDirectory,
			'@wordpress/private-apis',
			PREVIOUS_WORDPRESS_PACKAGE_VERSION
		);

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.installedPackages ).toEqual( [
			'@wordpress/data',
			'@wordpress/private-apis',
		] );
		expectCachePackageJson( result.cacheDirectory, 'latest', {
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
		} );
		expectNpmInstallFromCache( cacheDirectory );
	} );

	it( 'uses the compatibility cache when the installed package matches the target version', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );

		createInstalledPackage(
			cwd,
			'@wordpress/data',
			LATEST_WORDPRESS_PACKAGE_VERSION
		);

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.cachePackages ).toEqual( [
			'@wordpress/data',
			'@wordpress/private-apis',
		] );
		expect( result.installedPackages ).toEqual( [
			'@wordpress/data',
			'@wordpress/private-apis',
		] );
		expect( result.packagePaths ).toEqual( {
			'@wordpress/data': path.join(
				cacheRoot,
				'latest',
				'node_modules',
				'@wordpress',
				'data'
			),
			'@wordpress/private-apis': path.join(
				cacheRoot,
				'latest',
				'node_modules',
				'@wordpress',
				'private-apis'
			),
		} );
		expectCachePackageJson( result.cacheDirectory, 'latest', {
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
		} );
		expectNpmInstallFromCache( path.join( cacheRoot, 'latest' ) );
	} );

	it( 'uses the previous WordPress dist-tag for latest-1', () => {
		const cwd = createFixtureProject( {} );
		const cacheRoot = path.join( cwd, '.cache' );

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest-1',
			packages: [ '@wordpress/data' ],
			logger: false,
		} );

		expect( result.wpVersion ).toBe( 'latest-1' );
		expectCachePackageJson( result.cacheDirectory, 'latest-1', {
			'@wordpress/data': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
		} );
		expectNpmInstallFromCache( path.join( cacheRoot, 'latest-1' ) );
	} );

	it( 'uses the npm latest dist-tag for gutenberg', () => {
		const cwd = createFixtureProject( {} );
		const cacheRoot = path.join( cwd, '.cache' );

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'gutenberg',
			packages: [ '@wordpress/data' ],
			logger: false,
		} );

		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			[ 'view', '@wordpress/data@latest', 'version', '--json' ],
			expect.objectContaining( {
				encoding: 'utf8',
			} )
		);
		expectCachePackageJson( result.cacheDirectory, 'gutenberg', {
			'@wordpress/data': GUTENBERG_PACKAGE_VERSION,
			'@wordpress/private-apis': GUTENBERG_PACKAGE_VERSION,
		} );
		expectNpmInstallFromCache( path.join( cacheRoot, 'gutenberg' ) );
	} );

	it( 'rejects unsupported WordPress version targets', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );

		for ( const wpVersion of [
			'explicit-version',
			'wp-latest',
			'wp-explicit-version',
			'nightly',
		] ) {
			expect( () =>
				prepare( {
					cwd,
					wpVersion,
					logger: false,
				} )
			).toThrow( /Unsupported WordPress version/ );
		}
	} );
} );
