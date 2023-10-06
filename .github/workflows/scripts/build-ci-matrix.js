/**
 * External dependencies.
 */
const child_process = require( 'child_process' );
const fs = require( 'fs' );
const https = require( 'http' );

/**
 * Uses the WordPress API to get the downlod URL to the latest version of an X.X version line. This
 * also accepts "latest-X" to get an offset from the latest version of WordPress.
 *
 * @param {string} wpVersion The version of WordPress to look for.
 * @return {Promise.<string>} The precise WP version download URL.
 */
async function getPreciseWPVersionURL( wpVersion ) {
	return new Promise( ( resolve, reject ) => {
		// We're going to use the WordPress.org API to get information about available versions of WordPress.
		const request = https.get(
			'http://api.wordpress.org/core/stable-check/1.0/',
			( response ) => {
				// Listen for the response data.
				let responseData = '';
				response.on( 'data', ( chunk ) => {
					responseData += chunk;
				} );

				// Once we have the entire response we can process it.
				response.on( 'end', () =>
					resolve( JSON.parse( responseData ) )
				);
			}
		);

		request.on( 'error', ( error ) => {
			reject( error );
		} );
	} ).then( ( allVersions ) => {
		// Note: allVersions is an object where the keys are the version and the value is information about the version's status.

		// If we're requesting a "latest" offset then we need to figure out what version line we're offsetting from.
		const latestSubMatch = wpVersion.match( /^latest(?:-([0-9]+))?$/i );
		if ( latestSubMatch ) {
			for ( const version in allVersions ) {
				if ( allVersions[ version ] !== 'latest' ) {
					continue;
				}

				// We don't care about the patch version because we will
				// the latest version from the version line below.
				const versionParts = version.match( /^([0-9]+)\.([0-9]+)/ );

				// We're going to subtract the offset to figure out the right version.
				let offset = parseInt( latestSubMatch[ 1 ] ?? 0, 10 );
				let majorVersion = parseInt( versionParts[ 1 ], 10 );
				let minorVersion = parseInt( versionParts[ 2 ], 10 );
				while ( offset > 0 ) {
					minorVersion--;
					if ( minorVersion < 0 ) {
						majorVersion--;
						minorVersion = 9;
					}
					offset--;
				}

				// Set the version that we found in the offset.
				wpVersion = majorVersion + '.' + minorVersion;
			}
		}

		// Scan through all of the versions to find the latest version in the version line.
		let latestVersion = null;
		let latestPatch = -1;
		for ( const v in allVersions ) {
			// Parse the version so we can make sure we're looking for the latest.
			const matches = v.match( /([0-9]+)\.([0-9]+)(?:\.([0-9]+))?/ );

			// We only care about the correct minor version.
			const minor = `${ matches[ 1 ] }.${ matches[ 2 ] }`;
			if ( minor !== wpVersion ) {
				continue;
			}

			// Track the latest version in the line.
			const patch =
				matches[ 3 ] === undefined ? 0 : parseInt( matches[ 3 ], 10 );

			if ( patch > latestPatch ) {
				latestPatch = patch;
				latestVersion = v;
			}
		}

		if ( ! latestVersion ) {
			throw new Error(
				`Unable to find latest version for version line ${ wpVersion }.`
			);
		}

		return `https://wordpress.org/wordpress-${ latestVersion }.zip`;
	} );
}

/**
 * Parses a display-friendly WordPress version and returns a link to download the given version.
 *
 * @param {string} wpVersion A display-friendly WordPress version. Supports ("master", "trunk", "nightly", "latest", "latest-X", "X.X" for version lines, and "X.X.X" for specific versions)
 * @return {Promise.<string>} A link to download the given version of WordPress.
 */
