'use strict';

const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );

const { createCacheManifestPackageJson } = require( '../src/cache-manifest' );
const { getWordPressDependencyNames } = require( '../src/package-selection' );

const GUTENBERG_PLUGIN_VERSION = '23.4.0';
const GUTENBERG_PACKAGE_VERSION = '100.0.0';
const PREVIOUS_WORDPRESS_VERSION = '100.1';
const PREVIOUS_WORDPRESS_DIST_TAG = 'wp-100.1';
const PREVIOUS_WORDPRESS_PACKAGE_VERSION = '91.0.0';
const LATEST_WORDPRESS_VERSION = '101.0';
const LATEST_WORDPRESS_DIST_TAG = 'wp-101.0';
const LATEST_WORDPRESS_PACKAGE_VERSION = '92.0.0';
const LATEST_WORDPRESS_VERSION_TARGET = {
	distTag: LATEST_WORDPRESS_DIST_TAG,
	source: 'wordpress.org/core/version-check',
	version: LATEST_WORDPRESS_VERSION,
};
const PREVIOUS_WORDPRESS_VERSION_TARGET = {
	distTag: PREVIOUS_WORDPRESS_DIST_TAG,
	source: 'wordpress.org/core/version-check',
	version: PREVIOUS_WORDPRESS_VERSION,
};
const GUTENBERG_VERSION_TARGET = {
	distTag: 'latest',
	source: 'wordpress.org/plugins/info',
	version: GUTENBERG_PLUGIN_VERSION,
};
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

const { prepare } = require( '../src/cache' );

let registry;

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

function getVersionTarget( wpVersion ) {
	if ( wpVersion === 'gutenberg' ) {
		return GUTENBERG_VERSION_TARGET;
	}

	return wpVersion === 'latest-1'
		? PREVIOUS_WORDPRESS_VERSION_TARGET
		: LATEST_WORDPRESS_VERSION_TARGET;
}

function createCachePackageJson(
	wpVersion,
	packageVersions,
	selectedPackages = Object.keys( packageVersions ).sort(),
	versionTarget = getVersionTarget( wpVersion )
) {
	return createCacheManifestPackageJson( {
		packageVersions,
		selectedPackages,
		versionTarget,
		wpVersion,
	} );
}

function writeCachedPackageJson(
	cacheDirectory,
	wpVersion,
	packageVersions,
	selectedPackages,
	versionTarget
) {
	fs.mkdirSync( cacheDirectory, { recursive: true } );
	fs.writeFileSync(
		path.join( cacheDirectory, 'package.json' ),
		JSON.stringify(
			createCachePackageJson(
				wpVersion,
				packageVersions,
				selectedPackages,
				versionTarget
			),
			null,
			2
		)
	);
}

function expectCachePackageJson(
	cacheDirectory,
	wpVersion,
	packageVersions,
	selectedPackages,
	versionTarget
) {
	expect( readCachePackageJson( cacheDirectory ) ).toEqual(
		createCachePackageJson(
			wpVersion,
			packageVersions,
			selectedPackages,
			versionTarget
		)
	);
}

function getMockPackageVersion( distTag ) {
	if ( distTag === 'latest' ) {
		return GUTENBERG_PACKAGE_VERSION;
	}

	if ( distTag === PREVIOUS_WORDPRESS_DIST_TAG ) {
		return PREVIOUS_WORDPRESS_PACKAGE_VERSION;
	}

	return LATEST_WORDPRESS_PACKAGE_VERSION;
}

function resolveMockPackageVersions( packages, versionTarget ) {
	const resolvedPackages = new Set( packages );
	const packageQueue = [ ...packages ];

	for ( let index = 0; index < packageQueue.length; index++ ) {
		const packageName = packageQueue[ index ];

		for ( const dependencyName of getWordPressDependencyNames(
			mockPackageDependencies[ packageName ] || {}
		) ) {
			if ( resolvedPackages.has( dependencyName ) ) {
				continue;
			}

			resolvedPackages.add( dependencyName );
			packageQueue.push( dependencyName );
		}
	}

	return [ ...resolvedPackages ].sort().reduce( ( versions, packageName ) => {
		versions[ packageName ] = getMockPackageVersion(
			versionTarget.distTag
		);

		return versions;
	}, {} );
}

