import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import fs from 'node:fs';

/**
 * Internal dependencies
 */
import { loadPackage } from '../package-file';

vi.mock( 'node:fs' );

describe( 'Package File', () => {
	describe( 'loadPackage', () => {
		it( "should throw for file that doesn't exist", () => {
			vi.mocked( fs.readFileSync ).mockImplementation( ( path ) => {
				if ( path === 'foo' ) {
					throw new Error( 'ENOENT' );
				}

				return '';
			} );

			expect( () => loadPackage( 'foo' ) ).toThrow( 'ENOENT' );
		} );

		it( 'should load package.json', () => {
			vi.mocked( fs.readFileSync ).mockImplementationOnce( ( path ) => {
				if ( path === __dirname + '/test-package.json' ) {
					return JSON.stringify( {
						name: 'foo',
					} );
				}

				throw new Error( 'ENOENT' );
			} );

			const loadedFile = loadPackage( __dirname + '/test-package.json' );

			expect( loadedFile ).toMatchObject( {
				name: 'foo',
			} );
		} );

		it( 'should cache using normalized paths', () => {
			vi.mocked( fs.readFileSync ).mockImplementationOnce( ( path ) => {
				if ( path === __dirname + '/test-package.json' ) {
					return JSON.stringify( {
						name: 'foo',
					} );
				}

				throw new Error( 'ENOENT' );
			} );
			loadPackage( __dirname + '/test-package.json' );

			// Just throw if it's called again so that we can make sure we're using the cache.
			vi.mocked( fs.readFileSync ).mockImplementationOnce( () => {
				throw new Error( 'ENOENT' );
			} );

			const cachedFile = loadPackage(
				// Use a token that needs to be normalized to match the cached path.
				__dirname + '/./test-package.json'
			);

			expect( cachedFile ).toMatchObject( {
				name: 'foo',
			} );
		} );
	} );
} );
