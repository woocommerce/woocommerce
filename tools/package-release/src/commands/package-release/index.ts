/**
 * External dependencies
 */
import { CliUx, Command } from '@oclif/core';

/**
 * PackageRelease class
 */
export default class PackageRelease extends Command {
	/**
	 * CLI description
	 */
	static description = 'Release Monorepo JS packages';

	/**
	 * CLI arguments
	 */
	static args = [];

	/**
	 * CLI flags.
	 */
	static flags = {};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		CliUx.ux.action.start( `Starting process` );
		console.log( 'im runinning' );
		CliUx.ux.action.stop();
	}
}
