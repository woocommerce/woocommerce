/**
 * External dependencies.
 */
const child_process = require( 'child_process' );
const fs = require( 'fs' );

/**
 * Given a path within a project,
 *
 * @param {string} absolutePath An absolute path to a project or a project file.
 * @return {string} The path to the project.
 */
function getProjectPathFromAbsolutePath( absolutePath ) {
	const matches = absolutePath.match(
		// Note the special handling for `plugins/woocommerce/client/*` packages.
		/((?:plugins\/woocommerce\/client\/[a-z0-9\-_.]+|plugins\/|packages\/[a-z0-9\-_.]+\/|tools\/)[a-z0-9\-_.]+)\/?/i
	);
	if ( ! matches ) {
		return null;
	}
	return matches[ 1 ];
}

/**
 * A record for a project and all of the changes that have occurred to it.
 *
 * @typedef {Object} ProjectChanges
 * @property {string}  path                 The path to the project.
 * @property {boolean} sourceFileChanges    Whether or not the project has changes to source files.
 * @property {boolean} documentationChanges Whether or not the project has documentation changes.
 * @property {boolean} testFileChanges      Whether or not the project has changes to test files.
 * @property {boolean} e2eTestFileChanges   Whether or not the project has changes to e2e test files.
 */

/**
 * Scans through the files that have been changed since baseRef and returns information about the projects that have
 * changes and the kind of changes that have taken place.
 *
 * @param {string} baseRef The base branch to check for changes against.
 * @return {Array.<ProjectChanges>} An array of projects and the kinds of changes that have occurred.
 */
function detectProjectChanges( baseRef ) {
	// Using a diff will not only allow us to find the projects that have changed but we can also identify the nature of the change.
	const output = child_process.execSync(
		`git diff --name-only ${ baseRef }`,
		{ encoding: 'utf8' }
	);
	const changedFilePaths = output.split( '\n' );

	// Scan all of the changed files into the projects they belong to.
	const projectsWithChanges = {};
	for ( const filePath of changedFilePaths ) {
		const projectPath = getProjectPathFromAbsolutePath( filePath );
		if ( ! projectPath ) {
			continue;
		}
		if ( ! projectsWithChanges[ projectPath ] ) {
			projectsWithChanges[ projectPath ] = [];
		}
		projectsWithChanges[ projectPath ].push( filePath );
	}

	// Scan through the projects that have changes and identify the type of changes that have occurred.
	const projectChanges = [];
	for ( const projectPath in projectsWithChanges ) {
		// We are only interested in projects that are part of our workspace.
		if ( ! fs.existsSync( `${ projectPath }/package.json` ) ) {
			continue;
		}

		// Keep track of the kind of changes that have occurred.
		let sourceFileChanges = false;
		let documentationChanges = false;
		let testFileChanges = false;
		let e2eTestFileChanges = false;

		// Now we can look through all of the files that have changed and figure out the type of changes that have occurred.
		const fileChanges = projectsWithChanges[ projectPath ];
		for ( const filePath of fileChanges ) {
			// Some types of changes are not interesting and should be ignored completely.
			if ( filePath.match( /\/changelog\//i ) ) {
				continue;
			}

			// We're going to be greedy with the detection of source files to avoid false negatives.
			if (
				filePath.match(
					/\.(?:(?:t|j)sx?|php|json|ya?ml|lock|xml|csv|txt|html)$/i
				)
			) {
				sourceFileChanges = true;
				continue;
			}

			// We also want to consider asset files to be source files.
			if (
				filePath.match(
					/\.(?:png|jpg|gif|scss|css|ttf|svg|eot|woff)$/i
				)
			) {
				sourceFileChanges = true;
				continue;
			}

			// We can be a bit stricter with documentation changes because they are only ever going to be markdown files.
			if ( filePath.match( /\.md$/i ) ) {
				documentationChanges = true;
				continue;
			}

			// With test changes we're going to make some assumptions about filenames and file paths.
			if (
				filePath.match(
					/\.(?:spec|test)\.(?:t|j)sx?)$|\/tests?\/(?!e2e)/i
				)
			) {
				testFileChanges = true;
				continue;
			}

			// We're going to base this assumption about E2E test file paths on what seems to be standard elsewhere in our ecosystem.
			if ( filePath.match( /\/test?\/e2e/i ) ) {
				e2eTestFileChanges = true;
				continue;
			}
		}

		// We only want to track a changed project when we have encountered file changes that we care about.
		if (
			! sourceFileChanges &&
			! documentationChanges &&
			! testFileChanges &&
			! e2eTestFileChanges
		) {
			continue;
		}

		// We can use the information we've collected to generate the project change object.
		projectChanges.push( {
			path: projectPath,
			sourceFileChanges,
			documentationChanges,
			testFileChanges,
			e2eTestFileChanges,
		} );
	}

	return projectChanges;
}

