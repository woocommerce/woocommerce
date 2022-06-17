/**
 * External dependencies
 */
import { CliUx, Command } from '@oclif/core';
import { execSync } from 'child_process';

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
		const { args } = await this.parse( PackageRelease );
		CliUx.ux.action.start( `Prepare ` + args.package );

		// todo: Need to validate filepath.
		const filepath =
			'packages/js' + args.package.replace( '@woocommerce', '' );
		// execSync( './vendor/bin/changelogger write --use-version=2.1.0', {
		const nextVersion = execSync(
			'./vendor/bin/changelogger version next',
			{
				cwd: filepath,
				encoding: 'utf-8',
			}
		);

		console.log( nextVersion );

		CliUx.ux.action.stop();
	}
}
