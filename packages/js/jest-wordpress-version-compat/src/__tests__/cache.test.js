'use strict';

const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { spawnSync } = require( 'node:child_process' );

jest.mock( 'node:child_process', () => ( {
	spawnSync: jest.fn( ( _command, args ) => {
		if ( args[ 0 ] === 'view' && args[ 2 ] === 'dist-tags' ) {
			return {
				status: 0,
				stdout: JSON.stringify( {
					latest: '13.0.0',
					'wp-6.8': '10.19.2',
					'wp-6.9': '11.0.0',
					'wp-7.0': '12.0.0',
				} ),
				stderr: '',
			};
		}

		if ( args[ 0 ] === 'view' ) {
			let version = '13.0.0';

			if ( args[ 1 ].includes( 'wp-6.8' ) ) {
				version = '10.19.2';
			} else if ( args[ 1 ].includes( 'wp-6.9' ) ) {
				version = '11.0.0';
			} else if ( args[ 1 ].includes( 'wp-7.0' ) ) {
				version = '12.0.0';
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
				'@wordpress/icons': 'catalog:wp-min',
				'@woocommerce/data': 'workspace:*',
			},
			devDependencies: {
				'@wordpress/components': 'catalog:wp-min',
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
				'@wordpress/icons': 'catalog:wp-min',
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
			expect.arrayContaining( [ '@wordpress/icons@11.0.0' ] ),
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
				'@wordpress/data@12.0.0',
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
				'@wordpress/data': 'wp-7.0',
			},
			'@wordpress/data': '12.0.0',
		} );
	} );

	it( 'does not require a cache entry when the installed package matches the target version', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );

		createInstalledPackage( cwd, '@wordpress/data', '12.0.0' );

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
					'@wordpress/data': '11.0.0',
				},
				null,
				2
			)
		);

		expect(
			toPackageSpec( '@wordpress/data', 'latest', {
				cacheDirectory,
			} )
		).toBe( '@wordpress/data@12.0.0' );
		expect(
			JSON.parse(
				fs.readFileSync(
					path.join( cacheDirectory, 'resolved-versions.json' ),
					'utf8'
				)
			)
		).toEqual( {
			__distTags: {
				'@wordpress/data': 'wp-7.0',
			},
			'@wordpress/data': '12.0.0',
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
			'@wordpress/data@12.0.0'
		);
		expect( toPackageSpec( '@wordpress/data', 'latest-1' ) ).toBe(
			'@wordpress/data@11.0.0'
		);
		expect( toPackageSpec( '@wordpress/data', 'gutenberg' ) ).toBe(
			'@wordpress/data@13.0.0'
		);
		expect( toPackageSpec( '@wordpress/components', 'gutenberg' ) ).toBe(
			'@wordpress/components@13.0.0'
		);
	} );

	it( 'resolves package versions from npm dist-tags', () => {
		expect(
			resolvePackageVersionFromNpm( '@wordpress/data', 'gutenberg' )
		).toBe( '13.0.0' );
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
			'wp-7.0'
		);
		expect( resolveWordPressDistTagFromNpm( '@wordpress/data' ) ).toBe(
			'wp-7.0'
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
			'wp-6.9'
		);
		expect( resolveWordPressDistTagFromNpm( '@wordpress/data', 1 ) ).toBe(
			'wp-6.9'
		);
	} );

	it( 'parses npm view version output', () => {
		expect(
			parseNpmViewVersion( '@wordpress/data@wp-6.8', '"10.19.2"' )
		).toBe( '10.19.2' );
		expect(
			parseNpmViewVersion(
				'@wordpress/data@wp-6.8',
				'["10.19.1","10.19.2"]'
			)
		).toBe( '10.19.2' );
	} );

	it( 'parses npm view dist-tag output', () => {
		expect(
			parseNpmViewDistTags(
				'@wordpress/data',
				'{"latest":"13.0.0","wp-7.0":"12.0.0"}'
			)
		).toEqual( {
			latest: '13.0.0',
			'wp-7.0': '12.0.0',
		} );
	} );
} );
