/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';

/**
 * Internal dependencies
 */
import { getFilepaths } from '../../validate';

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
			name: 'packages',
			description: 'Package to release',
			required: false,
		},
	];

	/**
	 * CLI flags.
	 */
	static flags = {
		all: Flags.boolean( { char: 'a', default: false } ),
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( PackageRelease );

		if ( ! args.packages && ! flags.all ) {
			this.error( 'no package supplied.' );
		}

		CliUx.ux.action.start( `Prepare ` + args.packages );

		const filepaths = getFilepaths( args, flags, ( e: string ): void =>
			this.error( e )
		);

		// const filepath =
		// 	'packages/js' + args.packages.replace( '@woocommerce', '' );

		// const nextVersion = execSync(
		// 	'./vendor/bin/changelogger version next',
		// 	{
		// 		cwd: filepath,
		// 		encoding: 'utf-8',
		// 	}
		// );

		// const validation = execSync( './vendor/bin/changelogger validate', {
		// 	cwd: filepath,
		// 	encoding: 'utf-8',
		// } );

		// const write = execSync( './vendor/bin/changelogger write', {
		// 	cwd: filepath,
		// 	encoding: 'utf-8',
		// } );

		console.log( filepaths );

		CliUx.ux.action.stop();
	}
}
