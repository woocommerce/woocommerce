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
import { closePrompts, confirm } from './confirm.mjs';
import { REPOSITORY_PATTERN } from './github.mjs';

const execFileAsync = promisify( execFile );

export const MONOREPO_ROOT = resolve( import.meta.dirname, '../../..' );

const REMOTE = 'monorepo-merge-import';

// Signals that stop the merge but still let it remove the temporary clone and remote.
const SIGNALS = [ 'SIGINT', 'SIGTERM', 'SIGHUP' ];

// The command `run` is waiting on, so a signal sent to this process alone can be passed on to it.
let activeChild;

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
	// git merge prints a line per imported file, which can exceed the default 1 MiB buffer.
	const pending = execFileAsync( command, args, {
		maxBuffer: Infinity,
		...options,
	} );
	activeChild = pending.child;
	try {
		const { stdout } = await pending;

		return stdout.trim();
	} catch ( error ) {
		// Merge conflicts are listed on stdout, so include it alongside stderr.
		const detail =
			[ error.stdout?.trim(), error.stderr?.trim() ]
				.filter( Boolean )
				.join( '\n' ) || error.message;
		throw new Error( `${ failureMessage }: ${ detail }` );
	} finally {
		activeChild = undefined;
	}
}

/**
 * Validates the source repository and the destination path.
 *
 * @param {string} source      The GitHub repository we are merging, in "organization/repository" format.
 * @param {string} destination The monorepo path to merge the repository into.
 * @return {Promise<string>} The destination relative to the monorepo root, without `./` or a trailing slash.
 */
export async function validateArgs( source, destination ) {
	// We only support pulling from GitHub so the format needs to match that.
	if ( ! REPOSITORY_PATTERN.test( source ) ) {
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
			'The "destination" argument points to a file or directory that already exists'
		);
	}

	return relativeDestination;
}

/**
 * Builds the git-filter-repo callback that updates imported references.
 *
 * A reference alone in parentheses, such as `(#123)`, becomes a pull request link first.
 * Every other standalone reference, such as `#123` or the ones in `(#12, #34)`, is then qualified with the
 * source repository. A `#` that follows a word character or `/`, as in `other/repo#12` or a URL fragment,
 * is not a reference and is left alone.
 *
 * @param {string} source The GitHub repository we are merging.
 * @return {string} The Python body of the message callback.
 */
export function createMessageCallback( source ) {
	const pullRequestPattern = String.raw`rb"\(#(?P<pull_request_number>\d+)\)"`;
	const pullRequestReplacement = String.raw`rb"(https://github.com/${ source }/pull/\g<pull_request_number>)"`;
	const issuePattern = String.raw`rb"(?<![\w/])#(?P<issue_number>\d+)(?!\w)"`;
	const issueReplacement = String.raw`rb"${ source }#\g<issue_number>"`;
	const pullRequestCallback = `re.sub(${ pullRequestPattern }, ${ pullRequestReplacement }, message)`;

	return `return re.sub(${ issuePattern }, ${ issueReplacement }, ${ pullRequestCallback })`;
}

/**
 * Merges a GitHub repository into the current branch with its history, moved under `destination`.
 *
 * @param {string} source               The GitHub repository to merge, in "organization/repository" format.
 * @param {string} requestedDestination The monorepo path to merge the repository into.
 * @param {Object} options
 * @param {string} options.branch       The source branch to merge.
 */
