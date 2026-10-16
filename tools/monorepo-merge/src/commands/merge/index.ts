/**
 * External dependencies
 */
import { Args, Command, Flags, ux } from '@oclif/core';
import { execFile } from 'child_process';
import { rm } from 'fs/promises';
import { isAbsolute, join, relative, resolve, sep } from 'path';
import { tmpdir } from 'os';
import { promisify } from 'util';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from '../../const';
import { access, exec } from '../../node-async';

const execFilePromisified = promisify( execFile );

export default class Merge extends Command {
	static description =
		'Merges another repository into this one with history.';

	static args = {
		source: Args.string( {
			description: 'The GitHub repository we are merging from.',
			required: true,
		} ),
		destination: Args.string( {
			description:
				'The monorepo path for the repository to be merged at.',
			required: true,
		} ),
	};

	static flags = {
		branch: Flags.string( {
			description:
				'The destination branch we want to merge into the monorepo.',
			default: 'main',
		} ),
	};

	/**
	 * This method is called to execute the command.
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( Merge );

		await this.checkDependencies();
		await this.validateArgs( args.source, args.destination );
		const { default: confirm } = await import( '@inquirer/confirm' );

		let confirmation = await confirm( {
			message:
				'WARNING: This command will DESTROY the history of your current branch. Are you sure you want to proceed?',
			default: false,
		} );
		if ( ! confirmation ) {
			this.exit( 0 );
		}

		const repositoryPath = await this.cloneRepository( args.source );
		await this.alterRepositoryHistory(
			args.source,
			repositoryPath,
			args.destination
		);

		confirmation = await confirm( {
			message:
				'Are you ready to merge ' +
				args.source +
				' from ' +
				repositoryPath +
				'?',
			default: false,
		} );
		if ( ! confirmation ) {
			// Remove the repository we've cloned.
			try {
				await rm( repositoryPath, { recursive: true, force: true } );
			} catch {}

			this.exit( 0 );
		}

		await this.mergeRepository( args.source, repositoryPath, flags.branch );

		this.log(
			'Successfully merged ' + args.source + ' into ' + args.destination
		);
	}

	/**
	 * Checks to make sure that all of the necessary dependencies to run the script are installed.
	 */
	private async checkDependencies(): Promise< void > {
		try {
			await exec( 'git --version' );
		} catch {
			this.error( '"git" must be installed' );
		}

		try {
			await exec( 'git-filter-repo --version' );
		} catch {
			this.error( '"git-filter-repo" must be installed' );
		}
	}

	/**
	 * Validates all of the arguments to make sure they're compatible with the command.
	 *
	 * @param {string} source      The GitHub repository we are merging.
	 * @param {string} destination The local path we're merging into.
	 */
	private async validateArgs(
		source: string,
		destination: string
	): Promise< void > {
		// We only support pulling from GitHub so the format needs to match that.
		if ( ! source.match( /^[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_]+$/ ) ) {
			this.error(
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
			this.error(
				'The "destination" argument must point to a path inside the monorepo'
			);
		}

		// We can't merge into a directory that already exists.
		let exists = false;
		try {
			await access( destinationPath );
			exists = true;
		} catch {
			exists = false;
		}

		if ( exists ) {
			this.error(
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
	 */
	private static createMessageCallback( source: string ): string {
		const pullRequestPattern = String.raw`rb"\(#(?P<pull_request_number>\d+)\)"`;
		const pullRequestReplacement = String.raw`rb"(https://github.com/${ source }/pull/\g<pull_request_number>)"`;
		const issuePattern = String.raw`rb"(?<!\()#(?P<issue_number>\d+)(?!\))"`;
		const issueReplacement = String.raw`rb"${ source }#\g<issue_number>"`;
		const issueCallback = `re.sub(${ issuePattern }, ${ issueReplacement }, message)`;

		return `return re.sub(${ pullRequestPattern }, ${ pullRequestReplacement }, ${ issueCallback })`;
	}

	/**
	 * Clones a repository from GitHub into a temporary directory and returns the path.
	 *
	 * @param {string} source The GitHub repository we want to clone.
	 */
	private async cloneRepository( source: string ): Promise< string > {
		// Show progress for the cloning.
		const gitPath = 'https://github.com/' + source;
		ux.action.start( 'Cloning from ' + gitPath );

		// We need a fresh directory to clone the source into.
		const cloneDir = join( tmpdir(), 'monorepo-merge', source );
		try {
			await rm( cloneDir, { recursive: true, force: true } );
		} catch {}

		await execFilePromisified( 'git', [ 'clone', gitPath, cloneDir ] );

		ux.action.stop();
		return cloneDir;
	}

	/**
	 * Alters the commit history so that it appears as if it always existed within the monorepo.
	 *
	 * @param {string} source      The GitHub repository we are merging.
	 * @param {string} cloneDir    The directory we've cloned the repository into.
	 * @param {string} destination The monorepo directory we want to move the files into.
	 */
	private async alterRepositoryHistory(
		source: string,
		cloneDir: string,
		destination: string
	): Promise< void > {
		const messageCallback = Merge.createMessageCallback( source );

		ux.action.start( 'Altering repository history' );

		try {
			await execFilePromisified(
				'git-filter-repo',
				[
					'--to-subdirectory-filter',
					destination,
					'--message-callback=' + messageCallback,
				],
				{ cwd: cloneDir }
			);
		} catch {
			this.error( 'Failed to alter the repository history' );
		} finally {
			ux.action.stop();
		}
	}

	/**
	 * Merges the cloned repository into the current one.
	 *
	 * @param {string} source        The GitHub repository we are merging.
	 * @param {string} cloneDir      The directory we've cloned the repository into.
	 * @param {string} branchToMerge The branch we want to merge from.
	 */
	private async mergeRepository(
		source: string,
		cloneDir: string,
		branchToMerge: string
	): Promise< void > {
		ux.action.start( 'Merging repositories' );

		// We need the cloned repository as a remote in order to merge it.
		try {
			await execFilePromisified( 'git', [
				'remote',
				'add',
				source,
				cloneDir,
			] );
		} catch {
			ux.action.stop();

			this.error( 'Failed to add clone repository as remote' );
		}

		try {
			await execFilePromisified( 'git', [ 'fetch', source ] );
		} catch {
			ux.action.stop();

			this.error( 'Failed to fetch clone repository' );
		}

		try {
			await execFilePromisified( 'git', [
				'merge',
				'--allow-unrelated-histories',
				'--',
				source + '/' + branchToMerge,
			] );
		} catch {
			ux.action.stop();

			this.error( 'Failed to merge the repositories' );
		}

		// We don't need the remote anymore.
		try {
			await execFilePromisified( 'git', [ 'remote', 'remove', source ] );
			await rm( cloneDir, { recursive: true, force: true } );
		} catch {
			ux.action.stop();

			this.error( 'Failed to remove clone repository remote' );
		}

		ux.action.stop();
	}
}
