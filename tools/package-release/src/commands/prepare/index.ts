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

			let nextVersion = null;

			try {
				validateChangelogEntries( name );
			} catch ( e ) {
				this.error( 'Changelogger validation fails.' );
			}

			try {
				nextVersion = getNextVersion( name );
			} catch ( e ) {
				this.error( 'Cannot get next version.' );
			}

			console.log( nextVersion );

			try {
				writeChangelog( name );
			} catch ( e ) {
				this.error( 'Changelogger validation fails.' );
			}

			CliUx.ux.action.stop();
		} );
	}
}
