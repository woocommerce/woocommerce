/**
 * External dependencies
 */
import { execSync } from 'child_process';
import { dirname, join } from 'path';

const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );
const MONOREPO_UTILS_ROOT = join( MONOREPO_ROOT, 'tools', 'monorepo-utils' );
const CHANGELOGGER_PATH = join(
	MONOREPO_UTILS_ROOT,
	'vendor',
	'automattic',
	'jetpack-changelogger',
	'bin',
	'changelogger'
);
const COMPOSER_PATH = join( MONOREPO_UTILS_ROOT, 'composer.json' );

const cwd = process.cwd();
const args = process.argv.slice( 2 );
const argString = args.map( ( value ) => `"${ value }"` ).join( ' ' );

const command = `COMPOSER=${ COMPOSER_PATH } ${ CHANGELOGGER_PATH } ${ argString }`;
// const command = `${ CHANGELOGGER_PATH } ${ argString }`;

execSync( command, {
	cwd,
	encoding: 'utf-8',
	stdio: 'inherit',
} );
