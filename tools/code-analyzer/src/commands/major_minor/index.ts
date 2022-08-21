/**
 * External dependencies
 */
import { CliUx, Command } from '@oclif/core';
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
	static description = 'Determine major/minor version of WooCommerce plugin';

	/**
	 * CLI arguments
	 */
	static args = [
		{
			name: 'branch',
			description: 'GitHub branch to use to determine version',
			required: true,
		},
	];

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args } = await this.parse( MajorMinor );
		const { branch } = args;
		const repo = process.cwd();

		CliUx.ux.action.start( `Making a temporary clone of '${ branch }'` );

		const tmpRepoPath = await cloneRepo( repo );
		const version = await this.getPluginData( tmpRepoPath, branch );

		// Clean up the temporary repo.
		rmSync( tmpRepoPath, { force: true, recursive: true } );

		this.log( version );
		CliUx.ux.action.stop();
	}

	/**
	 * Get plugin data
	 *
	 * @param {string} tmpRepoPath  - Path to repo.
	 * @param {string} hashOrBranch - Hash or branch to checkout.
	 * @return {Promise<string>} - Promise containing version as string.
	 */
	private async getPluginData(
		tmpRepoPath: string,
		hashOrBranch: string
	): Promise< string > {
		const git = simpleGit( { baseDir: tmpRepoPath } );
		await git.checkout( [ hashOrBranch ] );

		const pluginData = {
			name: 'WooCommerce',
			mainFile: join(
				tmpRepoPath,
				'plugins',
				'woocommerce',
				'woocommerce.php'
			),
		};

		CliUx.ux.action.start( `Getting ${ pluginData.name } version` );

		const content = readFileSync( pluginData.mainFile ).toString();
		const rawVer = content.match( /^\s+\*\s+Version:\s+(.*)/m );

		if ( ! rawVer ) {
			this.error( 'Failed to find plugin version!' );
		}
		const version = rawVer[ 1 ].replace( /\-.*/, '' );

		CliUx.ux.action.stop();

		const [ major, minor ] = version.split( '.' );

		return `${ major }.${ minor }.0`;
	}
}