export async function merge( source, requestedDestination, { branch } ) {
	for ( const command of [ 'git', 'git-filter-repo' ] ) {
		await run(
			command,
			[ '--version' ],
			`"${ command }" must be installed`
		);
	}
	const destination = await validateArgs( source, requestedDestination );

	// Ask git from the tool's own directory, so a copy outside tools/monorepo-merge cannot target an enclosing repository.
	const inMonorepo = { cwd: MONOREPO_ROOT };
	const notARepository = `The checkout to merge into must be two directories above this tool, at ${ MONOREPO_ROOT }, but that is not the root of the git checkout containing it`;
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

	const clonePrefix = join( tmpdir(), 'monorepo-merge-' );
	// The remote may belong to another run in progress or to one that was killed, which this run cannot tell
	// apart, so never remove it automatically.
	const existing = await run(
		'git',
		[ 'remote', 'get-url', REMOTE ],
		'',
		inMonorepo
	).catch( () => null );
	if ( existing ) {
		throw new Error(
			`A "${ REMOTE }" remote already exists and points at ${ existing }. Make sure no other merge is running, remove it with "git remote remove ${ REMOTE }", then run the command again.`
		);
	}

	if (
		! ( await confirm(
			`This merges ${ source } and its history into ${ destination } on ${
				currentBranch
					? `branch "${ currentBranch }"`
					: 'a detached HEAD'
			} of ${ MONOREPO_ROOT }. Continue?`
		) )
	) {
		throw new Error( 'Cancelled.' );
	}

	const cloneDir = await mkdtemp( clonePrefix );

	// Catching these signals lets the cleanup below still run. Ctrl+C from a terminal already reaches git,
	// but a signal sent to this process alone has to be passed on to the running command.
	let interrupted = false;
	let mergeStarted = false;
	let cleaningUp = false;
	const onInterrupt = ( signal ) => {
		interrupted = true;
		closePrompts();
		if ( ! cleaningUp ) {
			activeChild?.kill( signal );
		}
	};
	for ( const signal of SIGNALS ) {
		process.on( signal, onInterrupt );
	}
	const step = ( ...args ) => {
		if ( interrupted ) {
			throw new Error( 'Interrupted' );
		}
		return run( ...args );
	};

	let failure;
	try {
		const gitPath = 'https://github.com/' + source;
		console.log( `Cloning ${ gitPath }` );
		await step(
			'git',
			[ 'clone', gitPath, cloneDir ],
			'Failed to clone the repository'
		);

		console.log( 'Rewriting the repository history' );
		await step(
			'git-filter-repo',
			[
				'--to-subdirectory-filter=' + destination,
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
			throw new Error( 'Cancelled. Nothing was merged.' );
		}

		console.log( 'Merging repositories' );
		// A fixed remote name, because names such as "org/.github" are valid repositories but invalid remotes.
		await step(
			'git',
			[ 'remote', 'add', REMOTE, cloneDir ],
			'Failed to add the cloned repository as a remote',
			inMonorepo
		);
		// Without --no-tags the source repository's tags would stay in the monorepo after the remote is removed.
		await step(
			'git',
			[ 'fetch', '--no-tags', REMOTE ],
			'Failed to fetch the cloned repository',
			inMonorepo
		);
		mergeStarted = true;
		await step(
			'git',
			[
				'merge',
				'--allow-unrelated-histories',
				'-m',
				`Merge ${ source } into ${ destination }`,
				'--',
				`${ REMOTE }/${ branch }`,
			],
			'Failed to merge the repositories',
			inMonorepo
		);
	} catch ( error ) {
		failure = error;
	}
	// Read before cleanup: a Ctrl+C during cleanup did not stop any step.
	const stopped = Boolean( failure ) && interrupted;

	// The merge only needs the fetched objects, so the clone and remote can go even after a conflict.
	// Signals are no longer passed on once cleanup starts. A terminal Ctrl+C can still hit these quick git commands,
	// and if that strands the remote, the next run stops and explains how to remove it.
	cleaningUp = true;
	const problems = [];
	try {
		// Checked by URL rather than a flag, because an interrupted `git remote add` can still have written the remote.
		try {
			const remotes = await run(
				'git',
				[ 'remote' ],
				'Failed to list the remotes',
				inMonorepo
			);
			if (
				remotes.split( '\n' ).includes( REMOTE ) &&
				( await run(
					'git',
					[ 'remote', 'get-url', REMOTE ],
					`Failed to read the temporary remote "${ REMOTE }"`,
					inMonorepo
				) ) === cloneDir
			) {
				await run(
					'git',
					[ 'remote', 'remove', REMOTE ],
					`Failed to remove the temporary remote "${ REMOTE }"`,
					inMonorepo
				);
			}
		} catch ( error ) {
			problems.push( error.message );
		}
		await rm( cloneDir, { recursive: true, force: true } ).catch(
			( error ) =>
				problems.push(
					`Failed to remove the temporary clone ${ cloneDir }: ${ error.message }`
				)
		);
	} finally {
		for ( const signal of SIGNALS ) {
			process.off( signal, onInterrupt );
		}
	}

	if ( ! failure ) {
		console.log( `Successfully merged ${ source } into ${ destination }` );
		if ( problems.length > 0 ) {
			throw new Error(
				`The merge succeeded, but cleanup failed:\n${ problems.join(
					'\n'
				) }`
			);
		}
		return;
	}

	let reason = failure.message;
	if ( stopped ) {
		reason = mergeStarted
			? 'Interrupted during git merge. The checkout may be mid-merge; run "git merge --abort" to undo it.'
			: 'Interrupted';
	}
	throw Object.assign( new Error( [ reason, ...problems ].join( '\n' ) ), {
		exitCode: stopped ? 130 : 1,
	} );
}