async function parseWPVersion( wpVersion ) {
	// Start with versions we can infer immediately.
	switch ( wpVersion ) {
		case 'master':
		case 'trunk': {
			return 'WordPress/WordPress#master';
		}

		case 'nightly': {
			return 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
		}

		case 'latest': {
			return 'https://wordpress.org/latest.zip';
		}
	}

	// We can also infer X.X.X versions immediately.
	const parsedVersion = wpVersion.match( /^([0-9]+)\.([0-9]+)\.([0-9]+)$/ );
	if ( parsedVersion ) {
		// Note that X.X.0 versions use a X.X download URL.
		let urlVersion = `${ parsedVersion[ 1 ] }.${ parsedVersion[ 2 ] }`;
		if ( parsedVersion[ 3 ] !== '0' ) {
			urlVersion += `.${ parsedVersion[ 3 ] }`;
		}

		return `https://wordpress.org/wordpress-${ urlVersion }.zip`;
	}

	// Since we haven't found a URL yet we're going to use the WordPress.org API to try and infer one.
	return getPreciseWPVersionURL( wpVersion );
}

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
 * @property {boolean} phpSourceChanges     Whether or not the project has changes to PHP source files.
 * @property {boolean} jsSourceChanges      Whether or not the project has changes to JS source files.
 * @property {boolean} sourceFileChanges    A greedier indication of whether any files that might be source files have changed.
 * @property {boolean} documentationChanges Whether or not the project has documentation changes.
 * @property {boolean} phpTestFileChanges   Whether or not the project has changes to PHP test files.
 * @property {boolean} jsTestFileChanges    Whether or not the project has changes to JS test files.
 * @property {boolean} testFileChanges      A greedier indication of whether any test files have changed.
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
		let phpSourceChanges = false;
		let jsSourceChanges = false;
		let sourceFileChanges = false;
		let documentationChanges = false;
		let phpTestFileChanges = false;
		let jsTestFileChanges = false;
		let testFileChanges = false;
		let e2eTestFileChanges = false;

		// Now we can look through all of the files that have changed and figure out the type of changes that have occurred.
		const fileChanges = projectsWithChanges[ projectPath ];
		for ( const filePath of fileChanges ) {
			// Some types of changes are not interesting and should be ignored completely.
			if ( filePath.match( /\/changelog\//i ) ) {
				continue;
			}

			// As part of the detection of source files we are going to try and identify the type of source file that was changed.
			// This isn't necessarily going to be completely perfect but it should be good enough for our purposes.
			phpSourceChanges = !! filePath.match(
				/\.(?:php|html)$|composer.(?:json|lock)$/i
			);
			jsSourceChanges = !! filePath.match(
				/\.(?:(?:t|j)sx?|json|html)$|package.json$/i
			);

			// We're also going to have a greedy detection of source file changes just in case we missed something.
			if (
				phpSourceChanges ||
				jsSourceChanges ||
				filePath.match( /\.(?:ya?ml|lock|xml|csv|txt)$/i )
			) {
				sourceFileChanges = true;
			}

			// We also want to consider asset files to be source files.
			if (
				! sourceFileChanges &&
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

			// For the detection of test files we are going to make some assumptions about file
			// paths based on common practices in our community as well as the wider ecosystem.
			phpTestFileChanges = !! filePath.match(
				/(?:[a-z]+Test|-test|\/tests?\/[^\.]+)\.php$/i
			);
			jsTestFileChanges = !! filePath.match(
				/(?:(?<!e2e[^\.]+)\.(?:spec|test)|\/tests?\/(?!e2e)[^\.]+)\.(?:t|j)sx?$/i
			);
			testFileChanges = phpTestFileChanges || jsTestFileChanges;

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
			phpSourceChanges,
			jsSourceChanges,
			sourceFileChanges,
			documentationChanges,
			phpTestFileChanges,
			jsTestFileChanges,
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
		// Note: This is actually a pretty slow way of doing this. If we find it is
		// taking too long we can instead use `--depth="Infinity" --json` and then
		// traverse the dependency tree ourselves.
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
					phpSourceChanges: false,
					jsSourceChanges: false,
					sourceFileChanges: false,
					documentationChanges: false,
					phpTestFileChanges: false,
					jsTestFileChanges: false,
					e2eTestFileChanges: false,
				};
			}

			// Consider the source files to have changed in the affected project because they are dependent on the source files in the changed project.
			if ( changes.phpSourceChanges ) {
				cascadedChanges[ affectedProjectPath ].phpSourceChanges = true;
			}
			if ( changes.jsSourceChanges ) {
				cascadedChanges[ affectedProjectPath ].jsSourceChanges = true;
			}
			if ( changes.sourceFileChanges ) {
				cascadedChanges[ affectedProjectPath ].sourceFileChanges = true;
			}
		}
	}

	return Object.values( cascadedChanges );
}