/**
 * Check the changes that occurred in each project and add any projects that are affected by those changes.
 *
 * @param {Array.<ProjectChanges>} projectChanges The project changes to cascade.
 * @return {Array.<ProjectChanges>} The project changes with any cascading changes.
 */
function cascadeProjectChanges( projectChanges ) {
	const cascadedChanges = {};

	// Scan through all of the changes and add any other projects that are affected by the changes.
	for ( const changes of projectChanges ) {
		// Populate the change object for the project if it doesn't already exist.
		// It might exist if the project has been affected by another project.
		if ( ! cascadedChanges[ changes.path ] ) {
			cascadedChanges[ changes.path ] = changes;
		}

		// Make sure that we are recording any "true" changes that have occurred either in the project itself or as a result of another project.
		for ( const property in changes ) {
			// We're going to assume the only properties on this object are "path" and the change flags.
			if ( property === 'path' ) {
				continue;
			}
			cascadedChanges[ changes.path ][ property ] =
				changes[ property ] ||
				cascadedChanges[ changes.path ][ property ];
		}

		// Use PNPM to get a list of dependent packages that may have been affected.
		const output = child_process.execSync(
			`pnpm list --filter='...{./${ changes.path }}' --only-projects --depth='-1' --parseable`,
			{ encoding: 'utf8' }
		);
		// The `--parseable` flag returns a list of package directories separated by newlines.
		const affectedProjects = output.split( '\n' );

		// At the VERY least PNPM will return the path to the project if it exists. The only way
		// this will happen is if the project isn't part of the workspace and we can ignore it.
		// We expect this to happen and thus haven't use the caret in the filter above.
		if ( ! affectedProjects ) {
			continue;
		}

		// Run through and decide whether or not the project has been affected by the changes.
		for ( const affected of affectedProjects ) {
			const affectedProjectPath =
				getProjectPathFromAbsolutePath( affected );
			if ( ! affectedProjectPath ) {
				continue;
			}

			// Skip the project we're checking against since it'll be in the results.
			if ( affectedProjectPath === changes.path ) {
				continue;
			}

			// Only certain changes will cascade to other projects.
			if ( ! changes.sourceFileChanges ) {
				continue;
			}

			// Populate the change object for the affected project if it doesn't already exist.
			if ( ! cascadedChanges[ affectedProjectPath ] ) {
				cascadedChanges[ affectedProjectPath ] = {
					path: affectedProjectPath,
					sourceFileChanges: false,
					documentationChanges: false,
					testFileChanges: false,
					e2eTestFileChanges: false,
				};
			}

			// Consider the source files to have changed in the affected project because they are dependent on the source files in the changed project.
			if ( changes.sourceFileChanges ) {
				cascadedChanges[ affectedProjectPath ].sourceFileChanges = true;
			}
		}
	}

	return Object.values( cascadedChanges );
}

/**
 * Details about a project and the tasks that should be run	for it.
 *
 * @typedef {Object} ProjectTasks
 * @property {string}  name               The name of the project.
 * @property {boolean} lint               Whether or not linting should be run for the project.
 * @property {boolean} hasTestEnvironment Whether or not the project has a test environment.
 * @property {boolean} test               Whether or not tests should be run for the project.
 * @property {boolean} e2e                Whether or not E2E tests should be run for the project.
 */

/**
 * Builds a task object for the project with support for limiting the tasks to only those that have changed.
 *
 * @param {string}              projectPath The path to the project.
 * @param {ProjectChanges|null} changes     Any changes that have occurred to the project.
 * @return {ProjectTasks} The tasks that should be run for the project.
 */
