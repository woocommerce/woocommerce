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
						shards: 0,
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
							shards: 0,
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
							shards: 0,
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
			[ '0', 0 ],
			[ '1', 1 ],
			[ '', 0 ],
			[ '.1', 0 ],
			[ '3.1', 3 ],
		] )(
			'should parse test config with %i shards',
			( input: any, result: number ) => {
				const parsed = parseCIConfig( {
					name: 'foo',
					config: {
						ci: {
							tests: [
								{
									name: 'default',
									testType: 'e2e',
									shards: input,
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
							shards: result,
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
	} );
} );
