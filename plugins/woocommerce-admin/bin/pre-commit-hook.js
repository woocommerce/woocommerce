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
			name => name.endsWith( '.js' ) || name.endsWith( '.jsx' ) || name.endsWith( '.scss' ) || name.endsWith( '.php' )
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
	[ ...files.filter( file => ! file.endsWith( '.scss' ) && ! file.endsWith( '.php' ) ) ],
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

// PHP Lint.
let hasPhpLintErrors = false;
let phpFiles = '';

files.forEach( file => {
	if ( ! file.endsWith( '.php' ) ) {
		return;
	}

	try {
		execSync( `php -l -d display_errors=0 ${ file }` );
	} catch( err ) {
		hasPhpLintErrors = true;
	}

	phpFiles += '' + file;
} );

if ( hasPhpLintErrors ) {
	console.log(
		chalk.red( 'COMMIT ABORTED:' ),
		'The PHP linter reported some errors. ' +
			'Fix all PHP syntax errors found, ' +
			'and repeat the commit command.'
	);
	process.exit( 1 );
}

// Check PHP files with PHPCS.
if ( phpFiles ) {
	console.log( 'Running PHP_CodeSniffer...' );
	try {
		execSync( `./vendor/bin/phpcs --encoding=utf-8 -n -p ${ phpFiles }` );
	} catch( err ) {
		console.log( err.stdout.toString( 'utf8' ) );

		console.log(
			chalk.red( 'COMMIT ABORTED:' ),
			'Fix all PHP_CodeSniffer errors before continue. \n' +
				'Run the following command to automatic fix some of the errors: \n' +
				`./vendor/bin/phpcbf ${ phpFiles }`
		);

		process.exit( 1 );
	}
}
