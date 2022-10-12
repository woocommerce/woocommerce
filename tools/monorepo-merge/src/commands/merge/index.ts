/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { join } from 'path';
import { tmpdir } from 'os';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from '../../const';
import { access, exec } from '../../node-async';

export default class Merge extends Command {
	static description =
		'Merges another repository into this one with history.';

	static args = [
		{
			name: 'source',
			description: 'The GitHub repository we are merging from.',
			required: true,
		},
		{
			name: 'destination',
			description:
				'The monorepo path for the repository to be merged at.',
			required: true,
		},
	];

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

		let confirmation = await CliUx.ux.confirm(
			'WARNING: This command will DESTROY the history of your current branch. Are you sure you want to proceed? (y/n)'
		);
		if ( ! confirmation ) {
			this.exit( 0 );
		}

		const repositoryPath = await this.cloneRepository( args.source );
		await this.alterRepositoryHistory(
			args.source,
			repositoryPath,
			args.destination
		);

		confirmation = await CliUx.ux.confirm(
			'Are you ready to merge ' +
				args.source +
				' from ' +
				repositoryPath +
				'? (y/n)'
		);
		if ( ! confirmation ) {
			// Remove the repository we've cloned.
			try {
				await exec( 'rm -rf ' + repositoryPath );
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

		// We can't merge into a directory that already exists.
		let exists = false;
		try {
			await access( join( MONOREPO_ROOT, destination ) );
			exists = true;
		} catch ( err ) {
			exists = false;
		}

		if ( exists ) {
			this.error(
				'The "destination" argument points to a directory that already exists'
			);
		}
	}

	/**
	 * Clones a repository from GitHub into a temporary directory and returns the path.
	 *
	 * @param {string} source The GitHub repository we want to clone.
	 */
	private async cloneRepository( source: string ): Promise< string > {
		// Show progress for the cloning.
		const gitPath = 'https://github.com/' + source;
		CliUx.ux.action.start( 'Cloning from ' + gitPath );

		// We need a fresh directory to clone the source into.
		const cloneDir = join( tmpdir(), 'monorepo-merge', source );
		try {
			await access( cloneDir );
			await exec( 'rm -rf ' + cloneDir );
		} catch {}

		await exec( 'git clone ' + gitPath + ' ' + cloneDir );

		CliUx.ux.action.stop();
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
		const filterCommand = [
			'git-filter-repo',
			"--to-subdirectory-filter '" + destination + "'",
			'--message-callback=\'return re.sub(b"\\(#(\\d+)\\)", b"(https://github.com/' +
				source +
				'/pull/\\\\1)", re.sub(b"(?<!\\()(#\\d+)(?!\\))", b"' +
				source +
				'\\\\1", message))\'',
		].join( ' ' );

		CliUx.ux.action.start( 'Altering repository history' );

		try {
			await exec( filterCommand, { cwd: cloneDir } );
		} catch {
			this.error( 'Failed to alter the repository history' );
		} finally {
			CliUx.ux.action.stop();
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
		CliUx.ux.action.start( 'Merging repositories' );

		// We need the cloned repository as a remote in order to merge it.
		try {
			await exec( 'git remote add ' + source + ' "' + cloneDir + '"' );
		} catch {
			CliUx.ux.action.stop();

			this.error( 'Failed to add clone repository as remote' );
		}

		try {
			await exec( 'git fetch ' + source );
		} catch {
			CliUx.ux.action.stop();

			this.error( 'Failed to fetch clone repository' );
		}

		try {
			await exec(
				'git merge --allow-unrelated-histories ' +
					source +
					'/' +
					branchToMerge
			);
		} catch {
			CliUx.ux.action.stop();

			this.error( 'Failed to merge the repositories' );
		}

		// We don't need the remote anymore.
		try {
			await exec( 'git remote remove ' + source );
			await exec( 'rm -rf ' + cloneDir );
		} catch {
			CliUx.ux.action.stop();

			this.error( 'Failed to remove clone repository remote' );
		}

		CliUx.ux.action.stop();
	}
}
