/**
 * External dependencies
 */
import { execSync } from 'node:child_process';

/**
 * Internal dependencies
 */
import { getFileChanges } from '../file-changes';

jest.mock( 'node:child_process' );

describe( 'File Changes', () => {
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
} );
