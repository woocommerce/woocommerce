/**
 * Internal dependencies
 */
import { JobType, testTypes } from '../config';
import { createJobsForChanges, getShardedJobs } from '../job-processing';
import { parseTestEnvConfig } from '../test-environment';

jest.mock( '../test-environment' );

describe( 'Job Processing', () => {
	describe( 'createJobsForChanges', () => {
		it( 'should do nothing with no CI configs', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					dependencies: [],
				},
				{},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should trigger lint job for single node', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Lint,
								changes: [ /test.js$/ ],
								command: 'test-lint',
								events: [],
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 1 );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				command: 'test-lint',
			} );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should replace vars in lint command', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Lint,
								changes: [ /test.js$/ ],
								command: 'test-lint <baseRef>',
								events: [],
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{
					commandVars: {
						baseRef: 'test-base-ref',
						event: '',
					},
				}
			);

			expect( jobs.lint ).toHaveLength( 1 );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				command: 'test-lint test-base-ref',
			} );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should throw when invalid var to replace in lint command', () => {
			const promise = createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Lint,
								changes: [ /test.js$/ ],
								command: 'test-lint <invalid>',
								events: [],
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{}
			);

			expect( promise ).rejects.toThrow();
		} );

		it( 'should not trigger a lint job that has already been created', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Lint,
								changes: [ /test.js$/ ],
								command: 'test-lint',
								events: [],
								jobCreated: true,
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should not trigger lint job for single node with no changes', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Lint,
								changes: [ /test.js$/ ],
								command: 'test-lint',
								events: [],
							},
						],
					},
					dependencies: [],
				},
				{},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should trigger lint job for project graph', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Lint,
								changes: [ /test.js$/ ],
								command: 'test-lint',
								events: [],
							},
						],
					},
					dependencies: [
						{
							name: 'test-a',
							path: 'test-a',
							ciConfig: {
								jobs: [
									{
										type: JobType.Lint,
										changes: [ /test-a.js$/ ],
										command: 'test-lint-a',
										events: [],
									},
								],
							},
							dependencies: [],
						},
						{
							name: 'test-b',
							path: 'test-b',
							ciConfig: {
								jobs: [
									{
										type: JobType.Lint,
										changes: [ /test-b.js$/ ],
										command: 'test-lint-b',
										events: [],
									},
								],
							},
							dependencies: [],
						},
					],
				},
				{
					test: [ 'test.js' ],
					'test-a': [ 'test-ignored.js' ],
					'test-b': [ 'test-b.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 2 );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				command: 'test-lint',
			} );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test-b',
				projectPath: 'test-b',
				command: 'test-lint-b',
			} );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should trigger lint job for project graph with empty config parent', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					dependencies: [
						{
							name: 'test-a',
							path: 'test-a',
							ciConfig: {
								jobs: [
									{
										type: JobType.Lint,
										changes: [ /test-a.js$/ ],
										command: 'test-lint-a',
										events: [],
									},
								],
							},
							dependencies: [],
						},
						{
							name: 'test-b',
							path: 'test-b',
							ciConfig: {
								jobs: [
									{
										type: JobType.Lint,
										changes: [ /test-b.js$/ ],
										command: 'test-lint-b',
										events: [],
									},
								],
							},
							dependencies: [],
						},
					],
				},
				{
					test: [ 'test.js' ],
					'test-a': [ 'test-a.js' ],
					'test-b': [ 'test-b.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 2 );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test-a',
				projectPath: 'test-a',
				command: 'test-lint-a',
			} );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test-b',
				projectPath: 'test-b',
				command: 'test-lint-b',
			} );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should trigger test job for single node', async () => {
			const testType = 'default';

			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								shardingArguments: [],
								events: [],
								name: 'Default',
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 1 );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				name: 'Default',
				command: 'test-cmd',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should replace vars in test command', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd <baseRef>',
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{
					commandVars: {
						baseRef: 'test-base-ref',
						event: '',
					},
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 1 );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				name: 'Default',
				command: 'test-cmd test-base-ref',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should not trigger a test job that has already been created', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
								jobCreated: true,
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 0 );
		} );

		it( 'should not trigger test job for single node with no changes', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [],
				},
				{},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 0 );
		} );

		it( 'should trigger test job for project graph', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [
						{
							name: 'test-a',
							path: 'test-a',
							ciConfig: {
								jobs: [
									{
										type: JobType.Test,
										testType: 'default',
										name: 'Default A',
										shardingArguments: [],
										events: [],
										changes: [ /test-b.js$/ ],
										command: 'test-cmd-a',
									},
								],
							},
							dependencies: [],
						},
						{
							name: 'test-b',
							path: 'test-b',
							ciConfig: {
								jobs: [
									{
										type: JobType.Test,
										testType: 'default',
										name: 'Default B',
										shardingArguments: [],
										events: [],
										changes: [ /test-b.js$/ ],
										command: 'test-cmd-b',
									},
								],
							},
							dependencies: [],
						},
					],
				},
				{
					test: [ 'test.js' ],
					'test-a': [ 'test-ignored.js' ],
					'test-b': [ 'test-b.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 2 );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				name: 'Default',
				command: 'test-cmd',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test-b',
				projectPath: 'test-b',
				name: 'Default B',
				command: 'test-cmd-b',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it.each( testTypes )(
			'should trigger %s test job for single node',
			async ( testType ) => {
				const jobs = await createJobsForChanges(
					{
						name: 'test',
						path: 'test',
						ciConfig: {
							jobs: [
								{
									type: JobType.Test,
									testType,
									name: 'Default',
									shardingArguments: [],
									events: [],
									changes: [ /test.js$/ ],
									command: 'test-cmd',
								},
							],
						},
						dependencies: [],
					},
					{
						test: [ 'test.js' ],
					},
					{}
				);

				expect( jobs.lint ).toHaveLength( 0 );
				expect( jobs[ `${ testType }Test` ] ).toHaveLength( 1 );
				expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
					projectName: 'test',
					projectPath: 'test',
					name: 'Default',
					command: 'test-cmd',
					shardNumber: 0,
					testEnv: {
						shouldCreate: false,
						envVars: {},
					},
				} );
			}
		);

		it( 'should trigger test job for dependent without changes when dependency has matching cascade key', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
								cascadeKeys: [ 'test' ],
							},
						],
					},
					dependencies: [
						{
							name: 'test-a',
							path: 'test-a',
							ciConfig: {
								jobs: [
									{
										type: JobType.Test,
										testType: 'default',
										name: 'Default A',
										shardingArguments: [],
										events: [],
										changes: [ /test-a.js$/ ],
										command: 'test-cmd-a',
										cascadeKeys: [ 'test-a', 'test' ],
									},
								],
							},
							dependencies: [],
						},
					],
				},
				{
					'test-a': [ 'test-a.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 2 );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				name: 'Default',
				command: 'test-cmd',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test-a',
				projectPath: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should isolate dependency cascade keys to prevent cross-dependency matching', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
								cascadeKeys: [ 'test' ],
							},
						],
					},
					dependencies: [
						{
							name: 'test-a',
							path: 'test-a',
							ciConfig: {
								jobs: [
									{
										type: JobType.Test,
										testType: 'default',
										name: 'Default A',
										shardingArguments: [],
										events: [],
										changes: [ /test-a.js$/ ],
										command: 'test-cmd-a',
										cascadeKeys: [ 'test-a', 'test' ],
									},
								],
							},
							dependencies: [],
						},
						{
							name: 'test-b',
							path: 'test-b',
							ciConfig: {
								jobs: [
									{
										type: JobType.Test,
										testType: 'default',
										name: 'Default B',
										shardingArguments: [],
										events: [],
										changes: [ /test-b.js$/ ],
										command: 'test-cmd-b',
										cascadeKeys: [ 'test-b', 'test' ],
									},
								],
							},
							dependencies: [],
						},
					],
				},
				{
					'test-a': [ 'test-a.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 2 );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				name: 'Default',
				command: 'test-cmd',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test-a',
				projectPath: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should trigger test job for single node and parse test environment config', async () => {
			const testType = 'default';
			jest.mocked( parseTestEnvConfig ).mockResolvedValue( {
				WP_ENV_CORE: 'https://wordpress.org/latest.zip',
			} );

			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
								testEnv: {
									start: 'test-start <baseRef>',
									config: {
										wpVersion: 'latest',
									},
								},
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{
					commandVars: {
						baseRef: 'test-base-ref',
						event: '',
					},
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 1 );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				name: 'Default',
				command: 'test-cmd',
				shardNumber: 0,
				testEnv: {
					shouldCreate: true,
					start: 'test-start test-base-ref',
					envVars: {
						WP_ENV_CORE: 'https://wordpress.org/latest.zip',
					},
				},
			} );
		} );

		it( 'should trigger all jobs for a single node with changes set to "true"', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Lint,
								changes: [ /test.js$/ ],
								command: 'test-lint',
								events: [],
							},
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [],
				},
				true,
				{}
			);

			expect( jobs.lint ).toHaveLength( 1 );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				command: 'test-lint',
			} );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 1 );
			expect( jobs[ `${ testType }Test` ] ).toContainEqual( {
				projectName: 'test',
				projectPath: 'test',
				name: 'Default',
				command: 'test-cmd',
				shardNumber: 0,
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should trigger sharded test jobs for single node', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [
									'--shard=1/2',
									'--shard=2/2',
								],
								events: [],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 2 );
			expect( jobs[ `${ testType }Test` ] ).toEqual(
				expect.arrayContaining( [
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 1/2',
						command: 'test-cmd --shard=1/2',
						shardNumber: 1,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 2/2',
						command: 'test-cmd --shard=2/2',
						shardNumber: 2,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
				] )
			);
		} );

		it( 'should trigger job with event configured but no event cli argument', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [
									'--shard=1/2',
									'--shard=2/2',
								],
								events: [ 'push' ],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 2 );
			expect( jobs[ `${ testType }Test` ] ).toEqual(
				expect.arrayContaining( [
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 1/2',
						command: 'test-cmd --shard=1/2',
						shardNumber: 1,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 2/2',
						command: 'test-cmd --shard=2/2',
						shardNumber: 2,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
				] )
			);
		} );

		it( 'should trigger job with event configured and matching event cli argument', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [
									'--shard=1/2',
									'--shard=2/2',
								],
								events: [ 'push' ],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{ commandVars: { baseRef: 'test-base-ref', event: 'push' } }
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs[ `${ testType }Test` ] ).toHaveLength( 2 );
			expect( jobs[ `${ testType }Test` ] ).toEqual(
				expect.arrayContaining( [
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 1/2',
						command: 'test-cmd --shard=1/2',
						shardNumber: 1,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 2/2',
						command: 'test-cmd --shard=2/2',
						shardNumber: 2,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
				] )
			);
		} );

		it( 'should not trigger job with event configured but not matching event cli argument', async () => {
			const testType = 'default';
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								testType,
								name: 'Default',
								shardingArguments: [
									'--shard=1/2',
									'--shard=2/2',
								],
								events: [ 'push' ],
								changes: [ /test.js$/ ],
								command: 'test-cmd',
							},
						],
					},
					dependencies: [],
				},
				{
					test: [ 'test.js' ],
				},
				{
					commandVars: {
						baseRef: 'test-base-ref',
						event: 'pull_request',
					},
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 0 );
		} );
	} );

	describe( 'getShardedJobs', () => {
		it( 'should create sharded jobs', async () => {
			const jobs = getShardedJobs(
				{
					projectName: 'test',
					projectPath: 'test',
					name: 'Default',
					command: 'test-cmd',
					shardNumber: 0,
					testEnv: {
						shouldCreate: false,
						envVars: {},
					},
					optional: false,
				},
				{
					type: JobType.Test,
					testType: 'e2e',
					name: 'Default',
					shardingArguments: [ '--shard-arg-1', '--shard-arg-2' ],
					events: [],
					changes: [ /test.js$/ ],
					command: 'test-cmd',
				}
			);

			expect( jobs ).toHaveLength( 2 );
			expect( jobs ).toEqual(
				expect.arrayContaining( [
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 1/2',
						command: 'test-cmd --shard-arg-1',
						shardNumber: 1,
						optional: false,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default 2/2',
						command: 'test-cmd --shard-arg-2',
						shardNumber: 2,
						optional: false,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
					},
				] )
			);
		} );

		it.each( [ [ [] ], [ [ '--sharding=1/1' ] ] ] )(
			'should not create sharded jobs for shards',
			async ( shardingArguments ) => {
				const jobs = getShardedJobs(
					{
						projectName: 'test',
						projectPath: 'test',
						name: 'Default',
						command: 'test-cmd',
						shardNumber: 0,
						testEnv: {
							shouldCreate: false,
							envVars: {},
						},
						optional: false,
					},
					{
						type: JobType.Test,
						testType: 'e2e',
						name: 'Default',
						shardingArguments,
						events: [],
						changes: [ /test.js$/ ],
						command: 'test-cmd',
					}
				);

				expect( jobs ).toHaveLength( 1 );
				expect( jobs ).toContainEqual( {
					projectName: 'test',
					projectPath: 'test',
					name: 'Default',
					command: 'test-cmd',
					shardNumber: 0,
					optional: false,
					testEnv: {
						shouldCreate: false,
						envVars: {},
					},
				} );
			}
		);
	} );
} );
