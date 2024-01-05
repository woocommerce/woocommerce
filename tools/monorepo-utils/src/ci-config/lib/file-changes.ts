/**
 * External dependencies
 */
import { execSync } from 'node:child_process';

/**
 * Internal dependencies
 */
import { ProjectGraph } from './project-graph';

/**
 * A map of changed files keyed by the project name.
 */
interface ProjectFileChanges {
	[ name: string ]: string[];
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
	projectGraph: ProjectGraph,
	baseRef: string
): ProjectFileChanges {
	// We're going to use git to figure out what files have changed.
	const output = execSync( `git diff --name-only ${ baseRef }`, {
		encoding: 'utf8',
	} );
	const changedFilePaths = output.split( '\n' );

	const changes: ProjectFileChanges = {};
	for ( const projectName in projectGraph ) {
		const project = projectGraph[ projectName ];
		const projectChanges = getChangedFilesForProject(
			project.path,
			changedFilePaths
		);
		changes[ projectName ] = projectChanges;
	}

	return changes;
}
