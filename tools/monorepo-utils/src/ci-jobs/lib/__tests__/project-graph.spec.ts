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
				if ( ! path.endsWith( 'package.json' ) ) {
					throw new Error( 'Invalid path' );
				}

				const matches = path.match( /\/([^/]+)\/package.json$/ );
				if ( matches[ 1 ] === 'project-a' ) {
					return JSON.parse(
						fs.readFileSync( __dirname + '/test-package.json', {
							encoding: 'utf8',
						} )
					);
				}

				return {
					name: matches[ 1 ],
				};
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
								/^(?:src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.js|src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.jsx|src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.ts|src(?:\/|\/(?:(?!(?:\/|^)\.).)*?\/)(?!\.)[^/]*?\.tsx)$/
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
