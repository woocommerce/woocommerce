/**
 * External dependencies
 */
import { execSync } from 'node:child_process';
import fs from 'node:fs';

/**
 * Internal dependencies
 */
import { buildProjectGraph } from '../project-graph';

jest.mock( 'node:child_process' );

describe( 'Project Graph', () => {
	describe( 'buildProjectGraph', () => {
		it( 'should build graph from pnpm list', async () => {
			jest.mocked( execSync ).mockImplementation( ( command ) => {
				if ( command === 'pnpm -r list --only-projects --json' ) {
					return fs.readFileSync(
						__dirname + '/test-pnpm-list.json'
					);
				}

				throw new Error( 'Invalid command' );
			} );

			const graph = await buildProjectGraph();

			expect( graph ).toMatchObject( {
				'project-a': {
					name: 'project-a',
					path: '/project-a',
					dependencies: [
						{
							name: 'project-b',
							path: '/project-b',
							dependencies: [
								{
									name: 'project-c',
									path: '/project-c',
								},
							],
						},
						{
							name: 'project-c',
							path: '/project-c',
						},
					],
				},
				'project-b': {
					name: 'project-b',
					path: '/project-b',
					dependencies: [
						{
							name: 'project-c',
							path: '/project-c',
						},
					],
				},
				'project-c': {
					name: 'project-c',
					path: '/project-c',
				},
			} );
		} );
	} );
} );
