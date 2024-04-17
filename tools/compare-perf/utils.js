/* eslint-disable no-console */
const inquirer = require( 'inquirer' );
const fs = require( 'fs' );
const childProcess = require( 'child_process' );
const path = require( 'path' );
const chalk = require( 'chalk' );

/**
 * Utility to run a child script
 *
 * @typedef {NodeJS.ProcessEnv} Env
 *
 * @param {string}  script Script to run.
 * @param {string=} cwd    Working directory.
 * @param {Env=}    env    Additional environment variables to pass to the script.
 */
function runShellScript( script, cwd, env = {} ) {
	console.log( `Running '${ script }' in '${ cwd }'` );
	return new Promise( ( resolve, reject ) => {
		childProcess.exec(
			script,
			{
				cwd,
				env: {
					NO_CHECKS: 'true',
					PATH: process.env.PATH,
					HOME: process.env.HOME,
					USER: process.env.USER,
					...env,
				},
			},
			function ( error, stdout, stderr ) {
				if ( error ) {
					console.log( stdout ); // Sometimes the error message is thrown via stdout.
					console.log( stderr );
					reject( error );
				} else {
					resolve( true );
				}
			}
		);
	} );
}

/**
 * Small utility used to read an uncached version of a JSON file
 *
 * @param {string} fileName
 */
function readJSONFile( fileName ) {
	const data = fs.readFileSync( fileName, 'utf8' );
	return JSON.parse( data );
}

/**
 * Asks the user for a confirmation to continue or abort otherwise.
 *
 * @param {string}  message      Confirmation message.
 * @param {boolean} isDefault    Default reply.
 * @param {string}  abortMessage Abort message.
 */
async function askForConfirmation(
	message,
	isDefault = true,
	abortMessage = 'Aborting.'
) {
	const { isReady } = await inquirer.prompt( [
		{
			type: 'confirm',
			name: 'isReady',
			default: isDefault,
			message,
		},
	] );

	if ( ! isReady ) {
		chalk.log( chalk.bold.red( '\n' + abortMessage ) );
		process.exit( 1 );
	}
}

/**
 * Scans the given directory and returns an array of file paths.
 *
 * @param {string} dir The path to the directory to scan.
 *
 * @return {string[]} An array of file paths.
 */
function getFilesFromDir( dir ) {
	if ( ! fs.existsSync( dir ) ) {
		console.log( 'Directory does not exist: ', dir );
		return [];
	}

	return fs
		.readdirSync( dir, { withFileTypes: true } )
		.filter( ( dirent ) => dirent.isFile() )
		.map( ( dirent ) => path.join( dir, dirent.name ) );
}

module.exports = {
	askForConfirmation,
	readJSONFile,
	runShellScript,
	getFilesFromDir,
};
