/**
 * External dependencies
 */
import { makeRe } from 'minimatch';

/**
 * Internal dependencies
 */
import { JobType, parseCIConfig, testTypes } from '../config';

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
							changes: '/src/**/*.{js,jsx,ts,tsx}',
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
							/^package\.json$/,
							makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
						],
						command: 'foo',
					},
				],
			} );
		} );

		it( 'should validate lint command vars', () => {
			const parsed = parseCIConfig( {
				name: 'foo',
				config: {
					ci: {
						lint: {
							changes: '/src/**/*.{js,jsx,ts,tsx}',
							command: 'foo <baseRef>',
						},
					},
				},
			} );

			expect( parsed ).toMatchObject( {
				jobs: [
					{
						type: JobType.Lint,
						changes: [
							/^package\.json$/,
							makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
						],
						command: 'foo <baseRef>',
					},
				],
			} );

			const expectation = () => {
				parseCIConfig( {
					name: 'foo',
					config: {
						ci: {
							lint: {
								changes: '/src/**/*.{js,jsx,ts,tsx}',
								command: 'foo <invalid>',
							},
						},
					},
				} );
			};
			expect( expectation ).toThrow();
		} );

		it( 'should parse lint config with changes array', () => {
			const parsed = parseCIConfig( {
				name: 'foo',
				config: {
					ci: {
						lint: {
							changes: [
								'/src/**/*.{js,jsx,ts,tsx}',
								'/test/**/*.{js,jsx,ts,tsx}',
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
							/^package\.json$/,
							makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
							makeRe( '/test/**/*.{js,jsx,ts,tsx}' ),
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
								changes: '/src/**/*.{js,jsx,ts,tsx}',
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
						testType: 'default',
						shardingArguments: [],
						name: 'default',
						changes: [
							/^package\.json$/,
							makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
						],
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
								changes: '/src/**/*.{js,jsx,ts,tsx}',
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
						changes: [
							/^package\.json$/,
							makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
						],
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
								changes: '/src/**/*.{js,jsx,ts,tsx}',
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
						changes: [
							/^package\.json$/,
							makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
						],
						command: 'foo',
						cascadeKeys: [ 'bar' ],
					},
				],
			} );
		} );

		it.each( testTypes )(
			'should parse test config with expected testType',
			( testType ) => {
				const parsed = parseCIConfig( {
					name: 'foo',
					config: {
						ci: {
							tests: [
								{
									name: 'default',
									testType,
									changes: '/src/**/*.{js,jsx,ts,tsx}',
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
							testType,
							shardingArguments: [],
							name: 'default',
							changes: [
								/^package\.json$/,
								makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
							],
							command: 'foo',
						},
					],
				} );
			}
		);

		it.each( [
			[ '', 'default' ],
			[ 'bad', 'default' ],
			[ 1, 'default' ],
			[ undefined, 'default' ],
		] )(
			'should parse test config with unexpected testType',
			( input, result ) => {
				const parsed = parseCIConfig( {
					name: 'foo',
					config: {
						ci: {
							tests: [
								{
									name: 'default',
									testType: input,
									changes: '/src/**/*.{js,jsx,ts,tsx}',
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
							testType: result,
							shardingArguments: [],
							name: 'default',
							changes: [
								/^package\.json$/,
								makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
							],
							command: 'foo',
						},
					],
				} );
			}
		);

		it.each( [
			[ [], [] ],
			[ undefined, [] ],
			[
				[ 'a', 'b' ],
				[ 'a', 'b' ],
			],
		] )(
			'should parse test config with shards',
			( shardingArguments: any, result: string[] ) => {
				const parsed = parseCIConfig( {
					name: 'foo',
					config: {
						ci: {
							tests: [
								{
									name: 'default',
									testType: 'e2e',
									shardingArguments,
									changes: '/src/**/*.{js,jsx,ts,tsx}',
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
							testType: 'e2e',
							shardingArguments: result,
							name: 'default',
							changes: [
								/^package\.json$/,
								makeRe( '/src/**/*.{js,jsx,ts,tsx}' ),
							],
							command: 'foo',
						},
					],
				} );
			}
		);

		it( 'should return default optional value for jobs', () => {
			const parsed = parseCIConfig( {
				name: 'foo',
				config: {
					ci: {
						lint: {
							changes: [],
							command: 'foo',
						},
						tests: [
							{
								name: 'default',
								changes: [],
								command: 'foo',
							},
						],
					},
				},
			} );

			expect( parsed ).toMatchObject( {
				jobs: [
					{
						type: JobType.Lint,
						optional: false,
					},
					{
						type: JobType.Test,
						optional: false,
					},
				],
			} );
		} );

		it.each( [
			[ true, true ],
			[ false, false ],
		] )(
			'should parse config with values for the optional property',
			( input, result ) => {
				const parsed = parseCIConfig( {
					name: 'foo',
					config: {
						ci: {
							lint: {
								changes: '/src/**/*.{js,jsx,ts,tsx}',
								command: 'foo',
								optional: input,
							},
						},
					},
				} );

				expect( parsed ).toMatchObject( {
					jobs: [
						{
							type: JobType.Lint,
							optional: result,
						},
					],
				} );
			}
		);

		it.each( [ [ 'bad', 1, undefined ] ] )(
			'should error for config with invalid values for the optional property',
			( input ) => {
				const expectation = () => {
					parseCIConfig( {
						name: 'foo',
						config: {
							ci: {
								lint: {
									changes: [],
									command: 'some command',
									optional: input,
								},
							},
						},
					} );
				};
				expect( expectation ).toThrow();
			}
		);
	} );
} );
