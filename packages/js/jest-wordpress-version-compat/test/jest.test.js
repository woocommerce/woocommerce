'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const cache = require( '../src/cache' );

jest.mock( '../src/cache', () => ( {
	prepare: jest.fn(),
} ) );

const packageRoot = require( '../src' );
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

	it( 'exports only the public Jest helper', () => {
		expect( packageRoot ).toEqual( {
			withWordPressDependencyCompat,
		} );
	} );

	it( 'maps cached WordPress packages and React singletons into Jest config', () => {
		mockPreparedCache( {
			'@wordpress/data': '/tmp/cache/latest/node_modules/@wordpress/data',
		} );

		const config = withWordPressDependencyCompat(
			{
				moduleNameMapper: {
					'^existing$': '/existing',
					'^react$': '/installed/react',
				},
			},
			{
				cacheRoot: '/tmp/cache',
				packages: [ '@wordpress/data' ],
				wpVersion: 'latest',
			}
		);
		const reactSubpathPattern = Object.keys( config.moduleNameMapper ).find(
			( pattern ) => pattern.startsWith( '^react/' )
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
		expect(
			'react/jsx-runtime'.replace(
				new RegExp( reactSubpathPattern ),
				config.moduleNameMapper[ reactSubpathPattern ]
			)
		).toBe( '/tmp/cache/latest/node_modules/react/jsx-runtime.js' );
		expect( cache.prepare ).toHaveBeenCalledWith( {
			cacheRoot: '/tmp/cache',
			packages: [ '@wordpress/data' ],
			wpVersion: 'latest',
		} );
	} );

	it( 'skips singleton mappings when singleton packages are not cached', () => {
		fs.existsSync.mockReturnValue( false );
		mockPreparedCache( {
			'@wordpress/data': '/tmp/cache/latest/node_modules/@wordpress/data',
		} );

		const config = withWordPressDependencyCompat();

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

		expect( config.transform ).toMatchObject( {
			'node_modules/.cache/jest-wordpress-version-compat/latest/node_modules/parsel-js/.*\\.js$':
				require.resolve( 'babel-jest', {
					paths: [
						path.resolve( __dirname, '../../internal-js-tests' ),
					],
				} ),
			'node_modules/parsel-js/.*\\.js$': 'babel-jest',
			'src/.*\\.js$': 'babel-jest',
		} );
		expect( config.transformIgnorePatterns ).toEqual( [
			'node_modules/(?!(?:\\.cache/jest-wordpress-version-compat/latest/node_modules/(?:parsel-js)|\\.pnpm|parsel-js)/)',
		] );
	} );
} );
