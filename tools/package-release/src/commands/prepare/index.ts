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

		const nextVersion = execSync(
			'./vendor/bin/changelogger version next',
			{
				cwd: filepath,
				encoding: 'utf-8',
			}
		);

		const validation = execSync( './vendor/bin/changelogger validate', {
			cwd: filepath,
			encoding: 'utf-8',
		} );

		const write = execSync( './vendor/bin/changelogger write', {
			cwd: filepath,
			encoding: 'utf-8',
		} );

		console.log( write );

		CliUx.ux.action.stop();
	}
}
