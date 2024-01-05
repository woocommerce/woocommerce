/**
 * External dependencies
 */
import { execSync } from 'node:child_process';

/**
 * Internal dependencies
 */
import { ProjectNode } from './project-graph';

/**
 * A map of changed files keyed by the project name.
 */
export interface ProjectFileChanges {
	[ name: string ]: string[];
}

/**
 * Gets the project path for every project in the graph.
 *
 * @param {Object} graph The project graph to process.
 * @return {Object} The project paths keyed by the project name.
 */
function getProjectPaths( graph: ProjectNode ): { [ name: string ]: string } {
	const projectPaths: { [ name: string ]: string } = {};

	const queue = [ graph ];
	const visited: { [ name: string ]: boolean } = {};
	while ( queue.length > 0 ) {
		const node = queue.shift();
		if ( ! node ) {
			continue;
		}

		if ( visited[ node.name ] ) {
			continue;
		}

		projectPaths[ node.name ] = node.path;

		visited[ node.name ] = true;

		queue.push( ...node.dependencies );
	}

	return projectPaths;
}

/**
 * Checks the changed files and returns any that are relevant to the project.
 *
 * @param {string}         projectPath  The path to the project to get changed files for.
 * @param {Array.<string>} changedFiles The files that have changed in the repo.
 * @return {Array.<string>} The files that have changed in the project.
 */
function getChangedFilesForProject(
	projectPath: string,
	changedFiles: string[]
): string[] {
	const projectChanges = [];

	// Find all of the files that have changed in the project.
	for ( const filePath of changedFiles ) {
		if ( ! filePath.startsWith( projectPath ) ) {
			continue;
		}

		// Track the file relative to the project.
		projectChanges.push( filePath.slice( projectPath.length + 1 ) );
	}

	return projectChanges;
}

/**
 * Pulls all of the files that have changed in the project graph since the given git ref.
 *
 * @param {Object} projectGraph The project graph to assign changes for.
 * @param {string} baseRef      The git ref to compare against for changes.
 * @return {Object} A map of changed files keyed by the project name.
 */
export function getFileChanges(
	projectGraph: ProjectNode,
	baseRef: string
): ProjectFileChanges {
	const projectPaths = getProjectPaths( projectGraph );

	// We're going to use git to figure out what files have changed.
	const output = execSync( `git diff --name-only ${ baseRef }`, {
		encoding: 'utf8',
	} );
	const changedFilePaths = output.split( '\n' );

	const changes: ProjectFileChanges = {};
	for ( const projectName in projectPaths ) {
		const projectChanges = getChangedFilesForProject(
			projectPaths[ projectName ],
			changedFilePaths
		);
		if ( projectChanges.length === 0 ) {
			continue;
		}

		changes[ projectName ] = projectChanges;
	}

	return changes;
}
