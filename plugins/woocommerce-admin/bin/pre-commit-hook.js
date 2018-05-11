#!/usr/bin/env node

/**
 * External dependencies
 */
const execSync = require( 'child_process' ).execSync;
const spawnSync = require( 'child_process' ).spawnSync;
const chalk = require( 'chalk' );
const fs = require( 'fs' );
const prettier = require( 'prettier' );

/**
 * Module constants
 */
const defaultPrettierConfig = undefined;
const sassPrettierConfig = { parser: 'scss' };

console.log(
	'\nBy contributing to this project, you license the materials you contribute ' +
		'under the GNU General Public License v2 (or later). All materials must have ' +
		'GPLv2 compatible licenses — see .github/CONTRIBUTING.md for details.\n\n'
);

/**
 * Parses the output of a git diff command into javascript file paths.
 *
 * @param   {String} command Command to run. Expects output like `git diff --name-only […]`
 * @returns {Array}          Paths output from git command
 */
function parseGitDiffToPathArray( command ) {
	return execSync( command )
		.toString()
		.split( '\n' )
		.map( name => name.trim() )
		.filter(
			name => name.endsWith( '.js' ) || name.endsWith( '.jsx' ) || name.endsWith( '.scss' )
		);
}

const dirtyFiles = new Set( parseGitDiffToPathArray( 'git diff --name-only --diff-filter=ACM' ) );

const files = parseGitDiffToPathArray( 'git diff --cached --name-only --diff-filter=ACM' );

// run prettier for any files in client/
files.forEach( file => {
	if ( 0 !== file.indexOf( 'client/' ) ) {
		return;
	}
	const text = fs.readFileSync( file, 'utf8' );
	// File has unstaged changes. It's a bad idea to modify and add it before commit.
	if ( dirtyFiles.has( file ) ) {
		console.log(
			chalk.red( `${ file } will not be auto-formatted because it has unstaged changes.` )
		);
		return;
	}

	const formattedText = prettier.format(
		text,
		file.endsWith( '.scss' ) ? sassPrettierConfig : defaultPrettierConfig
	);

	// No change required.
	if ( text === formattedText ) {
		return;
	}

	fs.writeFileSync( file, formattedText );
	console.log( `Prettier formatting file: ${ file }` );
	execSync( `git add ${ file }` );
} );

// linting should happen after formatting
const lintResult = spawnSync(
	'eslint',
	[ ...files.filter( file => ! file.endsWith( '.scss' ) ), '--', '--diff=index' ],
	{
		shell: true,
		stdio: 'inherit',
	}
);

if ( lintResult.status ) {
	console.log(
		chalk.red( 'COMMIT ABORTED:' ),
		'The linter reported some problems. ' +
			'If you are aware of them and it is OK, ' +
			'repeat the commit command with --no-verify to avoid this check.'
	);
	process.exit( 1 );
}
