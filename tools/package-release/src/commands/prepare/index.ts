/**
 * External dependencies
 */
import { Args, Command, Flags, ux } from '@oclif/core';
import { existsSync, readFileSync, writeFileSync } from 'fs';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import {
	getAllPackages,
	validatePackage,
	getFilepathFromPackageName,
	getPackageJson,
	getComposerJson,
	getPackageType,
} from '../../validate';
import {
	getNextVersion,
	validateChangelogEntries,
	writeChangelog,
	hasValidChangelogs,
} from '../../changelogger';
import { WOOCOMMERCE_PLUGIN_ROOT } from '../../const';

/**
 * PackagePrepare class
 */
export default class PackagePrepare extends Command {
	/**
	 * CLI description
	 */
	static description = 'Prepare Monorepo JS or PHP packages for Release';

	/**
	 * CLI arguments
	 */
	static args = {
		packages: Args.string( {
			description:
				'Package to release, or packages to release separated by commas.',
			required: false,
		} ),
	};

	/**
	 * CLI flags.
	 */
	static flags = {
		all: Flags.boolean( {
			char: 'a',
			default: false,
			description: 'Perform prepare function on all packages.',
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
		const { args, flags } = await this.parse( PackagePrepare );
		const packages = args.packages ? args.packages.split( ',' ) : [];

		if ( ! args.packages && ! flags.all ) {
			this.error( 'No packages supplied.' );
		}

		if ( flags.all ) {
			await this.preparePackages( getAllPackages() );
			return;
		}

		if ( flags[ 'initial-release' ] && packages.length > 1 ) {
			this.error(
				'Please release only a single package when making an initial release'
			);
		}

		packages.forEach( ( name: string ) =>
			validatePackage( name, ( e: string ): void => this.error( e ) )
		);

		await this.preparePackages( packages, flags[ 'initial-release' ] );
	}

	/**
	 * Prepare packages for release by creating the changelog and bumping version.
	 *
	 * @param {Array<string>} packages Packages to prepare.
	 */
	private async preparePackages(
		packages: Array< string >,
		initialRelease?: boolean
	) {
		for ( const name of packages ) {
			ux.action.start( `Preparing ${ name }` );

			try {
				if ( hasValidChangelogs( name ) ) {
					validateChangelogEntries( name );
					const nextVersion = getNextVersion( name );
					if ( ! nextVersion && ! initialRelease ) {
						throw new Error(
							`Error reading version number for ${ name }. Check that a Changelog file exists and has a version number. If making an initial release, pass the --initialRelease flag.`
						);
					}

					// @todo: When the composer.json versioning is "wordpress" as is for plugins, this value needs to be 1.0
					const version = nextVersion ?? '1.0.0';
					this.bumpPackageVersion( name, version );
					writeChangelog( name, nextVersion ? undefined : version );

					ux.action.stop();
				} else {
					ux.action.stop(
						`Skipping ${ name }. No changes available for a release.`
					);
				}
			} catch ( e ) {
				if ( e instanceof Error ) {
					this.error( e.message );
				}
			}
		}
	}

	/**
	 * Update the version number in package.json.
	 *
	 * @param {string} name    Package name.
	 * @param {string} version Next version.
	 */
	private bumpPackageVersion( name: string, version: string ) {
		try {
			const filepath = getFilepathFromPackageName( name );
			const packageType = getPackageType( name );
			let jsonFilepath;
			let json;

			if ( packageType === 'js' ) {
				jsonFilepath = `${ filepath }/package.json`;
				json = getPackageJson( name );
			} else if ( packageType === 'php' ) {
				jsonFilepath = `${ filepath }/composer.json`;
				json = getComposerJson( name );
			} else {
				this.error(
					`Can't bump version for ${ name }. The package does not exist.`
				);
			}

			const lockFilepath = `${ filepath }/composer.lock`;
			const originalFiles =
				packageType === 'php' && existsSync( lockFilepath )
					? {
							manifest: readFileSync( jsonFilepath ),
							lockfile: readFileSync( lockFilepath ),
						}
					: null;
			json.version = version;
			writeFileSync(
				jsonFilepath,
				JSON.stringify( json, null, '\t' ) + '\n'
			);

			if ( originalFiles ) {
				try {
					execSync(
						'composer update --lock --no-install --no-scripts --no-plugins --no-interaction',
						{ cwd: filepath, stdio: 'inherit' }
					);
				} catch ( error ) {
					writeFileSync( jsonFilepath, originalFiles.manifest );
					writeFileSync( lockFilepath, originalFiles.lockfile );
					throw error;
				}
			}

			// if preparing a php package and the package is used in the woocommerce plugin
			// also update the version in the plugin's composer.json and composer.lock
			// this is done to ensure jetpack autoloader fetches the correct version of the package
			if ( packageType === 'php' ) {
				try {
					// will throw an error if the package is not used in the woocommerce plugin
					execSync(
						`composer show ${ json.name } --no-scripts --direct --quiet`,
						{
							encoding: 'utf-8',
							cwd: WOOCOMMERCE_PLUGIN_ROOT,
							stdio: 'ignore',
						}
					);
					execSync(
						`composer update ${ json.name } --no-scripts --no-plugins --quiet`,
						{
							encoding: 'utf-8',
							cwd: WOOCOMMERCE_PLUGIN_ROOT,
						}
					);
				} catch ( error ) {
					// Ignore the error - package is not used in the woocommerce plugin.
				}
			}
		} catch ( e ) {
			this.error( `Can't bump version for ${ name }. ${ String( e ) }` );
		}
	}
}
