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
	const pathSorted: Array< [ name: string, path: string, depth: number ] > =
		[];

	const queue = [ graph ];
	const visited: { [ name: string ]: boolean } = {};
	while ( queue.length > 0 ) {
		const node = queue.shift();
		if ( ! node || visited[ node.name ] ) {
			continue;
		}

		pathSorted.push( [
			node.name,
			node.path,
			node.path.split( '/' ).length,
		] );
		visited[ node.name ] = true;
		queue.push( ...node.dependencies );
	}

	pathSorted.sort( ( a, b ) => b[ 2 ] - a[ 2 ] );
	pathSorted.forEach(
		( entry ) => ( projectPaths[ entry[ 0 ] ] = entry[ 1 ] )
	);

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
		if ( ! filePath.startsWith( projectPath + '/' ) ) {
			continue;
		}

		// Track the file relative to the project.
		projectChanges.push(
			filePath.slice( projectPath.length + Number( projectPath !== '' ) )
		);
	}

	return projectChanges;
}

/**
 * Pulls all of the files that have changed in the project graph since the given git ref.
 *
 * @param {Object} projectGraph The project graph to assign changes for.
 * @param {string} baseRef      The git ref to compare against for changes.
 * @param {string} prNumber     The PR number referencing the changes.
 * @return {Object|true} A map of changed files keyed by the project name or true if all projects should be marked as changed.
 */
export function getFileChanges(
	projectGraph: ProjectNode,
	baseRef: string,
	prNumber: string
): ProjectFileChanges | true {
	const command =
		( prNumber && `gh pr diff ${ prNumber } --name-only` ) ||
		`git diff --name-only ${ baseRef }`;
	// We're going to use git to figure out what files have changed.
	const output = execSync( command, {
		encoding: 'utf8',
	} );

	const changedFilePaths = output.split( '\n' );

	// If the root lockfile has been changed we have no easy way
	// of knowing which projects have been impacted. We want
	// to re-run all jobs in all projects for safety.
	if ( changedFilePaths.includes( 'pnpm-lock.yaml' ) ) {
		return true;
	}

	const ownedFilePaths = [];

	// At the very first iteration, we will identify files ownership by monorepo packages.
	const projectPaths = getProjectPaths( projectGraph );
	const changes: ProjectFileChanges = {};
	for ( const projectName in projectPaths ) {
		// Projects with no paths have no changed files for us to identify.
		if ( ! projectPaths[ projectName ] ) {
			continue;
		}

		const projectChanges = getChangedFilesForProject(
			projectPaths[ projectName ],
			changedFilePaths.filter(
				( filePath ) => ! ownedFilePaths.includes( filePath )
			)
		);
		if ( projectChanges.length === 0 ) {
			continue;
		}

		changes[ projectName ] = projectChanges;
		ownedFilePaths.push(
			...projectChanges.map(
				( filePath ) => projectPaths[ projectName ] + '/' + filePath
			)
		);
	}

	// Additional iteration will mark remaining files ownership by the monorepo itself.
	const orphanFilePaths = changedFilePaths.filter(
		( filePath ) => ! ownedFilePaths.includes( filePath )
	);
	for ( const projectName in projectPaths ) {
		if ( projectPaths[ projectName ] ) {
			continue;
		}

		const projectChanges = getChangedFilesForProject(
			projectPaths[ projectName ],
			orphanFilePaths
		);
		if ( projectChanges.length === 0 ) {
			continue;
		}

		changes[ projectName ] = projectChanges;
		break;
	}

	// Third iteration: assign files to projects based on CI config patterns.
	// This allows projects to claim files that match their CI config patterns,
	// even if those files are in nested package directories.
	const allNodes: ProjectNode[] = [];
	const queue: ProjectNode[] = [ projectGraph ];
	const visited = new Set< string >();
	while ( queue.length > 0 ) {
		const node = queue.shift();
		if ( ! node || visited.has( node.name ) ) {
			continue;
		}
		allNodes.push( node );
		visited.add( node.name );
		queue.push( ...node.dependencies );
	}

	for ( const node of allNodes ) {
		if ( ! node.ciConfig || ! node.path ) {
			continue;
		}

		// Collect all change patterns from all jobs in this project
		const jobs = node.ciConfig.jobs ?? [];
		if ( jobs.length === 0 ) {
			continue;
		}

		const changePatterns: RegExp[] = [];
		for ( const job of jobs ) {
			if ( job.changes ) {
				// Normalize flags: drop stateful g/y to avoid lastIndex side effects.
				for ( const re of job.changes ) {
					changePatterns.push(
						re.global || re.sticky
							? new RegExp(
									re.source,
									re.flags.replace( /[gy]/g, '' )
							  )
							: re
					);
				}
			}
		}

		if ( changePatterns.length === 0 ) {
			continue;
		}

		// Check all changed files to see if any match this project's patterns
		for ( const filePath of changedFilePaths ) {
			// Skip files that don't start with this project's path
			if ( ! filePath.startsWith( node.path + '/' ) ) {
				continue;
			}

			const relativePath = filePath.slice(
				node.path.length + Number( node.path !== '' )
			);

			// Check if this file matches any of the project's CI config patterns
			const matchesPattern = changePatterns.some( ( pattern ) => {
				// Defensive: prevent stateful regex from skipping matches
				pattern.lastIndex = 0;
				return pattern.test( relativePath );
			} );

			if ( matchesPattern ) {
				// Add this file to the project's changes if not already present
				if ( ! changes[ node.name ] ) {
					changes[ node.name ] = [];
				}
				if ( ! changes[ node.name ].includes( relativePath ) ) {
					changes[ node.name ].push( relativePath );
				}
			}
		}
	}

	return changes;
}
