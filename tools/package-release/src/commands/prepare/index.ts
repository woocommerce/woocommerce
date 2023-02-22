/**
 * External dependencies
 */
import { CliUx, Command, Flags, ux } from '@oclif/core';
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
			this.preparePackages( getAllPackges() );
			return;
		}

		const packages = args.packages.split( ',' );

		packages.forEach( ( name: string ) =>
			validatePackage( name, ( e: string ): void => this.error( e ) )
		);

		this.preparePackages( packages );
	}

	/**
	 * Prepare packages for release by creating the changelog and bumping version.
	 *
	 * @param {Array<string>} packages Packages to prepare.
	 */
	private preparePackages( packages: Array< string > ) {
		packages.forEach( ( name ) => {
			CliUx.ux.action.start( `Preparing ${ name }` );

			try {
				if ( hasValidChangelogs( name ) ) {
					validateChangelogEntries( name );
					const nextVersion = getNextVersion( name );
					if ( ! nextVersion ) {
						console.log( ux );
						// const input = await ux.prompt( 'What is your name?' );
					}
					writeChangelog( name );
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
