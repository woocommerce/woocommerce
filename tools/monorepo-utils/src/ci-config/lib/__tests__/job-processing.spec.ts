/**
 * Internal dependencies
 */
import { JobType } from '../config';
import { createJobsForChanges } from '../job-processing';

describe( 'Job Processing', () => {
	describe( 'getFileChanges', () => {
		it( 'should do nothing with no CI configs', () => {
			const jobs = createJobsForChanges(
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

		it( 'should trigger lint job for single node', () => {
			const jobs = createJobsForChanges(
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

		it( 'should not trigger lint job for single node with no changes', () => {
			const jobs = createJobsForChanges(
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

		it( 'should trigger lint job for project graph', () => {
			const jobs = createJobsForChanges(
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

		it( 'should trigger lint job for project graph with empty config parent', () => {
			const jobs = createJobsForChanges(
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

		it( 'should trigger test job for single node', () => {
			const jobs = createJobsForChanges(
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
				hasTestEnv: false,
			} );
		} );

		it( 'should not trigger test job for single node with no changes', () => {
			const jobs = createJobsForChanges(
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

		it( 'should trigger test job for project graph', () => {
			const jobs = createJobsForChanges(
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
				hasTestEnv: false,
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-b',
				name: 'Default B',
				command: 'test-cmd-b',
				hasTestEnv: false,
			} );
		} );

		it( 'should trigger test job for dependent without changes when dependency has matching cascade key', () => {
			const jobs = createJobsForChanges(
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
				hasTestEnv: false,
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
				hasTestEnv: false,
			} );
		} );

		it( 'should isolate dependency cascade keys to prevent cross-dependency matching', () => {
			const jobs = createJobsForChanges(
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
				hasTestEnv: false,
			} );
			expect( jobs.test ).toContainEqual( {
				projectName: 'test-a',
				name: 'Default A',
				command: 'test-cmd-a',
				hasTestEnv: false,
			} );
		} );
	} );
} );
