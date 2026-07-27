/**
 * External dependencies
 */
import { execSync } from 'node:child_process';

/**
 * Internal dependencies
 */
import { getFileChanges } from '../file-changes';
import { JobType } from '../config';

jest.mock( 'node:child_process' );

describe( 'File Changes', () => {
	afterEach( () => {
		jest.resetAllMocks();
	} );

	describe( 'getFileChanges', () => {
		it( 'should associate git changes with projects', () => {
			jest.mocked( execSync ).mockImplementation( ( command ) => {
				if ( command === 'git diff --name-only origin/trunk' ) {
					return `test/project-a/package.json
foo/project-b/foo.js
bar/project-c/bar.js
baz/project-d/baz.js`;
				}

				throw new Error( 'Invalid command' );
			} );

			const fileChanges = getFileChanges(
				{
					name: 'project-a',
					path: 'test/project-a',
					dependencies: [
						{
							name: 'project-b',
							path: 'foo/project-b',
							dependencies: [
								{
									name: 'project-c',
									path: 'bar/project-c',
									dependencies: [],
								},
							],
						},
						{
							name: 'project-c',
							path: 'bar/project-c',
							dependencies: [],
						},
					],
				},
				'origin/trunk',
				''
			);

			expect( fileChanges ).toMatchObject( {
				'project-a': [ 'package.json' ],
				'project-b': [ 'foo.js' ],
				'project-c': [ 'bar.js' ],
			} );
		} );
	} );

	it( 'should see pnpm-lock.yaml file changes as universal changes', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `test/project-a/package.json
foo/project-b/foo.js
pnpm-lock.yaml
bar/project-c/bar.js
baz/project-d/baz.js`;
			}

			throw new Error( 'Invalid command' );
		} );

		const fileChanges = getFileChanges(
			{
				name: 'project-a',
				path: 'test/project-a',
				dependencies: [
					{
						name: 'project-b',
						path: 'foo/project-b',
						dependencies: [
							{
								name: 'project-c',
								path: 'bar/project-c',
								dependencies: [],
							},
						],
					},
					{
						name: 'project-c',
						path: 'bar/project-c',
						dependencies: [],
					},
				],
			},
			'origin/trunk',
			''
		);

		expect( fileChanges ).toStrictEqual( true );
	} );

	it( 'should not associate non-code files with any project', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `AGENTS.md
test/project-a/README.md
test/project-a/CLAUDE.md
test/project-a/changelog/fix-123
test/project-a/readme.txt
docs/docs-manifest.json
foo/project-b/docs/guide.md`;
			}

			throw new Error( 'Invalid command' );
		} );

		const fileChanges = getFileChanges(
			{
				name: 'project-a',
				path: 'test/project-a',
				dependencies: [
					{
						name: 'project-b',
						path: 'foo/project-b',
						dependencies: [],
					},
				],
			},
			'origin/trunk',
			''
		);

		// None of these files can change behaviour, so no project -- and therefore
		// none of its dependents -- should be marked as changed.
		expect( fileChanges ).toStrictEqual( {} );
	} );

	it( 'should associate source files that live inside a changelog directory', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `test/project-a/src/commands/changelog/index.ts
test/project-a/changelog/fix-123`;
			}

			throw new Error( 'Invalid command' );
		} );

		const fileChanges = getFileChanges(
			{
				name: 'project-a',
				path: 'test/project-a',
				dependencies: [],
			},
			'origin/trunk',
			''
		);

		// The entry is ignored; the module that happens to sit in a `changelog` directory is not.
		expect( fileChanges ).toStrictEqual( {
			'project-a': [ 'src/commands/changelog/index.ts' ],
		} );
	} );

	it( 'should still associate code changes made alongside non-code files', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `test/project-a/README.md
test/project-a/index.js
foo/project-b/changelog/add-456`;
			}

			throw new Error( 'Invalid command' );
		} );

		const fileChanges = getFileChanges(
			{
				name: 'project-a',
				path: 'test/project-a',
				dependencies: [
					{
						name: 'project-b',
						path: 'foo/project-b',
						dependencies: [],
					},
				],
			},
			'origin/trunk',
			''
		);

		expect( fileChanges ).toStrictEqual( {
			'project-a': [ 'index.js' ],
		} );
	} );

	it( 'should assign files to projects based on CI config patterns', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `plugins/woocommerce/tests/e2e/tests/blocks/test.spec.ts
plugins/woocommerce/client/blocks/src/block.tsx`;
			}

			throw new Error( 'Invalid command' );
		} );

		const fileChanges = getFileChanges(
			{
				name: '@woocommerce/plugin-woocommerce',
				path: 'plugins/woocommerce',
				ciConfig: {
					jobs: [
						{
							type: JobType.Test,
							testType: 'e2e',
							name: 'Blocks e2e tests',
							changes: [ /^tests\/e2e\/tests\/blocks\/.*/ ],
							command: 'test:e2e:blocks',
							events: [ 'pull_request' ],
							shardingArguments: [],
						},
					],
				},
				dependencies: [
					{
						name: '@woocommerce/block-library',
						path: 'plugins/woocommerce/client/blocks',
						dependencies: [],
					},
				],
			},
			'origin/trunk',
			''
		);

		// Files should be assigned to both projects:
		// - block-library gets files in its path (both test and src files)
		// - plugin gets files matching its CI config pattern (test files only)
		expect( fileChanges ).not.toBe( true );
		if ( fileChanges !== true ) {
			expect( fileChanges ).toMatchObject( {
				'@woocommerce/plugin-woocommerce': [
					'tests/e2e/tests/blocks/test.spec.ts',
				],
				'@woocommerce/block-library': [ 'src/block.tsx' ],
			} );
		}
	} );

	it( 'should not assign files to projects if CI config pattern does not match', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `plugins/woocommerce/client/blocks/src/block.tsx
plugins/woocommerce/client/blocks/assets/style.scss`;
			}

			throw new Error( 'Invalid command' );
		} );

		const fileChanges = getFileChanges(
			{
				name: '@woocommerce/plugin-woocommerce',
				path: 'plugins/woocommerce',
				ciConfig: {
					jobs: [
						{
							type: JobType.Test,
							testType: 'e2e',
							name: 'Blocks e2e tests',
							changes: [ /^tests\/e2e\/tests\/blocks\/.*/ ],
							command: 'test:e2e:blocks',
							events: [ 'pull_request' ],
							shardingArguments: [],
						},
					],
				},
				dependencies: [
					{
						name: '@woocommerce/block-library',
						path: 'plugins/woocommerce/client/blocks',
						dependencies: [],
					},
				],
			},
			'origin/trunk',
			''
		);

		// Only block-library should get the files since they don't match plugin's CI patterns
		expect( fileChanges ).not.toBe( true );
		if ( fileChanges !== true ) {
			expect( fileChanges ).toMatchObject( {
				'@woocommerce/block-library': [
					'src/block.tsx',
					'assets/style.scss',
				],
			} );
			expect(
				fileChanges[ '@woocommerce/plugin-woocommerce' ]
			).toBeUndefined();
		}
	} );

	it( 'should handle multiple CI config patterns from different jobs', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `plugins/woocommerce/tests/e2e/tests/blocks/test.spec.ts
plugins/woocommerce/client/blocks/tests/unit/test.spec.ts
plugins/woocommerce/client/blocks/src/block.tsx`;
			}

			throw new Error( 'Invalid command' );
		} );

		const fileChanges = getFileChanges(
			{
				name: '@woocommerce/plugin-woocommerce',
				path: 'plugins/woocommerce',
				ciConfig: {
					jobs: [
						{
							type: JobType.Test,
							testType: 'e2e',
							name: 'Blocks e2e tests',
							changes: [ /^tests\/e2e\/tests\/blocks\/.*/ ],
							command: 'test:e2e:blocks',
							events: [ 'pull_request' ],
							shardingArguments: [],
						},
						{
							type: JobType.Test,
							testType: 'unit',
							name: 'Blocks unit tests',
							changes: [ /^client\/blocks\/tests\/unit\/.*/ ],
							command: 'test:unit:blocks',
							events: [ 'pull_request' ],
							shardingArguments: [],
						},
					],
				},
				dependencies: [
					{
						name: '@woocommerce/block-library',
						path: 'plugins/woocommerce/client/blocks',
						dependencies: [],
					},
				],
			},
			'origin/trunk',
			''
		);

		// Plugin should get both e2e and unit test files
		expect( fileChanges ).not.toBe( true );
		if ( fileChanges !== true ) {
			expect( fileChanges ).toMatchObject( {
				'@woocommerce/plugin-woocommerce': [
					'tests/e2e/tests/blocks/test.spec.ts',
					'client/blocks/tests/unit/test.spec.ts',
				],
				'@woocommerce/block-library': [
					'tests/unit/test.spec.ts',
					'src/block.tsx',
				],
			} );
		}
	} );
} );
