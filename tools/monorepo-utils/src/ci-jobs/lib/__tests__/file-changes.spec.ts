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

/**
 * The globs `.github/workflows/ci.yml` passes as `--ignore`. Mirrored here so the tests exercise
 * the patterns that actually run in CI rather than a simplified stand-in.
 */
const CI_IGNORE_GLOBS = [
	'{,**/}*.md',
	'docs/docs-manifest.json',
	'{,**/}changelog/!(*.ts|*.tsx|*.js|*.jsx|*.mjs|*.cjs|*.php|*.json|*.scss|*.css)',
	'.github/**',
	'.husky/**',
	'.cursor/**',
	'.gitignore',
	'{,**/}readme.txt',
];

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

	it( 'should not associate ignored files with any project', () => {
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
			'',
			CI_IGNORE_GLOBS
		);

		// Every file is ignored, so no project -- and therefore none of its
		// dependents -- should be marked as changed.
		expect( fileChanges ).toStrictEqual( {} );
	} );

	it( 'should associate every file when no ignore globs are given', () => {
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

		// The tool knows nothing about file types on its own: without globs it claims
		// the same files it always has.
		expect( fileChanges ).toStrictEqual( {
			'project-a': [
				'README.md',
				'CLAUDE.md',
				'changelog/fix-123',
				'readme.txt',
			],
			'project-b': [ 'docs/guide.md' ],
		} );
	} );

	it( 'should associate source files that live inside a changelog directory', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `test/project-a/changelog/index.ts
test/project-a/src/commands/changelog/index.ts
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
			'',
			CI_IGNORE_GLOBS
		);

		// The entry is ignored; modules that happen to sit in a `changelog` directory are not,
		// whether they are directly inside one or further down the tree.
		expect( fileChanges ).toStrictEqual( {
			'project-a': [
				'changelog/index.ts',
				'src/commands/changelog/index.ts',
			],
		} );
	} );

	it( 'should still associate code changes made alongside ignored files', () => {
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
			'',
			CI_IGNORE_GLOBS
		);

		expect( fileChanges ).toStrictEqual( {
			'project-a': [ 'index.js' ],
		} );
	} );

	it( 'should ignore matching files inside dot directories', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `test/project-a/.ai/skills/guide/SKILL.md
test/project-a/index.js`;
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
			'',
			CI_IGNORE_GLOBS
		);

		// `dorny/paths-filter` compiles with `dot`, so `**` has to descend into dot
		// directories here too -- otherwise the gate and this command disagree.
		expect( fileChanges ).toStrictEqual( {
			'project-a': [ 'index.js' ],
		} );
	} );

	it( 'should throw for an ignore glob it cannot compile', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return 'test/project-a/index.js';
			}

			throw new Error( 'Invalid command' );
		} );

		expect( () =>
			getFileChanges(
				{
					name: 'project-a',
					path: 'test/project-a',
					dependencies: [],
				},
				'origin/trunk',
				'',
				[ '' ]
			)
		).toThrow( '"" is an invalid ignore glob pattern.' );
	} );

	it( 'should assign files to projects based on CI config patterns', () => {
		jest.mocked( execSync ).mockImplementation( ( command ) => {
			if ( command === 'git diff --name-only origin/trunk' ) {
				return `plugins/woocommerce/changelog/fix-123
plugins/woocommerce/tests/e2e/tests/blocks/test.spec.ts
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
					'changelog/fix-123',
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
