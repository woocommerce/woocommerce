/**
 * External dependencies
 */
import { execFile } from 'node:child_process';
import { access, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { isAbsolute, join, relative, resolve, sep } from 'node:path';
import { promisify } from 'node:util';

/**
 * Internal dependencies
 */
import { confirm } from './confirm.mjs';

const execFileAsync = promisify( execFile );

export const MONOREPO_ROOT = resolve( import.meta.dirname, '../../..' );

/**
 * Runs a command and replaces any failure with a readable error.
 *
 * @param {string}   command        The executable to run.
 * @param {string[]} args           The command arguments.
 * @param {string}   failureMessage The error message to show when the command fails.
 * @param {Object}   [options]      Options for `child_process.execFile`.
 */
async function run( command, args, failureMessage, options ) {
	try {
		await execFileAsync( command, args, options );
	} catch ( error ) {
		const detail = error.stderr?.trim() || error.message;
		throw new Error( `${ failureMessage }: ${ detail }` );
	}
}

/**
 * Validates the source repository and the destination path.
 *
 * @param {string} source      The GitHub repository we are merging, in "organization/repository" format.
 * @param {string} destination The monorepo path to merge the repository into.
 */
export async function validateArgs( source, destination ) {
	// We only support pulling from GitHub so the format needs to match that.
	if ( ! source.match( /^[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_]+$/ ) ) {
		throw new Error(
			'The "source" argument must be in "organization/repository" format'
		);
	}

	const destinationPath = resolve( MONOREPO_ROOT, destination );
	const relativeDestination = relative( MONOREPO_ROOT, destinationPath );
	if (
		isAbsolute( destination ) ||
		relativeDestination === '' ||
		relativeDestination === '..' ||
		relativeDestination.startsWith( '..' + sep ) ||
		isAbsolute( relativeDestination )
	) {
		throw new Error(
			'The "destination" argument must point to a path inside the monorepo'
		);
	}

	const exists = await access( destinationPath ).then(
		() => true,
		() => false
	);
	if ( exists ) {
		throw new Error(
			'The "destination" argument points to a directory that already exists'
		);
	}
}

/**
 * Builds the git-filter-repo callback that updates imported references.
 *
 * Parenthesized references such as `(#123)` become pull request links.
 * Bare references such as `#123` are qualified with the source repository.
 *
 * @param {string} source The GitHub repository we are merging.
 * @return {string} The Python body of the message callback.
 */
export function createMessageCallback( source ) {
	const pullRequestPattern = String.raw`rb"\(#(?P<pull_request_number>\d+)\)"`;
	const pullRequestReplacement = String.raw`rb"(https://github.com/${ source }/pull/\g<pull_request_number>)"`;
	const issuePattern = String.raw`rb"(?<!\()#(?P<issue_number>\d+)(?!\))"`;
	const issueReplacement = String.raw`rb"${ source }#\g<issue_number>"`;
	const issueCallback = `re.sub(${ issuePattern }, ${ issueReplacement }, message)`;

	return `return re.sub(${ pullRequestPattern }, ${ pullRequestReplacement }, ${ issueCallback })`;
}

/**
 * Merges a GitHub repository into the current branch with its history, moved under `destination`.
 *
 * @param {string} source         The GitHub repository to merge, in "organization/repository" format.
 * @param {string} destination    The monorepo path to merge the repository into.
 * @param {Object} options
 * @param {string} options.branch The source branch to merge.
 */
export async function merge( source, destination, { branch } ) {
	for ( const command of [ 'git', 'git-filter-repo' ] ) {
		await run(
			command,
			[ '--version' ],
			`"${ command }" must be installed`
		);
	}
	await validateArgs( source, destination );

	if (
		! ( await confirm(
			`This merges ${ source } and its history into ${ destination } on your current branch. Continue?`
		) )
	) {
		return;
	}

	const gitPath = 'https://github.com/' + source;
	const cloneDir = join( tmpdir(), 'monorepo-merge', source );
	await rm( cloneDir, { recursive: true, force: true } );

	console.log( `Cloning ${ gitPath }` );
	await run(
		'git',
		[ 'clone', gitPath, cloneDir ],
		'Failed to clone the repository'
	);

	console.log( 'Rewriting the repository history' );
	await run(
		'git-filter-repo',
		[
			'--to-subdirectory-filter',
			destination,
			'--message-callback=' + createMessageCallback( source ),
		],
		'Failed to alter the repository history',
		{ cwd: cloneDir }
	);

	if (
		! ( await confirm(
			`Are you ready to merge ${ source } from ${ cloneDir }?`
		) )
	) {
		await rm( cloneDir, { recursive: true, force: true } );
		return;
	}

	console.log( 'Merging repositories' );
	await run(
		'git',
		[ 'remote', 'add', source, cloneDir ],
		'Failed to add the cloned repository as a remote'
	);
	await run(
		'git',
		[ 'fetch', source ],
		'Failed to fetch the cloned repository'
	);
	await run(
		'git',
		[
			'merge',
			'--allow-unrelated-histories',
			'--',
			`${ source }/${ branch }`,
		],
		'Failed to merge the repositories'
	);
	await run(
		'git',
		[ 'remote', 'remove', source ],
		'Failed to remove the cloned repository remote'
	);
	await rm( cloneDir, { recursive: true, force: true } );

	console.log( `Successfully merged ${ source } into ${ destination }` );
}
