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
	isValidUpdate,
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
		'dry-run': Flags.boolean( {
			char: 'd',
			default: false,
			description: 'Perform a dry run of pnpm publish',
		} ),
		branch: Flags.string( {
			char: 'b',
			description: 'Branch name to publish from',
			default: 'trunk',
		} ),
		'skip-install': Flags.boolean( {
			description: 'Skip pnpm install',
			default: false,
		} ),
		'initial-release': Flags.boolean( {
			default: false,
			description: "Create a package's first release to NPM",
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

		if ( ! flags[ 'skip-install' ] ) {
			CliUx.ux.action.start( 'Installing all dependencies' );

			execSync( 'pnpm install', {
				cwd: MONOREPO_ROOT,
				encoding: 'utf-8',
				stdio: 'inherit',
			} );

			// Sometimes the pnpm lock file is out of sync, this shouldn't prevent a release.
			execSync( 'git checkout pnpm-lock.yaml', {
				cwd: MONOREPO_ROOT,
				encoding: 'utf-8',
				stdio: 'inherit',
			} );

			CliUx.ux.action.stop();
		}

		if ( flags.all ) {
			this.publishPackages( getAllPackges(), flags );
			return;
		}

		const packages = args.packages.split( ',' );

		packages.forEach( ( name: string ) =>
			validatePackage( name, ( e: string ): void => this.error( e ) )
		);

		this.publishPackages( packages, flags );
	}

	/**
	 * Publish packages for release.
	 *
	 * @param {Array<string>} packages Packages to prepare.
	 * @param {boolean}       dryRun   pnpm dry run.
	 */
	private publishPackages(
		packages: Array< string >,
		{
			'dry-run': dryRun,
			branch,
			'initial-release': initialRelease,
		}: { 'dry-run': boolean; branch: string; 'initial-release': boolean }
	) {
		packages.forEach( ( name ) => {
			try {
				const verb = dryRun ? 'Performing dry run of' : 'Publishing';
				CliUx.ux.action.start( `${ verb } ${ name }` );
				if ( isValidUpdate( name, initialRelease ) ) {
					const cwd = getFilepathFromPackageName( name );
					execSync(
						`pnpm publish ${
							dryRun ? '--dry-run' : ''
						} --publish-branch=${ branch }`,
						{
							cwd,
							encoding: 'utf-8',
							stdio: 'inherit',
						}
					);
					CliUx.ux.action.stop( `${ name } successfully published.` );
				} else {
					CliUx.ux.action.stop(
						`${ name } does not have anything to update.`
					);
				}
			} catch ( e ) {
				if ( e instanceof Error ) {
					this.error( e.message );
				}
			}
		} );
	}
}