function buildTasksForProject( projectPath, changes ) {
	// Load the package file so we can check for task existence before adding them.
	const rawPackageFile = fs.readFileSync(
		`${ projectPath }/package.json`,
		'utf8'
	);
	const packageFile = JSON.parse( rawPackageFile );

	// Record the tasks that the project supports running.
	let lint = false;
	let hasTestEnvironment = false;
	let test = false;
	let e2e = false;
	if (
		packageFile.scripts?.lint &&
		( ! changes || changes.sourceFileChanges )
	) {
		lint = true;
	}
	if ( packageFile.scripts?.[ 'test:env:start' ] ) {
		hasTestEnvironment = true;
	}
	if (
		packageFile.scripts?.test &&
		( ! changes || changes.sourceFileChanges || changes.testFileChanges )
	) {
		test = true;
	}
	if (
		packageFile.scripts?.e2e &&
		( ! changes || changes.sourceFileChanges || changes.e2eTestFileChanges )
	) {
		e2e = true;
	}

	// There's nothing to do if the project has no tasks.
	if ( ! lint && ! test && ! e2e ) {
		return null;
	}

	return {
		name: packageFile.name,
		lint,
		hasTestEnvironment,
		test,
		e2e,
	};
}

/**
 * This function takes a list of project changes and generates a list of tasks that should be run for each project.
 *
 * @param {Array.<ProjectChanges>} projectChanges The project changes to generate tasks for.
 * @return {Array.<ProjectTasks>} All of the projects and the tasks that they should undertake.
 */
function generateProjectTasksForChanges( projectChanges ) {
	const projectTasks = [];

	// Scan through all of the changes and generate task objects for them.
	for ( const changes of projectChanges ) {
		const tasks = buildTasksForProject( changes.path, changes );
		if ( tasks ) {
			projectTasks.push( tasks );
		}
	}

	return projectTasks;
}

/**
 * Generates a list of tasks that should be run for each project in the workspace.
 *
 * @return {Array.<ProjectTasks>} All of the projects and the tasks that they should undertake.
 */
function generateProjectTasksForWorkspace() {
	// We can use PNPM to quickly get a list of every project in the workspace.
	const output = child_process.execSync(
		"pnpm list --filter='*' --only-projects --depth='-1' --parseable",
		{ encoding: 'utf8' }
	);
	// The `--parseable` flag returns a list of package directories separated by newlines.
	const workspaceProjects = output.split( '\n' );

	const projectTasks = [];
	for ( const project of workspaceProjects ) {
		const projectPath = getProjectPathFromAbsolutePath( project );
		if ( ! projectPath ) {
			continue;
		}

		const tasks = buildTasksForProject( projectPath, null );
		if ( tasks ) {
			projectTasks.push( tasks );
		}
	}

	return projectTasks;
}

/**
 * A CI matrix for the GitHub workflow.
 *
 * @typedef	{Object} CIMatrix
 * @property {string}  name               The name of the project.
 * @property {boolean} hasTestEnvironment Whether or not the project has a test environment.
 * @property {boolean} runTests           Whether or not tests should be run for the project.
 */

/**
 * Generates a matrix for the CI GitHub Workflow.
 *
 * @param {string} baseRef The base branch to check for changes against. If empty we check for everything.
 * @return {Array.<CIMatrix>} The CI matrices to be used in the CI workflows.
 */
function buildCIMatrices( baseRef ) {
	const matrices = [];

	// Build the project tasks based on the branch we are comparing against.
	let projectTasks = [];
	if ( baseRef ) {
		const projectChanges = detectProjectChanges( baseRef );
		const cascadedProjectChanges = cascadeProjectChanges( projectChanges );
		projectTasks = generateProjectTasksForChanges( cascadedProjectChanges );
	} else {
		projectTasks = generateProjectTasksForWorkspace();
	}

	// Note: For now all we care about is testing.
	for ( const tasks of projectTasks ) {
		// Right now we're only using this for the testing matrix.
		if ( ! tasks.test ) {
			continue;
		}

		matrices.push( {
			name: tasks.name,
			hasTestEnvironment: tasks.hasTestEnvironment,
			runTests: tasks.test,
		} );
	}

	return matrices;
}

module.exports = buildCIMatrices;
