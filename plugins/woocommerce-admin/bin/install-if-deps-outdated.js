#!/usr/bin/env node

/**
 * Performs an `pnpm install`. Since that's a costly operation,
 * it will only perform it if needed, that is, if the packages
 * installed at `node_modules` aren't in sync over what
 * `package-lock.json` has. For that, modification times of both
 * files will be compared. If the package-lock is newer, it means that
 * the packages at node_modules may be outdated. That will happen,
 * for example, when switching branches.
 */

const fs = require( 'fs' );
const spawnSync = require( 'child_process' ).spawnSync;

const needsInstall = () => {
	try {
		const shrinkwrapTime = fs.statSync( 'pnpm-lock.yaml' ).mtime;
		const nodeModulesTime = fs.statSync( 'node_modules' ).mtime;
		return shrinkwrapTime - nodeModulesTime > 1000; // In Windows, directory mtime has less precision than file mtime
	} catch ( e ) {
		return true;
	}
};

if ( needsInstall() ) {
	const installResult = spawnSync( 'pnpm', [ 'install' ], {
		shell: true,
		stdio: 'inherit',
	} ).status;

	if ( installResult ) {
		process.exit( installResult );
	}

	fs.utimesSync( 'node_modules', new Date(), new Date() );
}
