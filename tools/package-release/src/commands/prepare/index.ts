/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { readFileSync, writeFileSync } from 'fs';

/**
 * Internal dependencies
 */
import {
	getAllPackges,
	validatePackage,
	getFilepathFromPackageName,
} from '../../validate';
import {
	getNextVersion,
	validateChangelogEntries,
	writeChangelog,
	hasValidChangelogs,
} from '../../changelogger';

/**
 * PackagePrepare class
 */
export default class PackagePrepare extends Command {
	/**
	 * CLI description
	 */
	static description = 'Prepare Monorepo JS packages for Release';

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
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( PackagePrepare );

		if ( ! args.packages && ! flags.all ) {
			this.error( 'No packages supplied.' );
		}

		if ( flags.all ) {
			await this.preparePackages( getAllPackges() );
			return;
		}

		const packages = args.packages.split( ',' );

		packages.forEach( ( name: string ) =>
			validatePackage( name, ( e: string ): void => this.error( e ) )
		);

		await this.preparePackages( packages );
	}

	/**
	 * Prepare packages for release by creating the changelog and bumping version.
	 *
	 * @param {Array<string>} packages Packages to prepare.
	 */
	private async preparePackages( packages: Array< string > ) {
		packages.forEach( async ( name ) => {
			CliUx.ux.action.start( `Preparing ${ name }` );

			try {
				if ( hasValidChangelogs( name ) ) {
					validateChangelogEntries( name );
					let nextVersion = getNextVersion( name );
					if ( ! nextVersion ) {
						const isInitialDeployment = await CliUx.ux.confirm(
							`The changelog for package ${ name } does not contain a version number. \n\nAre you attempting to make an intial deployment of ${ name }? (yes/no)`
						);

						if ( isInitialDeployment ) {
							nextVersion = '1.0.0';
						} else {
							throw new Error(
								`Error reading version number for ${ name }. Check that a Changelog file exists and has a version number. `
							);
						}
					}
					writeChangelog( name, nextVersion );
					if ( nextVersion ) {
						this.bumpPackageVersion( name, nextVersion );
					}
					CliUx.ux.action.stop();
				} else {
					CliUx.ux.action.stop(
						`Skipping ${ name }. No changes available for a release.`
					);
				}
			} catch ( e ) {
				if ( e instanceof Error ) {
					this.error( e.message );
				}
			}
		} );
	}

	/**
	 * Update the version number in package.json.
	 *
	 * @param {string} name    Package name.
	 * @param {string} version Next version.
	 */
	private bumpPackageVersion( name: string, version: string ) {
		const filepath = getFilepathFromPackageName( name );
		const packageJsonFilepath = `${ filepath }/package.json`;
		try {
			const packageJson = JSON.parse(
				readFileSync( packageJsonFilepath, 'utf8' )
			);
			packageJson.version = version;
			writeFileSync(
				packageJsonFilepath,
				JSON.stringify( packageJson, null, '\t' ) + '\n'
			);
		} catch ( e ) {
			this.error( `Can't bump version for ${ name }.` );
		}
	}
}
