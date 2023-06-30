/**
 * External dependencies
 */
import { execSync } from 'child_process';
import { dirname, join } from 'path';

const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );
const MONOREPO_UTILS_ROOT = join( MONOREPO_ROOT, 'tools', 'monorepo-utils' );

const CHANGELOGGER_PATH = join(
	MONOREPO_ROOT,
	'tools',
	'monorepo-utils',
	'vendor',
	'automattic',
	'jetpack-changelogger',
	'bin',
	'changelogger'
);

const cwd = process.cwd();
const args = process.argv.slice( 2 );
const argString = args.map( ( value ) => `"${ value }"` ).join( ' ' );
// const args =
// 	'"add" "-f" "uuuuupdate-add_gitignore_to_package" "-s" "patch" "-t" "tweak" "-e" "changelog message" "-n"';

execSync( CHANGELOGGER_PATH + ' ' + argString, {
	cwd,
	encoding: 'utf-8',
	stdio: 'inherit',
} );