function createMockRegistry() {
	return {
		install: jest.fn(),
		resolvePackageVersions: jest.fn( ( { packages, versionTarget } ) =>
			resolveMockPackageVersions( packages, versionTarget )
		),
		resolveTarget: jest.fn( getVersionTarget ),
	};
}

function prepareWithRegistry( options ) {
	return prepare( {
		registry,
		...options,
	} );
}

describe( 'cache', () => {
	beforeEach( () => {
		registry = createMockRegistry();
	} );

	it( 'selects catalog WordPress dependencies and ignores bundled packages', () => {
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

		const result = prepareWithRegistry( {
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

		const result = prepareWithRegistry( {
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

		const result = prepareWithRegistry( {
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

	it( 'skips external resolution when no packages are selected', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/icons': 'catalog:wp-bundled',
			},
		} );

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot: path.join( cwd, '.cache' ),
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.packages ).toEqual( [] );
		expect( result.cachePackages ).toEqual( [] );
		expect( result.installedPackages ).toEqual( [] );
		expect( registry.resolveTarget ).not.toHaveBeenCalled();
		expect( registry.resolvePackageVersions ).not.toHaveBeenCalled();
		expect( registry.install ).not.toHaveBeenCalled();
	} );

	it( 'installs selected packages and their transitive WordPress dependencies', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/components': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );

		const result = prepareWithRegistry( {
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
		expectCachePackageJson(
			result.cacheDirectory,
			'latest-1',
			{
				'@wordpress/components': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/compose': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/element': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/private-apis': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
			},
			[ '@wordpress/components' ]
		);
		expect( registry.resolveTarget ).toHaveBeenCalledWith( 'latest-1' );
		expect( registry.resolvePackageVersions ).toHaveBeenCalledWith( {
			packages: [ '@wordpress/components' ],
			versionTarget: PREVIOUS_WORDPRESS_VERSION_TARGET,
			wpVersion: 'latest-1',
		} );
		expect( registry.install ).toHaveBeenCalledWith(
			path.join( cacheRoot, 'latest-1' )
		);
	} );

	it( 'skips npm resolution and install when cache metadata and packages match', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'latest' );
		const packageVersions = {
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
		};

		writeCachedPackageJson( cacheDirectory, 'latest', packageVersions, [
			'@wordpress/data',
		] );

		createInstalledPackage(
			cacheDirectory,
			'@wordpress/data',
			packageVersions[ '@wordpress/data' ]
		);
		createInstalledPackage(
			cacheDirectory,
			'@wordpress/private-apis',
			packageVersions[ '@wordpress/private-apis' ]
		);

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.cachePackages ).toEqual( [
			'@wordpress/data',
			'@wordpress/private-apis',
		] );
		expect( result.installedPackages ).toEqual( [] );
		expect( registry.resolveTarget ).toHaveBeenCalledTimes( 1 );
		expect( registry.resolveTarget ).toHaveBeenCalledWith( 'latest' );
		expect( registry.resolvePackageVersions ).not.toHaveBeenCalled();
		expect( registry.install ).not.toHaveBeenCalled();
	} );

	it( 'installs missing packages from current cache metadata without npm resolution', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'latest' );
		const packageVersions = {
			'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
			'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
		};

		writeCachedPackageJson( cacheDirectory, 'latest', packageVersions, [
			'@wordpress/data',
		] );
		createInstalledPackage(
			cacheDirectory,
			'@wordpress/data',
			packageVersions[ '@wordpress/data' ]
		);

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.installedPackages ).toEqual( [
			'@wordpress/private-apis',
		] );
		expect( registry.resolvePackageVersions ).not.toHaveBeenCalled();
		expect( registry.install ).toHaveBeenCalledWith( cacheDirectory );
	} );

	it( 'refreshes cached packages when the installed package version is stale', () => {
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

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( result.installedPackages ).toEqual( [
			'@wordpress/data',
			'@wordpress/private-apis',
		] );
		expectCachePackageJson(
			result.cacheDirectory,
			'latest',
			{
				'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
			},
			[ '@wordpress/data' ]
		);
		expect( registry.install ).toHaveBeenCalledWith( cacheDirectory );
	} );

	it( 'refreshes cached package metadata when the WordPress version marker changes', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'latest' );

		writeCachedPackageJson(
			cacheDirectory,
			'latest',
			{
				'@wordpress/data': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/private-apis': PREVIOUS_WORDPRESS_PACKAGE_VERSION,
			},
			[ '@wordpress/data' ],
			{
				...LATEST_WORDPRESS_VERSION_TARGET,
				version: '100.0',
				distTag: 'wp-100.0',
			}
		);

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( registry.resolvePackageVersions ).toHaveBeenCalled();
		expectCachePackageJson(
			result.cacheDirectory,
			'latest',
			{
				'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
			},
			[ '@wordpress/data' ]
		);
		expect( registry.install ).toHaveBeenCalledWith( cacheDirectory );
	} );

	it( 'refreshes cached package metadata when the Gutenberg version marker changes', () => {
		const cwd = createFixtureProject( {} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'gutenberg' );

		writeCachedPackageJson(
			cacheDirectory,
			'gutenberg',
			{
				'@wordpress/data': '99.0.0',
				'@wordpress/private-apis': '99.0.0',
			},
			[ '@wordpress/data' ],
			{
				...GUTENBERG_VERSION_TARGET,
				version: '23.3.0',
			}
		);

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot,
			wpVersion: 'gutenberg',
			packages: [ '@wordpress/data' ],
			logger: false,
		} );

		expect( registry.resolvePackageVersions ).toHaveBeenCalledWith( {
			packages: [ '@wordpress/data' ],
			versionTarget: GUTENBERG_VERSION_TARGET,
			wpVersion: 'gutenberg',
		} );
		expectCachePackageJson(
			result.cacheDirectory,
			'gutenberg',
			{
				'@wordpress/data': GUTENBERG_PACKAGE_VERSION,
				'@wordpress/private-apis': GUTENBERG_PACKAGE_VERSION,
			},
			[ '@wordpress/data' ]
		);
		expect( registry.install ).toHaveBeenCalledWith( cacheDirectory );
	} );

	it( 'refreshes cached package metadata when the selected packages change', () => {
		const cwd = createFixtureProject( {
			dependencies: {
				'@wordpress/components': 'catalog:wp-min',
				'@wordpress/data': 'catalog:wp-min',
			},
		} );
		const cacheRoot = path.join( cwd, '.cache' );
		const cacheDirectory = path.join( cacheRoot, 'latest' );

		writeCachedPackageJson(
			cacheDirectory,
			'latest',
			{
				'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
			},
			[ '@wordpress/data' ]
		);

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot,
			wpVersion: 'latest',
			logger: false,
		} );

		expect( registry.resolvePackageVersions ).toHaveBeenCalled();
		expect( result.cachePackages ).toEqual( [
			'@wordpress/components',
			'@wordpress/compose',
			'@wordpress/data',
			'@wordpress/element',
			'@wordpress/private-apis',
		] );
		expectCachePackageJson(
			result.cacheDirectory,
			'latest',
			{
				'@wordpress/components': LATEST_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/compose': LATEST_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/data': LATEST_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/element': LATEST_WORDPRESS_PACKAGE_VERSION,
				'@wordpress/private-apis': LATEST_WORDPRESS_PACKAGE_VERSION,
			},
			[ '@wordpress/components', '@wordpress/data' ]
		);
		expect( registry.install ).toHaveBeenCalledWith( cacheDirectory );
	} );

	it( 'uses the npm latest dist-tag for gutenberg', () => {
		const cwd = createFixtureProject( {} );
		const cacheRoot = path.join( cwd, '.cache' );

		const result = prepareWithRegistry( {
			cwd,
			cacheRoot,
			wpVersion: 'gutenberg',
			packages: [ '@wordpress/data' ],
			logger: false,
		} );

		expect( registry.resolvePackageVersions ).toHaveBeenCalledWith( {
			packages: [ '@wordpress/data' ],
			versionTarget: GUTENBERG_VERSION_TARGET,
			wpVersion: 'gutenberg',
		} );
		expectCachePackageJson(
			result.cacheDirectory,
			'gutenberg',
			{
				'@wordpress/data': GUTENBERG_PACKAGE_VERSION,
				'@wordpress/private-apis': GUTENBERG_PACKAGE_VERSION,
			},
			[ '@wordpress/data' ]
		);
		expect( registry.install ).toHaveBeenCalledWith(
			path.join( cacheRoot, 'gutenberg' )
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
				prepareWithRegistry( {
					cwd,
					wpVersion,
					logger: false,
				} )
			).toThrow( /Unsupported WordPress version/ );
		}

		expect( registry.resolveTarget ).not.toHaveBeenCalled();
	} );
} );
