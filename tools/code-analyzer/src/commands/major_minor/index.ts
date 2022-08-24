/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { join } from 'path';
import { readFileSync, rmSync } from 'fs';
import simpleGit from 'simple-git';

/**
 * Internal dependencies
 */
import { cloneRepo } from '../../git';

/**
 * MajorMinor command class
 */
export default class MajorMinor extends Command {
	/**
	 * CLI description
	 */
	static description = 'Determine major/minor version of a plugin';

	/**
	 * CLI arguments
	 */
	static args = [
		{
			name: 'branch',
			description: 'GitHub branch to use to determine version',
			required: true,
		},
		{
			name: 'pathToMainFile',
			description: "Path to plugin's main PHP file",
			required: true,
		},
	];

	/**
	 * CLI flags.
	 */
	static flags = {
		source: Flags.string( {
			char: 's',
			description: 'Git repo url or local path to a git repo.',
			default: process.cwd(),
		} ),
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( MajorMinor );
		const { source } = flags;
		const { branch, pathToMainFile } = args;

		CliUx.ux.action.start( `Making a temporary clone of '${ branch }'` );

		const tmpRepoPath = await cloneRepo( source );
		const version = await this.getPluginData(
			tmpRepoPath,
			pathToMainFile,
			branch
		);

		// Clean up the temporary repo.
		rmSync( tmpRepoPath, { force: true, recursive: true } );

		this.log( version );
		CliUx.ux.action.stop();
	}

	/**
	 * Get plugin data
	 *
	 * @param {string} tmpRepoPath    - Path to repo.
	 * @param {string} pathToMainFile - Path to plugin's main PHP file.
	 * @param {string} hashOrBranch   - Hash or branch to checkout.
	 * @return {Promise<string>} - Promise containing version as string.
	 */
	private async getPluginData(
		tmpRepoPath: string,
		pathToMainFile: string,
		hashOrBranch: string
	): Promise< string > {
		const git = simpleGit( { baseDir: tmpRepoPath } );
		await git.checkout( [ hashOrBranch ] );

		const mainFile = join( tmpRepoPath, pathToMainFile );

		CliUx.ux.action.start( `Getting version from ${ pathToMainFile }` );

		const content = readFileSync( mainFile ).toString();
		const rawVer = content.match( /^\s+\*\s+Version:\s+(.*)/m );

		if ( ! rawVer ) {
			this.error(
				'Failed to find plugin version! Make sure the file contains a version in the format `Version: ...`'
			);
		}
		const version = rawVer[ 1 ].replace( /\-.*/, '' );

		CliUx.ux.action.stop();

		const [ major, minor ] = version.split( '.' );

		return `${ major }.${ minor }.0`;
	}
}
