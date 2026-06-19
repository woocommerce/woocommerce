'use strict';

const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );

const GUTENBERG_PACKAGE_VERSION = '100.0.0';
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

jest.mock( '../src/npm', () => ( {
	installPackages: jest.fn(),
	resolvePackageDependenciesFromNpm: jest.fn(),
	resolvePackageVersionFromNpm: jest.fn(),
	resolveWordPressDistTagFromNpm: jest.fn(),
} ) );

const { prepare } = require( '../src/cache' );
const {
	installPackages,
	resolvePackageDependenciesFromNpm,
	resolvePackageVersionFromNpm,
	resolveWordPressDistTagFromNpm,
} = require( '../src/npm' );

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

function mockNpmPackageResolution() {
	installPackages.mockReset();
	resolveWordPressDistTagFromNpm.mockReset();
	resolvePackageVersionFromNpm.mockReset();
	resolvePackageDependenciesFromNpm.mockReset();

	resolveWordPressDistTagFromNpm.mockImplementation(
		( _packageName, offset ) =>
			offset === 1
				? PREVIOUS_WORDPRESS_DIST_TAG
				: LATEST_WORDPRESS_DIST_TAG
	);
	resolvePackageVersionFromNpm.mockImplementation(
		( _packageName, _wpVersion, distTag ) => {
			if ( distTag === 'latest' ) {
				return GUTENBERG_PACKAGE_VERSION;
			}

			if ( distTag === PREVIOUS_WORDPRESS_DIST_TAG ) {
				return PREVIOUS_WORDPRESS_PACKAGE_VERSION;
			}

			return LATEST_WORDPRESS_PACKAGE_VERSION;
		}
	);
	resolvePackageDependenciesFromNpm.mockImplementation(
		( packageName ) => mockPackageDependencies[ packageName ] || {}
	);
}

describe( 'cache', () => {
	beforeEach( () => {
		mockNpmPackageResolution();
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

	it( 'installs selected packages and their transitive WordPress dependencies', () => {
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
		expect( resolveWordPressDistTagFromNpm ).toHaveBeenCalledWith(
			'@wordpress/components',
			1
		);
		expect( installPackages ).toHaveBeenCalledWith(
			path.join( cacheRoot, 'latest-1' )
		);
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
		expect( installPackages ).not.toHaveBeenCalled();
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
		expect( installPackages ).toHaveBeenCalledWith( cacheDirectory );
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

		expect( resolvePackageVersionFromNpm ).toHaveBeenCalledWith(
			'@wordpress/data',
			'gutenberg',
			'latest'
		);
		expectCachePackageJson( result.cacheDirectory, 'gutenberg', {
			'@wordpress/data': GUTENBERG_PACKAGE_VERSION,
			'@wordpress/private-apis': GUTENBERG_PACKAGE_VERSION,
		} );
		expect( installPackages ).toHaveBeenCalledWith(
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
				prepare( {
					cwd,
					wpVersion,
					logger: false,
				} )
			).toThrow( /Unsupported WordPress version/ );
		}
	} );
} );