/**
 * Details about a task that should be run for a project.
 *
 * @typedef {Object} ProjectTask
 * @property {string}                 name                 The name of the task.
 * @property {Array.<string>}         commands             The commands that the project should run.
 * @property {Object.<string,string>} customCommands       Any commands that should be run in place of the default commands.
 * @property {boolean}                needsTestEnvironment Whether or not the project needs a test environment.
 * @property {Object.<string,string>} testEnvConfig        Any configuration for the test environment if one is needed.
 */

/**
 * Details about a project and the tasks that should be run	for it.
 *
 * @typedef {Object} ProjectTasks
 * @property {string}              name  The name of the project.
 * @property {Array.<ProjectTask>} tasks The tasks that should be run for the project.
 */

/**
 * Checks the commands that are available for a project and returns the ones that meet the change criteria.
 *
 * @param {Object}              packageFile The package.json content for the project.
 * @param {ProjectChanges|null} changes     Any changes that have occurred to the project.
 * @return {Array.<string>} The commands that can be run for the project.
 */
function getPossibleCommands( packageFile, changes ) {
	// Here are all of the commands that we support and the change criteria that they require to execute.
	// We treat the command's criteria as passing if any of the properties are true.
	const commandCriteria = {
		lint: [ 'sourceFileChanges' ],
		'test:php': [ 'phpSourceChanges', 'phpTestFileChanges' ],
		'test:js': [ 'jsSourceChanges', 'jsTestFileChanges' ],
		e2e: [ 'sourceFileChanges', 'e2eTestFileChanges' ],
	};

	// Projects can override the default commands to execute with custom commands.
	const customCommands = packageFile.config?.ci?.customCommands ?? {};

	// Custom commands may have tokens in them that we need to remove.
	// This lets us easily check for the command existence.
	for ( const command in customCommands ) {
		const split = customCommands[ command ].split( ' ' );
		customCommands[ command ] = split[0];
	}

	// We only want the list of possible commands to contain those that
	// the project actually has and meet the criteria for execution.
	const possibleCommands = [];
	for ( const command in commandCriteria ) {
		// We can only run commands that actually exist.
		const commandScript = customCommands[ command ] ?? command;
		if ( ! packageFile.scripts?.[ commandScript ] ) {
			// Since developers define the custom commands themselves we should make
			// sure that they didn't make a mistake and set one that doesn't exist.
			if ( customCommands[ command ] ) {
				throw new Error(
					`Unknown custom command "${ commandScript }".`
				);
			}

			continue;
		}

		// The criteria only needs to be checked if there is a change object to evaluate.
		if ( changes ) {
			let commandCriteriaMet = false;
			for ( const criteria of commandCriteria[ command ] ) {
				// Confidence check to make sure the criteria wasn't misspelled.
				if ( ! changes.hasOwnProperty( criteria ) ) {
					throw new Error(
						`Unknown criteria "${ criteria }" for command "${ command }".`
					);
				}

				if ( changes[ criteria ] ) {
					commandCriteriaMet = true;
					break;
				}
			}

			// As long as we meet one of the criteria requirements we can add the command.
			if ( ! commandCriteriaMet ) {
				continue;
			}
		}

		possibleCommands.push( command );
	}

	return possibleCommands;
}

