/**
 * Internal dependencies
 */
import { JobType } from '../config';
import { createJobsForChanges } from '../job-processing';
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
					},
				}
			);

			expect( jobs.lint ).toHaveLength( 1 );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test',
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
				command: 'test-lint',
			} );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test-b',
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
				command: 'test-lint-a',
			} );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test-b',
				command: 'test-lint-b',
			} );
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should trigger test job for single node', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
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
			expect( jobs.test ).toHaveLength( 1 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should replace vars in test command', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								name: 'Default',
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
					},
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 1 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd test-base-ref',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should not trigger a test job that has already been created', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								name: 'Default',
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
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should not trigger test job for single node with no changes', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								name: 'Default',
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
			expect( jobs.test ).toHaveLength( 0 );
		} );

		it( 'should trigger test job for project graph', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								name: 'Default',
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
										name: 'Default A',
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
										name: 'Default B',
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
			expect( jobs.test ).toHaveLength( 2 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-b',
				name: 'Default B',
				command: 'test-cmd-b',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should trigger test job for dependent without changes when dependency has matching cascade key', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								name: 'Default',
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
										name: 'Default A',
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
			expect( jobs.test ).toHaveLength( 2 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should isolate dependency cascade keys to prevent cross-dependency matching', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					ciConfig: {
						jobs: [
							{
								type: JobType.Test,
								name: 'Default',
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
										name: 'Default A',
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
										name: 'Default B',
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
			expect( jobs.test ).toHaveLength( 2 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );

		it( 'should trigger test job for single node and parse test environment config', async () => {
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
								name: 'Default',
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
					},
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 1 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
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
							},
							{
								type: JobType.Test,
								name: 'Default',
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
				command: 'test-lint',
			} );
			expect( jobs.test ).toHaveLength( 1 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
				testEnv: {
					shouldCreate: false,
					envVars: {},
				},
			} );
		} );
	} );
} );
