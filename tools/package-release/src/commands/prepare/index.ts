/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';

/**
 * Internal dependencies
 */
import { getAllPackges, validatePackage } from '../../validate';
import {
	getNextVersion,
	validateChangelogEntries,
	writeChangelog,
	hasChangelogs,
} from '../../changelogger';

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

	private preparePackages( packages: Array< string > ) {
		packages.forEach( ( name ) => {
			CliUx.ux.action.start( `Preparing ${ name }` );

			try {
				if ( hasChangelogs( name ) ) {
					validateChangelogEntries( name );
					// const nextVersion = getNextVersion( name );
					writeChangelog( name );
					// console.log( nextVersion );
				} else {
					this.log( `Skipping ${ name }, no changelogs available.` );
				}
			} catch ( e ) {
				if ( e instanceof Error ) {
					this.error( e.message );
				}
			}

			CliUx.ux.action.stop();
		} );
	}
}
