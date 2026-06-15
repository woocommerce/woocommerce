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
		} );
	} );
} );
