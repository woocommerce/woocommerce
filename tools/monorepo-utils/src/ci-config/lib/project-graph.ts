/**
 * External dependencies
 */
import { execSync } from 'node:child_process';
import path from 'node:path';

/**
 * A node in the project dependency graph.
 */
interface ProjectNode {
	name: string;
	path: string;
	dependencies: ProjectNode[];
}

/**
 * All of the project nodes keyed by their project name.
 */
export interface ProjectGraph {
	[ name: string ]: ProjectNode;
}

/**
 * Builds a dependency graph of all projects in the monorepo and returns the root node.
 */
export function buildProjectGraph(): ProjectGraph {
	// PNPM provides us with a flat list of all projects
	// in the workspace and their dependencies.
	const workspace = JSON.parse(
		execSync( 'pnpm -r list --only-projects --json', { encoding: 'utf-8' } )
	);

	// Start by building an object containing all of the nodes keyed by their project name.
	// This will let us link them together quickly by iterating through the list of
	// dependencies and adding the applicable nodes.
	const nodes: ProjectGraph = {};
	for ( const project of workspace ) {
		// Use a relative path to the project so that it's easier for us to work with
		const projectPath = project.path.replace(
			new RegExp(
				'#^' + process.cwd().replace( '#', '\\#' ) + path.sep + '?#'
			),
			''
		);

		const node = {
			name: project.name,
			path: projectPath,
			dependencies: [],
		};

		nodes[ project.name ] = node;
	}

	// Now we can scan through all of the nodes and hook them up to their respective dependency nodes.
	for ( const project of workspace ) {
		const node = nodes[ project.name ];
		if ( project.dependencies ) {
			for ( const dependency in project.dependencies ) {
				node.dependencies.push( nodes[ dependency ] );
			}
		}
		if ( project.devDependencies ) {
			for ( const dependency in project.devDependencies ) {
				node.dependencies.push( nodes[ dependency ] );
			}
		}
	}

	return nodes;
}
