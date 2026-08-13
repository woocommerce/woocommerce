'use strict';

const childProcess = require( 'node:child_process' );

jest.mock( 'node:child_process', () => ( {
	spawnSync: jest.fn(),
} ) );

const { resolvePackageVersionFromNpm } = require( '../src/npm' );

describe( 'npm registry helpers', () => {
	beforeEach( () => {
		childProcess.spawnSync.mockReset();
	} );

	it( 'throws a clear error when the requested dist-tag is missing', () => {
		childProcess.spawnSync.mockReturnValueOnce( {
			status: 0,
			stdout: JSON.stringify( {
				latest: '4.0.0',
				'wp-6.9': '3.0.0',
			} ),
			stderr: '',
		} );

		expect( () =>
			resolvePackageVersionFromNpm(
				'@wordpress/a11y',
				'latest',
				'wp-7.0'
			)
		).toThrow( 'npm did not return dist-tag wp-7.0 for @wordpress/a11y.' );
		expect( childProcess.spawnSync ).toHaveBeenCalledTimes( 1 );
		expect( childProcess.spawnSync ).toHaveBeenCalledWith(
			'npm',
			[ 'view', '@wordpress/a11y', 'dist-tags', '--json' ],
			{
				encoding: 'utf8',
				stdio: 'pipe',
			}
		);
	} );

	it( 'resolves the package version when the requested dist-tag exists', () => {
		childProcess.spawnSync
			.mockReturnValueOnce( {
				status: 0,
				stdout: JSON.stringify( {
					latest: '4.0.0',
					'wp-6.9': '3.0.0',
				} ),
				stderr: '',
			} )
			.mockReturnValueOnce( {
				status: 0,
				stdout: JSON.stringify( '3.0.0' ),
				stderr: '',
			} );

		expect(
			resolvePackageVersionFromNpm(
				'@wordpress/a11y',
				'latest',
				'wp-6.9'
			)
		).toBe( '3.0.0' );
		expect( childProcess.spawnSync ).toHaveBeenLastCalledWith(
			'npm',
			[ 'view', '@wordpress/a11y@wp-6.9', 'version', '--json' ],
			{
				encoding: 'utf8',
				stdio: 'pipe',
			}
		);
	} );
} );
