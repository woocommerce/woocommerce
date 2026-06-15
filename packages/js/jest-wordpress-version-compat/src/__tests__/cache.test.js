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

function createInstalledPackage( cwd, packageName, version ) {
	const packageDirectory = path.join(
		cwd,
		'node_modules',
		...packageName.split( '/' )
	);

	fs.mkdirSync( packageDirectory, { recursive: true } );
	fs.writeFileSync(
		path.join( packageDirectory, 'package.json' ),
		JSON.stringify( { name: packageName, version }, null, 2 )
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
		expect(
			fs.existsSync( path.join( result.cacheDirectory, 'package.json' ) )
		).toBe( true );
		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [
				'install',
				'--prefix',
				path.join( cacheRoot, 'latest' ),
				`@wordpress/data@${ LATEST_WORDPRESS_PACKAGE_VERSION }`,
				`@wordpress/private-apis@${ LATEST_WORDPRESS_PACKAGE_VERSION }`,
			] ),
			expect.objectContaining( {
				encoding: 'utf8',
			} )
		);
		expect(
			JSON.parse(
				fs.readFileSync(
					path.join(
						result.cacheDirectory,
						'resolved-versions.json'
					),
					'utf8'
				)
			)
		).toEqual( {
			__dependencies: {
				'@wordpress/data': {
					version: LATEST_WORDPRESS_PACKAGE_VERSION,
					dependencies: mockPackageDependencies[ '@wordpress/data' ],
				},
				'@wordpress/private-apis': {
					version: LATEST_WORDPRESS_PACKAGE_VERSION,
					dependencies: {},
				},
			},
			__distTags: {
				'@wordpress/data': LATEST_WORDPRESS_DIST_TAG,
				'@wordpress/private-apis': LATEST_WORDPRESS_DIST_TAG,
			},
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
		} );
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
		} );
		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [
				'install',
				`@wordpress/components@${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }`,
				`@wordpress/compose@${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }`,
				`@wordpress/element@${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }`,
				`@wordpress/private-apis@${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }`,
			] ),
			expect.anything()
		);
	} );

	it( 'reuses cached package dependencies for the same package version', () => {
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

		fs.mkdirSync( cacheDirectory, { recursive: true } );
		fs.writeFileSync(
			path.join( cacheDirectory, 'resolved-versions.json' ),
			JSON.stringify(
				{
					__dependencies: {
						'@wordpress/data': {
							version: LATEST_WORDPRESS_PACKAGE_VERSION,
							dependencies:
								mockPackageDependencies[ '@wordpress/data' ],
						},
						'@wordpress/private-apis': {
							version: LATEST_WORDPRESS_PACKAGE_VERSION,
							dependencies: {},
						},
					},
					__distTags: {
						'@wordpress/data': LATEST_WORDPRESS_DIST_TAG,
						'@wordpress/private-apis': LATEST_WORDPRESS_DIST_TAG,
					},
					'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
					'@wordpress/private-apis':
						LATEST_WORDPRESS_PACKAGE_VERSION,
				},
				null,
				2
			)
		);

		prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( spawnSync ).not.toHaveBeenCalledWith(
			'npm',
			[
				'view',
				`@wordpress/data@${ LATEST_WORDPRESS_PACKAGE_VERSION }`,
				'dependencies',
				'--json',
			],
			expect.anything()
		);
		expect( spawnSync ).not.toHaveBeenCalledWith(
			'npm',
			[
				'view',
				`@wordpress/private-apis@${ LATEST_WORDPRESS_PACKAGE_VERSION }`,
				'dependencies',
				'--json',
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
		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [
				'install',
				`@wordpress/data@${ LATEST_WORDPRESS_PACKAGE_VERSION }`,
				`@wordpress/private-apis@${ LATEST_WORDPRESS_PACKAGE_VERSION }`,
			] ),
			expect.anything()
		);
	} );

	it( 'does not require a cache entry when the installed package matches the target version', () => {
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

		expect( result.cachePackages ).toEqual( [] );
		expect( result.installedPackages ).toEqual( [] );
		expect( result.packagePaths ).toEqual( {
			'@wordpress/data': path.join(
				fs.realpathSync( cwd ),
				'node_modules',
				'@wordpress',
				'data'
			),
		} );
		expect( spawnSync ).not.toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [ 'install' ] ),
			expect.anything()
		);
	} );

	it( 'refreshes latest when cached metadata does not include the npm dist-tag', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'latest' );

		fs.mkdirSync( cacheDirectory, { recursive: true } );
		fs.writeFileSync(
			path.join( cacheDirectory, 'resolved-versions.json' ),
			JSON.stringify(
				{
					'@wordpress/data': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
				},
				null,
				2
			)
		);

		prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect(
			JSON.parse(
				fs.readFileSync(
					path.join( cacheDirectory, 'resolved-versions.json' ),
					'utf8'
				)
			)
		).toEqual( {
			__dependencies: {
				'@wordpress/data': {
					version: LATEST_WORDPRESS_PACKAGE_VERSION,
					dependencies: mockPackageDependencies[ '@wordpress/data' ],
				},
				'@wordpress/private-apis': {
					version: LATEST_WORDPRESS_PACKAGE_VERSION,
					dependencies: {},
				},
			},
			__distTags: {
				'@wordpress/data': LATEST_WORDPRESS_DIST_TAG,
				'@wordpress/private-apis': LATEST_WORDPRESS_DIST_TAG,
			},
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
		} );
	} );

	it( 'throws in offline mode when packages are missing', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );

		expect( () =>
			prepare( {
				cwd,
				cacheRoot: path.join( cwd, '.cache' ),
				wpVersion: 'latest',
				offline: true,
			} )
		).toThrow( /run the compatibility test once with network access/ );
	} );

	it( 'uses the previous WordPress dist-tag for latest-1', () => {
		const cwd = createFixtureProject( {} );
		const cacheRoot = path.join( cwd, '.cache' );

		prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest-1',
			packages: [ '@wordpress/data' ],
			logger: false,
		} );

		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [
				'install',
				`@wordpress/data@${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }`,
			] ),
			expect.anything()
		);
	} );

	it( 'uses the npm latest dist-tag for gutenberg', () => {
		const cwd = createFixtureProject( {} );
		const cacheRoot = path.join( cwd, '.cache' );

		prepare( {
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
		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [
				'install',
				`@wordpress/data@${ GUTENBERG_PACKAGE_VERSION }`,
			] ),
			expect.anything()
		);
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
