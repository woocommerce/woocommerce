/**
 * Internal dependencies
 */
import { JobType, parseCIConfig } from '../config';

describe( 'Config', () => {
	describe( 'parseCIConfig', () => {
		it( 'should parse empty config', () => {
			const parsed = parseCIConfig( { name: 'foo', config: {} } );

			expect( parsed ).toMatchObject( {} );
		} );

		it( 'should parse lint config', () => {
			const parsed = parseCIConfig( {
				name: 'foo',
				config: {
					ci: {
						lint: {
							changes: '/src\\/.*\\.[jt]sx?$/',
							command: 'foo',
						},
					},
				},
			} );

			expect( parsed ).toMatchObject( {
				jobs: [
					{
						type: JobType.Lint,
						changes: [ new RegExp( '/src\\/.*\\.[jt]sx?$/' ) ],
						command: 'foo',
					},
				],
			} );
		} );

		it( 'should parse lint config with changes array', () => {
			const parsed = parseCIConfig( {
				name: 'foo',
				config: {
					ci: {
						lint: {
							changes: [
								'/src\\/.*\\.[jt]sx?$/',
								'/test\\/.*\\.[jt]sx?$/',
							],
							command: 'foo',
						},
					},
				},
			} );

			expect( parsed ).toMatchObject( {
				jobs: [
					{
						type: JobType.Lint,
						changes: [
							new RegExp( '/src\\/.*\\.[jt]sx?$/' ),
							new RegExp( '/test\\/.*\\.[jt]sx?$/' ),
						],
						command: 'foo',
					},
				],
			} );
		} );

		it( 'should parse test config', () => {
			const parsed = parseCIConfig( {
				name: 'foo',
				config: {
					ci: {
						tests: [
							{
								name: 'default',
								changes: '/src\\/.*\\.[jt]sx?$/',
								command: 'foo',
							},
						],
					},
				},
			} );

			expect( parsed ).toMatchObject( {
				jobs: [
					{
						type: JobType.Test,
						name: 'default',
						changes: [ new RegExp( '/src\\/.*\\.[jt]sx?$/' ) ],
						command: 'foo',
					},
				],
			} );
		} );

		it( 'should parse test config with environment', () => {
			const parsed = parseCIConfig( {
				name: 'foo',
				config: {
					ci: {
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
					},
				},
			} );

			expect( parsed ).toMatchObject( {
				jobs: [
					{
						type: JobType.Test,
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
				name: 'foo',
				config: {
					ci: {
						tests: [
							{
								name: 'default',
								changes: '/src\\/.*\\.[jt]sx?$/',
								command: 'foo',
								cascade: 'bar',
							},
						],
					},
				},
			} );

			expect( parsed ).toMatchObject( {
				jobs: [
					{
						type: JobType.Test,
						name: 'default',
						changes: [ new RegExp( '/src\\/.*\\.[jt]sx?$/' ) ],
						command: 'foo',
						cascadeKeys: [ 'bar' ],
					},
				],
			} );
		} );
	} );
} );
