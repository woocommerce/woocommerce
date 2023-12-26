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
	// Allow for download URLs in place of a version.
	if ( wpVersion.match( /[a-z]+:\/\//i ) ) {
		return wpVersion;
	}

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
 * @property {boolean} assetSourceChanges   Whether or not the project has changed to asset source files.
 * @property {boolean} documentationChanges Whether or not the project has documentation changes.
 * @property {boolean} phpTestChanges       Whether or not the project has changes to PHP test files.
 * @property {boolean} jsTestChanges        Whether or not the project has changes to JS test files.
 * @property {boolean} e2eTestChanges       Whether or not the project has changes to e2e test files.
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
		`git diff --relative --name-only ${ baseRef }`,
		{ encoding: 'utf8' }
	);
	const changedFilePaths = output.split( '\n' );

	// Scan all of the changed files into the projects they belong to.
	const projectsWithChanges = {};
	for ( const filePath of changedFilePaths ) {
		if ( ! filePath ) {
			continue;
		}
		
		const projectPath = getProjectPathFromAbsolutePath( filePath );
		if ( ! projectPath ) {
			console.log(
				`${ filePath }: ignoring change because it is not part of a project.`
			);
			continue;
		}
		if ( ! projectsWithChanges[ projectPath ] ) {
			projectsWithChanges[ projectPath ] = [];
		}
		projectsWithChanges[ projectPath ].push( filePath );
		console.log(
			`${ filePath }: marked as a change in project "${ projectPath }".`
		);
	}

	// Scan through the projects that have changes and identify the type of changes that have occurred.
	const projectChanges = [];
	for ( const projectPath in projectsWithChanges ) {
		// We are only interested in projects that are part of our workspace.
		if ( ! fs.existsSync( `${ projectPath }/package.json` ) ) {
			console.error( `${ projectPath }: no "package.json" file found.` );
			continue;
		}

		// Keep track of the kind of changes that have occurred.
		let phpTestChanges = false;
		let jsTestChanges = false;
		let e2eTestChanges = false;
		let phpSourceChanges = false;
		let jsSourceChanges = false;
		let assetSourceChanges = false;
		let documentationChanges = false;

		// Now we can look through all of the files that have changed and figure out the type of changes that have occurred.
		const fileChanges = projectsWithChanges[ projectPath ];
		for ( const filePath of fileChanges ) {
			// Some types of changes are not interesting and should be ignored completely.
			if ( filePath.match( /\/changelog\//i ) ) {
				console.log(
					`${ projectPath }: ignoring changelog file "${ filePath }".`
				);
				continue;
			}

			// As a preface, the detection of changes here is likely not absolutely perfect. We're going to be making some assumptions
			// about file extensions and paths in order to decide whether or not something is a type of change. This should still
			// be okay though since we have other cases where we check everything without looking at any changes to filter.

			// We can identify PHP test files using PSR-4 or WordPress file naming conventions. We also have
			// a fallback to any PHP files in a "tests" directory or its children.
			// Note: We need to check for this before we check for source files, otherwise we will
			// consider test file changes to be PHP source file changes.
			if (
				filePath.match( /(?:[a-z]+Test|-test|\/tests?\/[^\.]+)\.php$/i )
			) {
				phpTestChanges = true;
				console.log(
					`${ projectPath }: detected PHP test file change in "${ filePath }".`
				);
				continue;
			}

			// We can identify JS test files using Jest file file naming conventions. We also have
			// a fallback to any JS files in a "tests" directory or its children, but we need to
			// avoid picking up E2E test files in the process.
			// Note: We need to check for this before we check for source files, otherwise we will
			// consider test file changes to be JS source file changes.
			if (
				filePath.match(
					/(?:(?<!e2e[^\.]+)\.(?:spec|test)|\/tests?\/(?!e2e)[^\.]+)\.(?:t|j)sx?$/i
				)
			) {
				jsTestChanges = true;
				console.log(
					`${ projectPath }: detected JS test file change in "${ filePath }".`
				);
				continue;
			}

			// We're going to make an assumption about where E2E test files live based on what seems typical.
			if ( filePath.match( /\/test?\/e2e/i ) ) {
				e2eTestChanges = true;
				console.log(
					`${ projectPath }: detected E2E test file change in "${ filePath }".`
				);
				continue;
			}

			// Generally speaking, PHP files and changes to Composer dependencies affect PHP source code.
			if (
				filePath.match( /\.(?:php|html)$|composer\.(?:json|lock)$/i )
			) {
				phpSourceChanges = true;
				console.log(
					`${ projectPath }: detected PHP source file change in "${ filePath }".`
				);
				continue;
			}

			// JS changes should also include JSX and TS files.
			if (
				filePath.match( /\.(?:(?:t|j)sx?|json|html)$|package\.json$/i )
			) {
				jsSourceChanges = true;
				console.log(
					`${ projectPath }: detected JS source file change in "${ filePath }".`
				);
				continue;
			}

			// We should also keep an eye on asset file changes since these may affect
			// presentation in different tests that have expectations about this data.
			if (
				filePath.match(
					/\.(?:png|jpg|gif|scss|css|ttf|svg|eot|woff|xml|csv|txt|ya?ml)$/i
				)
			) {
				assetSourceChanges = true;
				console.log(
					`${ projectPath }: detected asset file change in "${ filePath }".`
				);
				continue;
			}

			// We can be a strict with documentation changes because they are only ever going to be markdown files.
			if ( filePath.match( /\.md$/i ) ) {
				documentationChanges = true;
				console.log(
					`${ projectPath }: detected documentation change in "${ filePath }".`
				);
				continue;
			}
		}

		// We only want to track a changed project when we have encountered file changes that we care about.
		if (
			! phpSourceChanges &&
			! jsSourceChanges &&
			! assetSourceChanges &&
			! documentationChanges &&
			! phpTestChanges &&
			! jsSourceChanges &&
			! e2eTestChanges
		) {
			console.log( `${ projectPath }: no changes detected.` );
			continue;
		}

		// We can use the information we've collected to generate the project change object.
		projectChanges.push( {
			path: projectPath,
			phpSourceChanges,
			jsSourceChanges,
			assetSourceChanges,
			documentationChanges,
			phpTestChanges,
			jsTestChanges,
			e2eTestChanges,
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

			// Only changes to source files will impact other projects.
			if (
				! changes.phpSourceChanges &&
				! changes.jsSourceChanges &&
				! changes.assetSourceChanges
			) {
				continue;
			}

			console.log(
				`${ changes.path }: cascading source file changes to ${ affectedProjectPath }.`
			);

			// Populate the change object for the affected project if it doesn't already exist.
			if ( ! cascadedChanges[ affectedProjectPath ] ) {
				cascadedChanges[ affectedProjectPath ] = {
					path: affectedProjectPath,
					phpSourceChanges: false,
					jsSourceChanges: false,
					assetSourceChanges: false,
					documentationChanges: false,
					phpTestChanges: false,
					jsTestChanges: false,
					e2eTestChanges: false,
				};
			}

			// Consider the source files to have changed in the affected project because they are dependent on the source files in the changed project.
			if ( changes.phpSourceChanges ) {
				cascadedChanges[ affectedProjectPath ].phpSourceChanges = true;
			}
			if ( changes.jsSourceChanges ) {
				cascadedChanges[ affectedProjectPath ].jsSourceChanges = true;
			}
			if ( changes.assetSourceChanges ) {
				cascadedChanges[
					affectedProjectPath
				].assetSourceChanges = true;
			}
		}
	}

	return Object.values( cascadedChanges );
}

/**
 * The valid commands that we can execute.
 *
 * @typedef {string} CommandType
 * @enum {CommandType}
 */
const COMMAND_TYPE = {
	Lint: 'lint',
	TestPHP: 'test:php',
	TestJS: 'test:js',
	E2E: 'e2e',
};

/**
 * Checks a command to see whether or not it is valid.
 *
 * @param {CommandType} command The command to check.
 * @return {boolean} Whether or not the command is valid.T
 */
function isValidCommand( command ) {
	for ( const commandType in COMMAND_TYPE ) {
		if ( COMMAND_TYPE[ commandType ] === command ) {
			return true;
		}
	}

	return false;
}

/**
 * Indicates whether or not the command is a test command.
 *
 * @param {CommandType} command The command to check.
 * @return {boolean} Whether or not the command is a test command.
 */
function isTestCommand( command ) {
	return (
		command === COMMAND_TYPE.TestPHP ||
		command === COMMAND_TYPE.TestJS ||
		command === COMMAND_TYPE.E2E
	);
}

/**
 * Details about a task that should be run for a project.
 *
 * @typedef {Object} ProjectTask
 * @property {string}                 name           The name of the task.
 * @property {Array.<CommandType>}    commandsToRun  The commands that the project should run.
 * @property {Object.<string,string>} customCommands Any commands that should be run in place of the default commands.
 * @property {string|null}            testEnvCommand The command that should be run to start the test environment if one is needed.
 * @property {Object.<string,string>} testEnvConfig  Any configuration for the test environment if one is needed.
 */

/**
 * Parses the task configuration from the package.json file and returns a task object.
 *
 * @param {Object}              packageFile        The package file for the project.
 * @param {Object}              config             The taw task configuration.
 * @param {Array.<CommandType>} commandsForChanges The commands that we should run for the project.
 * @param {ProjectTask|null}    parentTask         The task that this task is a child of.
 * @return {ProjectTask|null} The parsed task.
 */
function parseTaskConfig(
	packageFile,
	config,
	commandsForChanges,
	parentTask
) {
	// Child tasks are required to have a name because otherwise
	// every task for a project would be named "default".
	let taskName = 'default';
	if ( parentTask ) {
		taskName = config.name;
		if ( ! taskName ) {
			throw new Error( `${ packageFile.name }: missing name for task.` );
		}
	}

	// When the config object declares a command filter we should remove any
	// of the commands it contains from the list of commands to run.
	if ( config?.commandFilter ) {
		// Check for invalid commands being used since they won't do anything.
		for ( const command of config.commandFilter ) {
			if ( ! isValidCommand( command ) ) {
				throw new Error(
					`${ packageFile.name }: invalid command filter type of "${ command }" for task "${ taskName }".`
				);
			}
		}

		// Apply the command filter.
		commandsForChanges = commandsForChanges.filter( ( command ) =>
			config.commandFilter.includes( command )
		);
	}

	// Custom commands developers to support a command without having to use the
	// standardized script name for it. For ease of use we will add parent task
	// custom commands to children and allow the children to override any
	// specific tasks they want.
	const customCommands = Object.assign(
		{},
		parentTask?.customCommands ?? {}
	);
	if ( config?.customCommands ) {
		for ( const customCommandType in config.customCommands ) {
			// Check for invalid commands being mapped since they won't do anything.
			if ( ! isValidCommand( customCommandType ) ) {
				throw new Error(
					`${ packageFile.name }: invalid custom command type "${ customCommandType } for task "${ taskName }".`
				);
			}

			// Custom commands may have tokens that we need to remove in order to check them for existence.
			const split =
				config.customCommands[ customCommandType ].split( ' ' );
			const customCommand = split[ 0 ];

			if ( ! packageFile.scripts?.[ customCommand ] ) {
				throw new Error(
					`${ packageFile.name }: unknown custom "${ customCommandType }" command "${ customCommand }" for task "${ taskName }".`
				);
			}

			// We only need to bother with commands we can actually run.
			if ( commandsForChanges.includes( customCommandType ) ) {
				customCommands[ customCommandType ] =
					config.customCommands[ customCommandType ];
			}
		}
	}

	// Our goal is to run only the commands that have changes, however, not all
	// projects will have scripts for all of the commands we want to run.
	const commandsToRun = [];
	for ( const command of commandsForChanges ) {
		// We have already filtered and confirmed custom commands.
		if ( customCommands[ command ] ) {
			commandsToRun.push( command );
			continue;
		}

		// Commands that don't have a script to run should be ignored.
		if ( ! packageFile.scripts?.[ command ] ) {
			continue;
		}

		commandsToRun.push( command );
	}

	// We don't want to create a task if there aren't any commands to run.
	if ( ! commandsToRun.length ) {
		return null;
	}

	// The test environment command only needs to be set when a test environment is needed.
	let testEnvCommand = null;
	if ( commandsToRun.some( ( command ) => isTestCommand( command ) ) ) {
		if ( config?.testEnvCommand ) {
			// Make sure that a developer hasn't put in a test command that doesn't exist.
			if ( ! packageFile.scripts?.[ config.testEnvCommand ] ) {
				throw new Error(
					`${ packageFile.name }: unknown test environment command "${ config.testEnvCommand }" for task "${ taskName }".`
				);
			}

			testEnvCommand =
				config?.testEnvCommand ?? parentTask?.testEnvCommand;
		} else if ( packageFile.scripts?.[ 'test:env:start' ] ) {
			testEnvCommand = 'test:env:start';
		}
	}

	// The test environment configuration should also cascade from parent task to child task.
	const testEnvConfig = Object.assign(
		{},
		parentTask?.testEnvConfig ?? {},
		config?.testEnvConfig ?? {}
	);

	return {
		name: taskName,
		commandsToRun,
		customCommands,
		testEnvCommand,
		testEnvConfig,
	};
}

/**
 * Details about a project and the tasks that should be run	for it.
 *
 * @typedef {Object} ProjectTasks
 * @property {string}              name  The name of the project.
 * @property {Array.<ProjectTask>} tasks The tasks that should be run for the project.
 */

/**
 * Evaluates the given changes against the possible commands and returns those that should run as
 * a result of the change criteria being met.
 *
 * @param {ProjectChanges|null} changes Any changes that have occurred to the project.
 * @return {Array.<string>} The commands that can be run for the project.
 */
function getCommandsForChanges( changes ) {
	// Here are all of the commands that we support and the change criteria that they require to execute.
	// We treat the command's criteria as passing if any of the properties are true.
	const commandCriteria = {
		[ COMMAND_TYPE.Lint ]: [
			'phpSourceChanges',
			'jsSourceChanges',
			'assetSourceChanges',
			'phpTestChanges',
			'jsTestChanges',
		],
		[ COMMAND_TYPE.TestPHP ]: [ 'phpSourceChanges', 'phpTestChanges' ],
		[ COMMAND_TYPE.TestJS ]: [ 'jsSourceChanges', 'jsTestChanges' ],
		//[ COMMAND_TYPE.E2E ]: [ 'phpSourceChanges', 'jsSourceChanges', 'assetSourceChanges', 'e2eTestFileChanges' ],
	};

	// We only want the list of possible commands to contain those that
	// the project actually has and meet the criteria for execution.
	const commandsForChanges = [];
	for ( const command in commandCriteria ) {
		// The criteria only needs to be checked if there is a change object to evaluate.
		if ( changes ) {
			let commandCriteriaMet = false;
			for ( const criteria of commandCriteria[ command ] ) {
				// Confidence check to make sure the criteria wasn't misspelled.
				if ( ! changes.hasOwnProperty( criteria ) ) {
					throw new Error(
						`Invalid criteria "${ criteria }" for command "${ command }".`
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

			console.log( `${ changes.path }: command "${ command }" added based on given changes.` );
		}

		commandsForChanges.push( command );
	}

	return commandsForChanges;
}

/**
 * Builds a task object for the project with support for limiting the tasks to only those that have changed.
 *
 * @param {string}              projectPath The path to the project.
 * @param {ProjectChanges|null} changes     Any changes that have occurred to the project.
 * @return {ProjectTasks|null} The tasks that should be run for the project.
 */
function buildTasksForProject( projectPath, changes ) {
	// There's nothing to do if the project has no tasks.
	const commandsForChanges = getCommandsForChanges( changes );
	if ( ! commandsForChanges.length ) {
		return null;
	}

	// Load the package file so we can check for task existence before adding them.
	const rawPackageFile = fs.readFileSync(
		`${ projectPath }/package.json`,
		'utf8'
	);
	const packageFile = JSON.parse( rawPackageFile );

	// We're going to parse each of the projects and add them to the list of tasks if necessary.
	const projectTasks = [];

	// Parse the task configuration from the package.json file.
	const parentTask = parseTaskConfig(
		packageFile,
		packageFile.config?.ci,
		commandsForChanges,
		null
	);
	if ( parentTask ) {
		projectTasks.push( parentTask );
	}

	if ( packageFile.config?.ci?.additionalTasks ) {
		for ( const additionalTask of packageFile.config.ci.additionalTasks ) {
			const task = parseTaskConfig(
				packageFile,
				additionalTask,
				commandsForChanges,
				parentTask
			);
			if ( task ) {
				projectTasks.push( task );
			}
		}
	}

	if ( ! projectTasks.length ) {
		return null;
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
 * @property {string}                 projectName    The name of the project.
 * @property {string}                 taskName       The name of the task.
 * @property {Object.<string,string>} testEnvVars    The environment variables for the test environment.
 * @property {string|null}            lintCommand    The command to run if linting is necessary.
 * @property {string|null}            phpTestCommand The command to run if PHP tests are necessary.
 * @property {string|null}            jsTestCommand  The command to run if JS tests are necessary.
 * @property {string|null}            e2eCommand     The command to run if E2E is necessary.
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
		try {
			envVars.WP_ENV_CORE = await parseWPVersion(
				testEnvConfig.wpVersion
			);
		} catch ( error ) {
			throw new Error(
				`Failed to parse WP version: ${ error.message }.`
			);
		}
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
 * @param {CommandType}            command     The command to run.
 * @param {Object.<string,string>} tokenValues Any tokens that should be replaced and their associated values.
 * @return {string|null} The command that should be run for the task or null if the command should not be run.
 */
function getCommandForMatrix( task, command, tokenValues ) {
	if ( ! task.commandsToRun.includes( command ) ) {
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
				match[ 0 ],
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
				testEnvCommand: task.testEnvCommand,
				testEnvVars: await parseTestEnvConfig( task.testEnvConfig ),
				lintCommand: getCommandForMatrix(
					task,
					COMMAND_TYPE.Lint,
					commandTokens
				),
				phpTestCommand: getCommandForMatrix(
					task,
					COMMAND_TYPE.TestPHP,
					commandTokens
				),
				jsTestCommand: getCommandForMatrix(
					task,
					COMMAND_TYPE.TestJS,
					commandTokens
				),
				e2eCommand: getCommandForMatrix(
					task,
					COMMAND_TYPE.E2E,
					commandTokens
				),
			} );
		}
	}

	return matrix;
}

module.exports = buildCIMatrix;
