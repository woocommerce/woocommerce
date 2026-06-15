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

const {
	getCacheDirectory,
	getCachedPackagePath,
	getPackagesRequiringCache,
	parseNpmViewDistTags,
	parseNpmViewVersion,
	prepare,
	resolveWordPressDistTagFromNpm,
	resolveNpmDistTag,
	resolvePackageVersionFromNpm,
	resolveRequestedPackages,
	toPackageSpec,
} = require( '../cache' );

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

		expect(
			resolveRequestedPackages( { cwd, wpVersion: 'latest' } )
		).toEqual( [ '@wordpress/components', '@wordpress/data' ] );
	} );

	it( 'prefers explicit package requests over package.json dependencies', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );

		expect(
			resolveRequestedPackages( {
				cwd,
				wpVersion: 'latest',
				packages: '@wordpress/element',
			} )
		).toEqual( [ '@wordpress/element' ] );
	} );

	it( 'excludes bundled packages from explicit package requests', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );

		expect(
			resolveRequestedPackages( {
				cwd,
				wpVersion: 'latest',
				packages: [
					'@wordpress/data',
					'@wordpress/dataviews',
					'@wordpress/dataviews/wp',
					'@wordpress/icons',
				],
			} )
		).toEqual( [ '@wordpress/data' ] );
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
		expect( result.installedPackages ).toEqual( [ '@wordpress/data' ] );
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

		expect( result.installedPackages ).toEqual( [ '@wordpress/data' ] );
		expect(
			fs.existsSync( path.join( result.cacheDirectory, 'package.json' ) )
		).toBe( true );
		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			expect.arrayContaining( [
				'install',
				'--prefix',
				getCacheDirectory( { cwd, cacheRoot, wpVersion: 'latest' } ),
				`@wordpress/data@${ LATEST_WORDPRESS_PACKAGE_VERSION }`,
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
			__distTags: {
				'@wordpress/data': LATEST_WORDPRESS_DIST_TAG,
			},
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
		} );
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

		expect(
			getPackagesRequiringCache( {
				cacheDirectory: getCacheDirectory( {
					cwd,
					cacheRoot,
					wpVersion: 'latest',
				} ),
				cwd,
				packages: [ '@wordpress/data' ],
				wpVersion: 'latest',
			} )
		).toEqual( [] );

		const result = prepare( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.cachePackages ).toEqual( [] );
		expect( result.installedPackages ).toEqual( [] );
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
		const cacheDirectory = getCacheDirectory( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
		} );

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

		expect(
			toPackageSpec( '@wordpress/data', 'latest', {
				cacheDirectory,
			} )
		).toBe( `@wordpress/data@${ LATEST_WORDPRESS_PACKAGE_VERSION }` );
		expect(
			JSON.parse(
				fs.readFileSync(
					path.join( cacheDirectory, 'resolved-versions.json' ),
					'utf8'
				)
			)
		).toEqual( {
			__distTags: {
				'@wordpress/data': LATEST_WORDPRESS_DIST_TAG,
			},
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
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
		).toThrow( /call prepareWordPressPackages\(\) before running tests/ );
	} );

	it( 'returns cached package paths', () => {
		expect(
			getCachedPackagePath( '@wordpress/data', {
				cacheRoot: '/tmp/cache',
				wpVersion: 'latest',
			} )
		).toBe( '/tmp/cache/latest/node_modules/@wordpress/data' );
	} );

	it( 'builds package specs from version metadata', () => {
		expect( toPackageSpec( '@wordpress/data', 'latest' ) ).toBe(
			`@wordpress/data@${ LATEST_WORDPRESS_PACKAGE_VERSION }`
		);
		expect( toPackageSpec( '@wordpress/data', 'latest-1' ) ).toBe(
			`@wordpress/data@${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }`
		);
		expect( toPackageSpec( '@wordpress/data', 'gutenberg' ) ).toBe(
			`@wordpress/data@${ GUTENBERG_PACKAGE_VERSION }`
		);
		expect( toPackageSpec( '@wordpress/components', 'gutenberg' ) ).toBe(
			`@wordpress/components@${ GUTENBERG_PACKAGE_VERSION }`
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
				resolveRequestedPackages( {
					cwd,
					wpVersion,
				} )
			).toThrow( /Unsupported WordPress version/ );
			expect( () =>
				toPackageSpec( '@wordpress/data', wpVersion )
			).toThrow( /Unsupported WordPress version/ );
		}
	} );

	it( 'resolves package versions from npm dist-tags', () => {
		expect(
			resolvePackageVersionFromNpm( '@wordpress/data', 'gutenberg' )
		).toBe( GUTENBERG_PACKAGE_VERSION );
		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			[ 'view', '@wordpress/data@latest', 'version', '--json' ],
			expect.objectContaining( {
				encoding: 'utf8',
			} )
		);
	} );

	it( 'resolves latest from the highest WordPress npm dist-tag', () => {
		expect( resolveNpmDistTag( '@wordpress/data', 'latest' ) ).toBe(
			LATEST_WORDPRESS_DIST_TAG
		);
		expect( resolveWordPressDistTagFromNpm( '@wordpress/data' ) ).toBe(
			LATEST_WORDPRESS_DIST_TAG
		);
		expect( spawnSync ).toHaveBeenCalledWith(
			'npm',
			[ 'view', '@wordpress/data', 'dist-tags', '--json' ],
			expect.objectContaining( {
				encoding: 'utf8',
			} )
		);
	} );

	it( 'resolves latest-1 from the previous WordPress npm dist-tag', () => {
		expect( resolveNpmDistTag( '@wordpress/data', 'latest-1' ) ).toBe(
			PREVIOUS_WORDPRESS_DIST_TAG
		);
		expect( resolveWordPressDistTagFromNpm( '@wordpress/data', 1 ) ).toBe(
			PREVIOUS_WORDPRESS_DIST_TAG
		);
	} );

	it( 'parses npm view version output', () => {
		expect(
			parseNpmViewVersion(
				`@wordpress/data@${ OLDER_WORDPRESS_DIST_TAG }`,
				`"${ OLDER_WORDPRESS_PACKAGE_VERSION }"`
			)
		).toBe( OLDER_WORDPRESS_PACKAGE_VERSION );
		expect(
			parseNpmViewVersion(
				`@wordpress/data@${ OLDER_WORDPRESS_DIST_TAG }`,
				`["${ PREVIOUS_WORDPRESS_PACKAGE_VERSION }","${ OLDER_WORDPRESS_PACKAGE_VERSION }"]`
			)
		).toBe( OLDER_WORDPRESS_PACKAGE_VERSION );
	} );

	it( 'parses npm view dist-tag output', () => {
		expect(
			parseNpmViewDistTags(
				'@wordpress/data',
				`{"latest":"${ GUTENBERG_PACKAGE_VERSION }","${ LATEST_WORDPRESS_DIST_TAG }":"${ LATEST_WORDPRESS_PACKAGE_VERSION }"}`
			)
		).toEqual( {
			latest: GUTENBERG_PACKAGE_VERSION,
			[ LATEST_WORDPRESS_DIST_TAG ]: LATEST_WORDPRESS_PACKAGE_VERSION,
		} );
	} );
} );