/**
 * Builds a task object for the project with support for limiting the tasks to only those that have changed.
 *
 * @param {string}              projectPath The path to the project.
 * @param {ProjectChanges|null} changes     Any changes that have occurred to the project.
 * @return {ProjectTasks|null} The tasks that should be run for the project.
 */
function buildTasksForProject( projectPath, changes ) {
	// Load the package file so we can check for task existence before adding them.
	const rawPackageFile = fs.readFileSync(
		`${ projectPath }/package.json`,
		'utf8'
	);
	const packageFile = JSON.parse( rawPackageFile );

	// There's nothing to do if the project has no tasks.
	const possibleCommands = getPossibleCommands( packageFile, changes );
	if ( ! possibleCommands.length ) {
		return null;
	}

	// Projects can override the default commands to execute with custom commands.
	// This lets us talk about the default commands internally but change what
	// command is actually run when it comes time to execute the task.
	const customCommands = packageFile.config?.ci?.customCommands ?? {};

	// Certain tasks may require a test environment if one exists.
	const hasTestEnvironment = !! packageFile.scripts?.[ 'test:env:start' ];
	const needsTestEnvironmentFn = ( task ) =>
		task === 'test:php' || task === 'test:js' || task === 'e2e';
	const testEnvConfig = packageFile.config?.ci?.testEnvConfig ?? {};

	// The package tasks will include both the default tasks and any tasks that have been added by the package.json configuration.
	const projectTasks = [
		{
			name: packageFile.config?.ci?.name ?? 'default',
			commands: possibleCommands,
			customCommands,
			needsTestEnvironment:
				hasTestEnvironment &&
				possibleCommands.some( needsTestEnvironmentFn ),
			testEnvConfig,
		},
	];

	if ( packageFile.config?.ci?.additionalTasks ) {
		for ( const additionalTask of packageFile.config.ci.additionalTasks ) {
			if ( ! additionalTask.name ) {
				throw new Error(
					`No name specified for additional task in ${ projectPath }.`
				);
			}

			if (
				! additionalTask.commands ||
				! additionalTask.commands.length
			) {
				throw new Error(
					`No commands specified for additional task "${ additionalTask.name }" in ${ projectPath }.`
				);
			}

			// We only want to add the commands that are possible for this project.
			const taskCommands = additionalTask.commands.filter( ( command ) =>
				possibleCommands.includes( command )
			);

			// Don't add the additional task if it wouldn't run any commands.
			if ( ! taskCommands.length ) {
				continue;
			}

			// Make sure to use the additional task's custom commands as overrides instead of a replacement for the entire object.
			const taskCustomCommands = Object.assign(
				{},
				customCommands,
				additionalTask.customCommands ?? {}
			);

			// Make sure to use the additional task's configuration as overrides instead of a replacement for the entire config object.
			const taskNeedsTestEnvironment =
				hasTestEnvironment &&
				taskCommands.some( needsTestEnvironmentFn );
			const taskTestEnvConfig = Object.assign(
				{},
				testEnvConfig,
				additionalTask.testEnvConfig ?? {}
			);

			// We can create the task now that we're sure we have the whole configuration.
			projectTasks.push( {
				name: additionalTask.name,
				commands: taskCommands,
				customCommands: taskCustomCommands,
				needsTestEnvironment: taskNeedsTestEnvironment,
				testEnvConfig: taskTestEnvConfig,
			} );
		}
	}

	return {
		name: packageFile.name,
		tasks: projectTasks,
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
 * @property {string}                 projectName          The name of the project.
 * @property {string}                 taskName             The name of the task.
 * @property {boolean}                needsTestEnvironment Whether or not the task needs a test environment.
 * @property {Object.<string,string>} testEnvVars          The environment variables for the test environment.
 * @property {boolean}                runLint              Whether or not linting should be run for the project.
 * @property {boolean}                runPHPTests          Whether or not PHP tests should be run for the project.
 * @property {boolean}                runJSTests           Whether or not JS tests should be run for the project.
 * @property {boolean}                runE2E               Whether or not E2E tests should be run for the project.
 */

/**
 * Parses the test environment's configuration and returns any environment variables that
 * should be set.
 *
 * @param {Object} testEnvConfig The test environment configuration.
 * @return {Promise.<Object>} The environment variables for the test environment.
 */
async function parseTestEnvConfig( testEnvConfig ) {
	const envVars = {};

	// Convert `wp-env` configuration options to environment variables.
	if ( testEnvConfig.wpVersion ) {
		envVars.WP_ENV_CORE = await parseWPVersion( testEnvConfig.wpVersion );
	}
	if ( testEnvConfig.phpVersion ) {
		envVars.WP_ENV_PHP_VERSION = testEnvConfig.phpVersion;
	}

	return envVars;
}

/**
 * Generates a command for the task that can be executed in the CI matrix. This will check the task
 * for the command, apply any command override, and replace any valid tokens with their values.
 *
 * @param {ProjectTask}            task        The task to get the command for.
 * @param {string}                 command     The command to run.
 * @param {Object.<string,string>} tokenValues Any tokens that should be replaced and their associated values.
 * @return {string|null} The command that should be run for the task or null if the command should not be run.
 */
function getCommandForMatrix( task, command, tokenValues ) {
	if ( ! task.commands.includes( command ) ) {
		return null;
	}

	// Support overriding the default command with a custom one.
	command = task.customCommands[ command ] ?? command;

	// Replace any of the tokens that are used in commands with their values if one exists.
	let matrixCommand = command;
	const matches = command.matchAll( /\${([a-z0-9_\-]+)}/gi );
	if ( matches ) {
		for ( const match of matches ) {
			if ( ! tokenValues.hasOwnProperty( match[ 1 ] ) ) {
				throw new Error(
					`Command "${ command }" contains unknown token "${ match[ 1 ] }".`
				);
			}

			matrixCommand = matrixCommand.replace(
				'${' + match + '}',
				tokenValues[ match[ 1 ] ]
			);
		}
	}

	return matrixCommand;
}

/**
 * Generates a matrix for the CI GitHub Workflow.
 *
 * @param {string} baseRef The base branch to check for changes against. If empty we check for everything.
 * @return {Promise.<Array.<CIMatrix>>} The CI matrix to be used in the CI workflows.
 */
async function buildCIMatrix( baseRef ) {
	const matrix = [];

	// Build the project tasks based on the branch we are comparing against.
	let projectTasks = [];
	if ( baseRef ) {
		const projectChanges = detectProjectChanges( baseRef );
		const cascadedProjectChanges = cascadeProjectChanges( projectChanges );
		projectTasks = generateProjectTasksForChanges( cascadedProjectChanges );
	} else {
		projectTasks = generateProjectTasksForWorkspace();
	}

	// Prepare the tokens that are able to be replaced in commands.
	const commandTokens = {
		baseRef: baseRef ?? '',
	};

	// Parse the tasks and generate matrix entries for each of them.
	for ( const project of projectTasks ) {
		for ( const task of project.tasks ) {
			matrix.push( {
				projectName: project.name,
				taskName: task.name,
				needsTestEnvironment: task.needsTestEnvironment,
				testEnvVars: await parseTestEnvConfig( task.testEnvConfig ),
				lintCommand: getCommandForMatrix( task, 'lint', commandTokens ),
				phpTestCommand: getCommandForMatrix(
					task,
					'test:php',
					commandTokens
				),
				jsTestCommand: getCommandForMatrix(
					task,
					'test:js',
					commandTokens
				),
				e2eCommand: getCommandForMatrix( task, 'e2e', commandTokens ),
			} );
		}
	}

	return matrix;
}

module.exports = buildCIMatrix;
