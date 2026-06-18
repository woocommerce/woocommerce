'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const cache = require( '../cache' );

jest.mock( '../cache', () => ( {
	prepare: jest.fn(),
} ) );

const packageRoot = require( '../index' );
const { withWordPressDependencyCompat } = packageRoot;

function mockPreparedCache( packagePaths = {} ) {
	cache.prepare.mockReturnValue( {
		cacheDirectory: '/tmp/cache/latest',
		packagePaths,
	} );
}

describe( 'jest adapter', () => {
	beforeEach( () => {
		cache.prepare.mockReset();
		jest.spyOn( fs, 'existsSync' ).mockReturnValue( true );
	} );

	afterEach( () => {
		fs.existsSync.mockRestore();
	} );

	it( 'merges generated mappings into Jest config', () => {
		mockPreparedCache( {
			'@wordpress/data': '/tmp/cache/latest/node_modules/@wordpress/data',
		} );

		const config = withWordPressDependencyCompat(
			{
				moduleNameMapper: {
					'^existing$': '/existing',
				},
			},
			{
				cacheRoot: '/tmp/cache',
				packages: [ '@wordpress/data' ],
				wpVersion: 'latest',
			}
		);

		expect( config.moduleNameMapper ).toEqual( {
			'^existing$': '/existing',
			'^@wordpress/data$':
				'/tmp/cache/latest/node_modules/@wordpress/data',
			'^react$': '/tmp/cache/latest/node_modules/react',
			'^react/(.*?)(?:\\.js)?$':
				'/tmp/cache/latest/node_modules/react/$1.js',
			'^react-dom$': '/tmp/cache/latest/node_modules/react-dom',
			'^react-dom/(.*?)(?:\\.js)?$':
				'/tmp/cache/latest/node_modules/react-dom/$1.js',
		} );
		expect( cache.prepare ).toHaveBeenCalledWith( {
			cacheRoot: '/tmp/cache',
			packages: [ '@wordpress/data' ],
			wpVersion: 'latest',
		} );
	} );

	it( 'keeps singleton dependencies mapped to the compatibility cache', () => {
		cache.prepare.mockReturnValue( {
			cacheDirectory: '/tmp/cache/gutenberg',
			packagePaths: {
				'@wordpress/element':
					'/tmp/cache/gutenberg/node_modules/@wordpress/element',
			},
		} );

		const config = withWordPressDependencyCompat(
			{
				moduleNameMapper: {
					'^react$': '/installed/react',
					'^react/(.*)$': '/installed/react/$1',
				},
			},
			{
				cacheRoot: '/tmp/cache',
				packages: [ '@wordpress/element' ],
				wpVersion: 'gutenberg',
			}
		);

		expect( config.moduleNameMapper ).toEqual( {
			'^react$': '/tmp/cache/gutenberg/node_modules/react',
			'^react/(.*?)(?:\\.js)?$':
				'/tmp/cache/gutenberg/node_modules/react/$1.js',
			'^@wordpress/element$':
				'/tmp/cache/gutenberg/node_modules/@wordpress/element',
			'^react-dom$': '/tmp/cache/gutenberg/node_modules/react-dom',
			'^react-dom/(.*?)(?:\\.js)?$':
				'/tmp/cache/gutenberg/node_modules/react-dom/$1.js',
		} );
	} );

	it( 'maps singleton package subpath exports to JavaScript files', () => {
		mockPreparedCache( {
			'@wordpress/element':
				'/tmp/cache/latest/node_modules/@wordpress/element',
		} );

		const config = withWordPressDependencyCompat(
			{},
			{
				cacheRoot: '/tmp/cache',
				packages: [ '@wordpress/element' ],
				wpVersion: 'latest',
			}
		);
		const reactSubpathPattern = Object.keys( config.moduleNameMapper ).find(
			( pattern ) => pattern.startsWith( '^react/' )
		);
		const reactSubpathReplacement =
			config.moduleNameMapper[ reactSubpathPattern ];

		expect(
			'react/jsx-runtime'.replace(
				new RegExp( reactSubpathPattern ),
				reactSubpathReplacement
			)
		).toBe( '/tmp/cache/latest/node_modules/react/jsx-runtime.js' );
		expect(
			'react/jsx-runtime.js'.replace(
				new RegExp( reactSubpathPattern ),
				reactSubpathReplacement
			)
		).toBe( '/tmp/cache/latest/node_modules/react/jsx-runtime.js' );
	} );

	it( 'skips singleton mappings when singleton packages are not cached', () => {
		fs.existsSync.mockReturnValue( false );
		mockPreparedCache( {
			'@wordpress/data': '/tmp/cache/latest/node_modules/@wordpress/data',
		} );

		const config = withWordPressDependencyCompat(
			{},
			{
				cacheRoot: '/tmp/cache',
				packages: [ '@wordpress/data' ],
				wpVersion: 'latest',
			}
		);

		expect( config.moduleNameMapper ).toEqual( {
			'^@wordpress/data$':
				'/tmp/cache/latest/node_modules/@wordpress/data',
		} );
	} );

	it( 'mirrors transformed node modules into the compatibility cache', () => {
		fs.existsSync.mockImplementation( ( filePath ) =>
			filePath.endsWith( 'parsel-js/package.json' )
		);
		mockPreparedCache( {
			'@wordpress/block-editor':
				'/tmp/cache/latest-1/node_modules/@wordpress/block-editor',
		} );

		const config = withWordPressDependencyCompat(
			{
				transformIgnorePatterns: [
					'node_modules/(?!(?:\\.pnpm|parsel-js)/)',
				],
				transform: {
					'node_modules/parsel-js/.*\\.js$': 'babel-jest',
					'src/.*\\.js$': 'babel-jest',
				},
			},
			{
				cacheRoot: '/tmp/cache',
				packages: [ '@wordpress/block-editor' ],
				wpVersion: 'latest-1',
			}
		);

		expect( config.transformIgnorePatterns ).toEqual( [
			'node_modules/(?!(?:\\.cache/jest-wordpress-version-compat/latest/node_modules/(?:parsel-js)|\\.pnpm|parsel-js)/)',
		] );
		expect( config.transform ).toMatchObject( {
			'node_modules/parsel-js/.*\\.js$': 'babel-jest',
			'src/.*\\.js$': 'babel-jest',
		} );
		expect( Object.keys( config.transform ).slice( 0, 2 ) ).toEqual( [
			'node_modules/.cache/jest-wordpress-version-compat/latest/node_modules/parsel-js/.*\\.js$',
			'node_modules/parsel-js/.*\\.js$',
		] );
		expect(
			config.transform[
				'node_modules/.cache/jest-wordpress-version-compat/latest/node_modules/parsel-js/.*\\.js$'
			]
		).toBe(
			require.resolve( 'babel-jest', {
				paths: [
					path.resolve( __dirname, '../../../internal-js-tests' ),
				],
			} )
		);
	} );

	it( 'keeps the package root export limited to public Jest helpers', () => {
		expect( packageRoot ).toEqual( {
			withWordPressDependencyCompat,
		} );
	} );
} );
