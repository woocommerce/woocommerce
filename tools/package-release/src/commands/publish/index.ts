/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import {
	getAllPackges,
	validatePackage,
	getFilepathFromPackageName,
} from '../../validate';
import { MONOREPO_ROOT } from '../../const';

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
			description:
				'Package to release, or packages to release separated by commas.',
			required: false,
		},
	];

	/**
	 * CLI flags.
	 */
	static flags = {
		all: Flags.boolean( {
			char: 'a',
			default: false,
			description: 'Perform prepare function on all packages.',
		} ),
		dry: Flags.boolean( {
			char: 'd',
			default: false,
			description: 'Perform a dry run of pnpm publish',
		} ),
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( PackageRelease );

		if ( ! args.packages && ! flags.all ) {
			this.error( 'No packages supplied.' );
		}

		execSync( 'pnpm install', {
			cwd: MONOREPO_ROOT,
			encoding: 'utf-8',
		} );

		if ( flags.all ) {
			this.publishPackages( getAllPackges(), flags.dry );
			return;
		}

		const packages = args.packages.split( ',' );

		packages.forEach( ( name: string ) =>
			validatePackage( name, ( e: string ): void => this.error( e ) )
		);

		this.publishPackages( packages, flags.dry );
	}

	/**
	 * Publish packages for release.
	 *
	 * @param {Array<string>} packages Packages to prepare.
	 * @param {boolean}       dryRun   pnpm dry run.
	 */
	private publishPackages( packages: Array< string >, dryRun: boolean ) {
		packages.forEach( ( name ) => {
			CliUx.ux.action.start( `Publishing ${ name }` );

			try {
				const cwd = getFilepathFromPackageName( name );
				return execSync( 'pnpm publish --dry-run', {
					cwd,
					encoding: 'utf-8',
				} ).trim();
			} catch ( e ) {
				if ( e instanceof Error ) {
					this.error( e.message );
				}
			}

			CliUx.ux.action.stop();
		} );
	}
}
