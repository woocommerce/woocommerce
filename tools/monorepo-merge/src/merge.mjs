/**
 * External dependencies
 */
import { execFile } from 'node:child_process';
import { access, mkdtemp, realpath, rm } from 'node:fs/promises';
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
 * @return {Promise<string>} The command's trimmed standard output.
 */
async function run( command, args, failureMessage, options ) {
	try {
		// git merge prints a line per imported file, which can exceed the default 1 MiB buffer.
		const { stdout } = await execFileAsync( command, args, {
			maxBuffer: Infinity,
			...options,
		} );

		return stdout.trim();
	} catch ( error ) {
		// Merge conflicts are listed on stdout, so include it alongside stderr.
		const detail =
			[ error.stdout?.trim(), error.stderr?.trim() ]
				.filter( Boolean )
				.join( '\n' ) || error.message;
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

	// Ask git from the tool's own directory, so a copy outside tools/monorepo-merge cannot target an enclosing repository.
	const inMonorepo = { cwd: MONOREPO_ROOT };
	const notARepository = `This tool must sit at tools/monorepo-merge in the git checkout you merge into, but ${ MONOREPO_ROOT } is not that checkout's root`;
	const topLevel = await run(
		'git',
		[ 'rev-parse', '--show-toplevel' ],
		notARepository,
		{ cwd: import.meta.dirname }
	);
	if (
		( await realpath( topLevel ) ) !== ( await realpath( MONOREPO_ROOT ) )
	) {
		throw new Error( notARepository );
	}
	const currentBranch = await run(
		'git',
		[ 'branch', '--show-current' ],
		'Failed to read the current branch',
		inMonorepo
	);

	if (
		! ( await confirm(
			`This merges ${ source } and its history into ${ destination } on ${
				currentBranch
					? `branch "${ currentBranch }"`
					: 'a detached HEAD'
			} of ${ MONOREPO_ROOT }. Continue?`
		) )
	) {
		return;
	}

	const cloneDir = await mkdtemp( join( tmpdir(), 'monorepo-merge-' ) );
	let remoteAdded = false;
	try {
		const gitPath = 'https://github.com/' + source;
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
			return;
		}

		console.log( 'Merging repositories' );
		await run(
			'git',
			[ 'remote', 'add', source, cloneDir ],
			'Failed to add the cloned repository as a remote',
			inMonorepo
		);
		remoteAdded = true;
		await run(
			'git',
			[ 'fetch', source ],
			'Failed to fetch the cloned repository',
			inMonorepo
		);
		await run(
			'git',
			[
				'merge',
				'--allow-unrelated-histories',
				'--',
				`${ source }/${ branch }`,
			],
			'Failed to merge the repositories',
			inMonorepo
		);

		console.log( `Successfully merged ${ source } into ${ destination }` );
	} finally {
		// The merge only needs the fetched objects, so the clone and remote can go even after a conflict.
		if ( remoteAdded ) {
			await run(
				'git',
				[ 'remote', 'remove', source ],
				'Failed to remove the cloned repository remote',
				inMonorepo
			).catch( ( error ) => console.error( error.message ) );
		}
		await rm( cloneDir, { recursive: true, force: true } );
	}
}
