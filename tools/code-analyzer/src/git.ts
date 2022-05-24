/**
 * External dependencies
 */
import { CliUx } from '@oclif/core';
import { execSync } from 'child_process';
import { join } from 'path';
import { tmpdir } from 'os';
import { readFileSync } from 'fs';

/**
 * Fetch branches from origin.
 *
 * @param {string}   branch branch/commit hash.
 * @param {Function} error  error print method.
 * @return {boolean} Promise.
 */
export const fetchBranch = (
	branch: string,
	error: ( s: string ) => void
): boolean => {
	CliUx.ux.action.start( `Fetching ${ branch }` );
	const branches = execSync( 'git branch', {
		encoding: 'utf-8',
	} );

	const branchExistsLocally = branches.includes( branch );

	if ( branchExistsLocally ) {
		CliUx.ux.action.stop();
		return true;
	}

	try {
		// Fetch branch.
		execSync( `git fetch origin ${ branch }` );
		// Create branch.
		execSync( `git branch ${ branch } origin/${ branch }` );
	} catch ( e ) {
		error( `Unable to fetch ${ branch }` );
		return false;
	}

	CliUx.ux.action.stop();
	return true;
};

/**
 * Generate a patch file into the temp directory and return its contents
 *
 * @param {string}   source  The GitHub repository.
 * @param {string}   compare Branch/commit hash to compare against the base.
 * @param {string}   base    Base branch/commit hash.
 * @param {Function} error   error print method.
 * @return {string} patch string.
 */
export const generatePatch = (
	source: string,
	compare: string,
	base: string,
	error: ( s: string ) => void
): string => {
	const filename = `${ source }-${ base }-${ compare }.patch`.replace(
		/\//g,
		'-'
	);
	const filepath = join( tmpdir(), filename );

	fetchBranch( base, error );
	fetchBranch( compare, error );

	CliUx.ux.action.start( 'Generating patch for ' + compare );

	try {
		const diffCommand = `git diff ${ base }...${ compare } > ${ filepath }`;
		execSync( diffCommand );
	} catch ( e ) {
		error(
			'Unable to create diff. Check that git origin, base branch, and compare branch all exist.'
		);
	}

	const content = readFileSync( filepath ).toString();

	CliUx.ux.action.stop();
	return content;
};
