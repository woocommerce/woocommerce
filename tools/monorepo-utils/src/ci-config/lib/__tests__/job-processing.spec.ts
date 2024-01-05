/**
 * Internal dependencies
 */
import { JobType } from '../config';
import { createJobsForChanges } from '../job-processing';
import { parseTestEnvConfig } from '../test-environment';

jest.mock( '../test-environment' );

describe( 'Job Processing', () => {
	describe( 'getFileChanges', () => {
		it( 'should do nothing with no CI configs', async () => {
			const jobs = await createJobsForChanges(
				{
					name: 'test',
					path: 'test',
					dependencies: [],
				},
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
				}
			);

			expect( jobs.lint ).toHaveLength( 1 );
			expect( jobs.lint ).toContainEqual( {
				projectName: 'test',
				command: 'test-lint',
			} );
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
				}
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
				}
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
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 1 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
			} );
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
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 2 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-b',
				name: 'Default B',
				command: 'test-cmd-b',
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
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 2 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
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
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 2 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
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
									start: 'test-start',
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
				}
			);

			expect( jobs.lint ).toHaveLength( 0 );
			expect( jobs.test ).toHaveLength( 1 );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test',
				name: 'Default',
				command: 'test-cmd',
				testEnv: {
					start: 'test-start',
					envVars: {
						WP_ENV_CORE: 'https://wordpress.org/latest.zip',
					},
				},
			} );
		} );
	} );
} );
