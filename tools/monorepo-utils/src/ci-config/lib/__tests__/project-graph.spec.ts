/**
 * External dependencies
 */
import { execSync } from 'node:child_process';
import fs from 'node:fs';

/**
 * Internal dependencies
 */
import { parseCIConfig } from '../config';
import { loadPackage } from '../package-file';
import { buildProjectGraph } from '../project-graph';

jest.mock( 'node:child_process' );
jest.mock( '../config' );
jest.mock( '../package-file' );

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

			jest.mocked( loadPackage ).mockImplementation( ( path ) => {
				if ( ! path.endsWith( 'package.json' ) ) {
					throw new Error( 'Invalid path' );
				}

				const matches = path.match( /\/([^/]+)\/package.json$/ );

				return {
					name: matches[ 1 ],
				};
			} );

			jest.mocked( parseCIConfig ).mockImplementation(
				( packageFile ) => {
					expect( packageFile ).toMatchObject( {
						name: expect.stringMatching( /project-[abc]/ ),
					} );

					return { jobs: [] };
				}
			);

			const graph = await buildProjectGraph();

			expect( loadPackage ).toHaveBeenCalled();
			expect( parseCIConfig ).toHaveBeenCalled();
			expect( graph ).toMatchObject( {
				name: 'project-a',
				path: '/project-a',
				ciConfig: {
					jobs: [],
				},
				dependencies: [
					{
						name: 'project-b',
						path: '/project-b',
						ciConfig: {
							jobs: [],
						},
						dependencies: [
							{
								name: 'project-c',
								path: '/project-c',
								ciConfig: {
									jobs: [],
								},
								dependencies: [],
							},
						],
					},
					{
						name: 'project-c',
						path: '/project-c',
						ciConfig: {
							jobs: [],
						},
						dependencies: [],
					},
				],
			} );
		} );
	} );
} );
