/**
 * Internal dependencies
 */
import { parseCIConfig } from '../config';

describe( 'Config', () => {
	describe( 'parseCIConfig', () => {
		it( 'should parse empty config', () => {
			const parsed = parseCIConfig( {} );

			expect( parsed ).toMatchObject( {} );
		} );

		it( 'should parse lint config', () => {
			const parsed = parseCIConfig( {
				lint: {
					changes: '/src\\/.*\\.[jt]sx?$/',
					command: 'foo',
				},
			} );

			expect( parsed ).toMatchObject( {
				lint: {
					changes: [ new RegExp( '/src\\/.*\\.[jt]sx?$/' ) ],
					command: 'foo',
				},
			} );
		} );

		it( 'should parse lint config with changes array', () => {
			const parsed = parseCIConfig( {
				lint: {
					changes: [
						'/src\\/.*\\.[jt]sx?$/',
						'/test\\/.*\\.[jt]sx?$/',
					],
					command: 'foo',
				},
			} );

			expect( parsed ).toMatchObject( {
				lint: {
					changes: [
						new RegExp( '/src\\/.*\\.[jt]sx?$/' ),
						new RegExp( '/test\\/.*\\.[jt]sx?$/' ),
					],
					command: 'foo',
				},
			} );
		} );

		it( 'should parse test config', () => {
			const parsed = parseCIConfig( {
				tests: [
					{
						name: 'default',
						changes: '/src\\/.*\\.[jt]sx?$/',
						command: 'foo',
					},
				],
			} );

			expect( parsed ).toMatchObject( {
				tests: [
					{
						name: 'default',
						changes: [ new RegExp( '/src\\/.*\\.[jt]sx?$/' ) ],
						command: 'foo',
					},
				],
			} );
		} );

		it( 'should parse test config with environment', () => {
			const parsed = parseCIConfig( {
				tests: [
					{
						name: 'default',
						changes: '/src\\/.*\\.[jt]sx?$/',
						command: 'foo',
						testEnv: {
							start: 'bar',
							config: {
								wpVersion: 'latest',
							},
						},
					},
				],
			} );

			expect( parsed ).toMatchObject( {
				tests: [
					{
						name: 'default',
						changes: [ new RegExp( '/src\\/.*\\.[jt]sx?$/' ) ],
						command: 'foo',
						testEnv: {
							start: 'bar',
							config: {
								wpVersion: 'latest',
							},
						},
					},
				],
			} );
		} );

		it( 'should parse test config with cascade', () => {
			const parsed = parseCIConfig( {
				tests: [
					{
						name: 'default',
						changes: '/src\\/.*\\.[jt]sx?$/',
						command: 'foo',
						cascade: 'bar',
					},
				],
			} );

			expect( parsed ).toMatchObject( {
				tests: [
					{
						name: 'default',
						changes: [ new RegExp( '/src\\/.*\\.[jt]sx?$/' ) ],
						command: 'foo',
						cascade: [ 'bar' ],
					},
				],
			} );
		} );
	} );
} );
