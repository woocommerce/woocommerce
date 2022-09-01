/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { valid, lt as versionLessThan, prerelease } from 'semver';
import { readFileSync, writeFileSync } from 'fs';

/**
 * Internal dependencies
 */

/**
 * PackagePrepare class
 */
export default class VersionBump extends Command {
	/**
	 * CLI description
	 */
	static description = 'Bump plugin versions';

	/**
	 * CLI arguments
	 */
	static args = [
		{
			name: 'plugin',
			description: 'Plugin to bump versions.',
			required: false,
		},
	];

	/**
	 * CLI flags.
	 */
	static flags = {
		version: Flags.string( {
			char: 'v',
			required: true,
			description: 'The next version to bump to.',
		} ),
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( VersionBump );

		let nextVersion = flags.version;

		if ( ! valid( nextVersion ) ) {
			this.error(
				'Invalid version supplied, please pass in a semantically correct version.'
			);
		}

		const currentVersion = this.getCurrentVersion();

		if ( versionLessThan( nextVersion, currentVersion ) ) {
			this.error(
				'The version supplied is less than the current version, please supply a valid version.'
			);
		}

		const prereleaseParameters = prerelease( nextVersion );
		const isPrerelease = !! prereleaseParameters;
		const isDevVersionBump = prereleaseParameters[ 0 ] === 'dev';

		this.updatePluginFile( nextVersion );

		if ( isPrerelease && ! isDevVersionBump ) {
			// Prereleases such as beta or rc only bump the plugin file.
			return;
		}

		if ( isDevVersionBump ) {
			nextVersion = nextVersion.replace( '-dev', '' );
		}

		this.updateComposerJSON( nextVersion );
	}

	private updateComposerJSON( nextVersion: string ): void {
		try {
			const composerJson = JSON.parse(
				readFileSync( 'plugins/woocommerce/composer.json', 'utf8' )
			);
			composerJson.version = nextVersion;
			writeFileSync(
				'plugins/woocommerce/composer.json',
				JSON.stringify( composerJson, null, '\t' ) + '\n'
			);
		} catch ( e ) {
			this.error( 'Unable to update composer.json' );
		}
	}

	private updatePluginFile( nextVersion: string ): void {
		try {
			const pluginFileContents = readFileSync(
				'plugins/woocommerce/woocommerce.php',
				'utf8'
			);

			const updatedPluginFileContenst = pluginFileContents.replace(
				/Version: \d.\d.\d.*\n/m,
				`Version: ${ nextVersion }\n`
			);
			writeFileSync(
				'plugins/woocommerce/woocommerce.php',
				updatedPluginFileContenst
			);
		} catch ( e ) {
			this.error( 'Unable to update plugin file.' );
		}
	}

	private getCurrentVersion(): string {
		try {
			const composerJSON = JSON.parse(
				readFileSync( 'plugins/woocommerce/composer.json', 'utf8' )
			);
			return composerJSON.version;
		} catch ( e ) {
			this.error( 'Unable to read current version.' );
		}
	}
}
