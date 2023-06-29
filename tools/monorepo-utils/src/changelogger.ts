/**
 * External dependencies
 */
import { execSync } from 'child_process';
import { dirname, join } from 'path';

const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );
const MONOREPO_UTILS_ROOT = join( MONOREPO_ROOT, 'tools', 'monorepo-utils' );

// const cwd = process.cwd();
const args = process.argv.slice( 2 );

execSync( 'composer exec -- changelogger ' + args.join( ' ' ), {
	cwd: MONOREPO_UTILS_ROOT,
	encoding: 'utf-8',
	stdio: 'inherit',
} );
