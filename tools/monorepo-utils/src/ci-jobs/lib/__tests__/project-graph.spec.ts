/**
 * External dependencies
 */
import { execSync } from 'node:child_process';
import fs from 'node:fs';

/**
 * Internal dependencies
 */
import { loadPackage } from '../package-file';
import { buildProjectGraph } from '../project-graph';

jest.mock( 'node:child_process' );
jest.mock( '../package-file' );

describe( 'Project Graph', () => {
	describe( 'buildProjectGraph', () => {
		it( 'should build graph from pnpm list', () => {
			jest.mocked( execSync ).mockImplementation( ( command ) => {
				if ( command === 'pnpm -w root' ) {
					return '/test/monorepo/node_modules';
				}

				if ( command === 'pnpm -r list --only-projects --json' ) {
					return fs.readFileSync(
						__dirname + '/test-pnpm-list.json'
					);
				}

				throw new Error( 'Invalid command' );
			} );

			jest.mocked( loadPackage ).mockImplementation( ( path ) => {
				const matches = path.match( /project-([abcd])\/package.json$/ );
				if ( ! matches ) {
					throw new Error( `Invalid project path: ${ path }.` );
				}

				const packageFile = JSON.parse(
					fs.readFileSync( __dirname + '/test-package.json', {
						encoding: 'utf8',
					} )
				);

				packageFile.name = 'project-' + matches[ 1 ];

				switch ( matches[ 1 ] ) {
					case 'a':
						packageFile.dependencies = {
							'project-b': 'workspace:*',
						};
						packageFile.devDependencies = {
							'project-c': 'workspace:*',
						};
						break;
					case 'b':
						packageFile.dependencies = {
							'project-c': 'workspace:*',
						};
						break;
					case 'd':
						packageFile.devDependencies = {
							'project-c': 'workspace:*',
						};
						break;
				}

				return packageFile;
			} );

			const graph = buildProjectGraph();

			expect( loadPackage ).toHaveBeenCalled();
			expect( graph ).toMatchObject( {
				name: 'project-a',
				path: 'project-a',
				ciConfig: {
					jobs: [
						{
							command: 'foo',
							type: 'lint',
							changes: [
								/^package\.json$/,
								/^(?:src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.js|src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.jsx|src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.ts|src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.tsx)$/,
							],
						},
					],
				},
				dependencies: [
					{
						name: 'project-b',
						path: 'project-b',
						dependencies: [
							{
								name: 'project-c',
								path: 'project-c',
								dependencies: [],
							},
						],
					},
					{
						name: 'project-c',
						path: 'project-c',
						dependencies: [],
					},
					{
						name: 'project-d',
						path: 'project-d',
						dependencies: [
							{
								name: 'project-c',
								path: 'project-c',
								dependencies: [],
							},
						],
					},
				],
			} );
		} );
	} );
} );
