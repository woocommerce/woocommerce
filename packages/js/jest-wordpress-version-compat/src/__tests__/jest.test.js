'use strict';

const cache = require( '../cache' );

jest.mock( '../cache', () => {
	const actual = jest.requireActual( '../cache' );

	return {
		...actual,
		prepare: jest.fn(),
	};
} );

const {
	createJestModuleNameMapper,
	withWordPressDependencyCompat,
} = require( '../jest' );
const packageRoot = require( '../index' );
const { getWordPressVersionTarget, isWordPressVersionTarget } = packageRoot;

describe( 'jest adapter', () => {
	beforeEach( () => {
		cache.prepare.mockClear();
	} );

	it( 'creates moduleNameMapper entries for selected packages', () => {
		const mapper = createJestModuleNameMapper( {
			cacheRoot: '/tmp/cache',
			lazy: false,
			packages: [ '@wordpress/data' ],
			wpVersion: 'latest',
		} );

		expect( mapper ).toEqual( {
			'^@wordpress/data$':
				'/tmp/cache/latest/node_modules/@wordpress/data',
			'^react$': '/tmp/cache/latest/node_modules/react',
			'^react/(.*)$': '/tmp/cache/latest/node_modules/react/$1',
			'^react-dom$': '/tmp/cache/latest/node_modules/react-dom',
			'^react-dom/(.*)$': '/tmp/cache/latest/node_modules/react-dom/$1',
		} );
		expect( cache.prepare ).not.toHaveBeenCalled();
	} );

	it( 'prepares packages lazily by default', () => {
		createJestModuleNameMapper( {
			cacheRoot: '/tmp/cache',
			packages: [ '@wordpress/data' ],
			wpVersion: 'latest',
		} );

		expect( cache.prepare ).toHaveBeenCalledWith(
			expect.objectContaining( {
				packages: [ '@wordpress/data' ],
				wpVersion: 'latest',
			} )
		);
	} );

	it( 'merges generated mappings into Jest config', () => {
		const config = withWordPressDependencyCompat(
			{
				moduleNameMapper: {
					'^existing$': '/existing',
				},
			},
			{
				cacheRoot: '/tmp/cache',
				lazy: false,
				packages: [ '@wordpress/data' ],
				wpVersion: 'latest',
			}
		);

		expect( config.moduleNameMapper ).toEqual( {
			'^existing$': '/existing',
			'^@wordpress/data$':
				'/tmp/cache/latest/node_modules/@wordpress/data',
			'^react$': '/tmp/cache/latest/node_modules/react',
			'^react/(.*)$': '/tmp/cache/latest/node_modules/react/$1',
			'^react-dom$': '/tmp/cache/latest/node_modules/react-dom',
			'^react-dom/(.*)$': '/tmp/cache/latest/node_modules/react-dom/$1',
		} );
	} );

	it( 'keeps singleton dependencies mapped to the compatibility cache', () => {
		const config = withWordPressDependencyCompat(
			{
				moduleNameMapper: {
					'^react$': '/installed/react',
					'^react/(.*)$': '/installed/react/$1',
				},
			},
			{
				cacheRoot: '/tmp/cache',
				lazy: false,
				packages: [ '@wordpress/element' ],
				wpVersion: 'gutenberg',
			}
		);

		expect( config.moduleNameMapper ).toEqual( {
			'^react$': '/tmp/cache/gutenberg/node_modules/react',
			'^react/(.*)$': '/tmp/cache/gutenberg/node_modules/react/$1',
			'^@wordpress/element$':
				'/tmp/cache/gutenberg/node_modules/@wordpress/element',
			'^react-dom$': '/tmp/cache/gutenberg/node_modules/react-dom',
			'^react-dom/(.*)$':
				'/tmp/cache/gutenberg/node_modules/react-dom/$1',
		} );
	} );

	it( 'keeps the package root export limited to public Jest helpers', () => {
		expect( packageRoot ).toEqual( {
			getWordPressVersionTarget,
			isWordPressVersionTarget,
			withWordPressDependencyCompat,
		} );
	} );

	it( 'reads the selected WordPress version target from the environment', () => {
		expect(
			getWordPressVersionTarget( {
				WP_VERSION: 'latest',
			} )
		).toBe( 'latest' );
		expect(
			getWordPressVersionTarget( {
				WP_VERSION: 'latest-1',
			} )
		).toBe( 'latest-1' );
		expect(
			getWordPressVersionTarget( {
				WP_VERSION: 'gutenberg',
			} )
		).toBe( 'gutenberg' );
		expect( getWordPressVersionTarget( {} ) ).toBeUndefined();
		expect( () =>
			getWordPressVersionTarget( {
				WP_VERSION: 'nightly',
			} )
		).toThrow( /Unsupported WordPress version/ );
	} );

	it( 'checks whether the selected WordPress version matches a target', () => {
		expect(
			isWordPressVersionTarget( 'latest', {
				WP_VERSION: 'latest',
			} )
		).toBe( true );
		expect(
			isWordPressVersionTarget( [ 'latest', 'latest-1' ], {
				WP_VERSION: 'latest-1',
			} )
		).toBe( true );
		expect(
			isWordPressVersionTarget( 'gutenberg', {
				WP_VERSION: 'latest',
			} )
		).toBe( false );
		expect( isWordPressVersionTarget( 'latest', {} ) ).toBe( false );
		expect( () =>
			isWordPressVersionTarget( 'nightly', {
				WP_VERSION: 'latest',
			} )
		).toThrow( /Unsupported WordPress version/ );
	} );
} );
