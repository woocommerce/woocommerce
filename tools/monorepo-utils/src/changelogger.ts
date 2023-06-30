/**
 * External dependencies
 */
import { execSync } from 'child_process';
import { dirname, join } from 'path';

const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );
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

execSync( CHANGELOGGER_PATH + ' ' + argString, {
	cwd,
	encoding: 'utf-8',
	stdio: 'inherit',
} );
