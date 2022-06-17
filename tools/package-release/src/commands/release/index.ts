/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';

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
	static args = [
		{
			name: 'package',
			description: 'Package to release',
			required: true,
		},
	];

	/**
	 * CLI flags.
	 */
	static flags = {};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( PackageRelease );
		CliUx.ux.action.start( `Starting process` );
		console.log( 'im going to release ' + args.package );
		CliUx.ux.action.stop();
	}
}
