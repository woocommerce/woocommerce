/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { valid, lt as versionLessThan, prerelease } from 'semver';
import { readFileSync, writeFileSync } from 'fs';
import { ArgBase } from '@oclif/core/lib/interfaces/parser';
import { OutputFlags, OutputArgs } from '@oclif/core/lib/interfaces';

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
			default: 'woocommerce',
			options: [ 'woocommerce', 'woocommerce-beta-tester' ],
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

		this.validateArgs( args, flags );

		let nextVersion = flags.version;
		const { plugin } = args;

		const prereleaseParameters = prerelease( nextVersion );
		const isPrerelease = !! prereleaseParameters;
		const isDevVersionBump =
			prereleaseParameters && prereleaseParameters[ 0 ] === 'dev';

		this.updatePluginFile( plugin, nextVersion );

		if ( isPrerelease && ! isDevVersionBump ) {
			// Prereleases such as beta or rc only bump the plugin file.
			return;
		}

		if ( isDevVersionBump ) {
			// When updating to a dev version, only the plugin file gets the '-dev'.
			nextVersion = nextVersion.replace( '-dev', '' );
			// Bumping the dev version means updating the readme's changelog.
			this.updateReadmeChangelog( plugin, nextVersion );
		}

		this.updateComposerJSON( plugin, nextVersion );
		this.updateClassPluginFile( plugin, nextVersion );
		this.updateReadmeStableTag( plugin, nextVersion );
	}

	/**
	 * Validate inputs.
	 *
	 * @param args CLI arguments
	 * @param flags CLI flags
	 */
	private validateArgs(
		args: OutputArgs,
		flags: OutputFlags< typeof VersionBump[ 'flags' ] >
	): void {
		const nextVersion = flags.version;

		if ( ! valid( nextVersion ) ) {
			this.error(
				'Invalid version supplied, please pass in a semantically correct version.'
			);
		}

		const currentVersion = this.getCurrentVersion( args.plugin );

		if ( versionLessThan( nextVersion, currentVersion ) ) {
			this.error(
				'The version supplied is less than the current version, please supply a valid version.'
			);
		}
	}

	/**
	 * Update plugin readme stable tag.
	 *
	 * @param plugin plugin to update
	 * @param nextVersion version to bump to
	 */
	private updateReadmeStableTag( plugin: string, nextVersion: string ): void {
		const filePath = `plugins/${ plugin }/readme.txt`;
		try {
			const readmeContents = readFileSync( filePath, 'utf8' );

			const updatedReadmeContents = readmeContents.replace(
				/Stable tag: \d.\d.\d\n/m,
				`Stable tag: ${ nextVersion }\n`
			);

			writeFileSync( filePath, updatedReadmeContents );
		} catch ( e ) {
			this.error( 'Unable to update readme stable tag' );
		}
	}

	/**
	 * Update plugin readme changelog.
	 *
	 * @param plugin plugin to update
	 * @param nextVersion version to bump to
	 */
	private updateReadmeChangelog( plugin: string, nextVersion: string ): void {
		const filePath = `plugins/${ plugin }/readme.txt`;
		try {
			const readmeContents = readFileSync( filePath, 'utf8' );

			const updatedReadmeContents = readmeContents.replace(
				/= \d.\d.\d \d\d\d\d-XX-XX =\n/m,
				`= ${ nextVersion } ${ new Date().getFullYear() }-XX-XX =\n`
			);

			writeFileSync( filePath, updatedReadmeContents );
		} catch ( e ) {
			this.error( 'Unable to update readme changelog' );
		}
	}

	/**
	 * Update plugin class file.
	 *
	 * @param plugin plugin to update
	 * @param nextVersion version to bump to
	 */
	private updateClassPluginFile( plugin: string, nextVersion: string ): void {
		const filePath = `plugins/${ plugin }/includes/class-${ plugin }.php`;
		try {
			const classPluginFileContents = readFileSync( filePath, 'utf8' );

			const updatedClassPluginFileContents =
				classPluginFileContents.replace(
					/public \$version = '\d.\d.\d';\n/m,
					`public $version = '${ nextVersion }';\n`
				);

			writeFileSync( filePath, updatedClassPluginFileContents );
		} catch ( e ) {
			this.error( 'Unable to update plugin file.' );
		}
	}

	/**
	 * Update plugin composer.json.
	 *
	 * @param plugin plugin to update
	 * @param nextVersion version to bump to
	 */
	private updateComposerJSON( plugin: string, nextVersion: string ): void {
		const filePath = `plugins/${ plugin }/composer.json`;
		try {
			const composerJson = JSON.parse( readFileSync( filePath, 'utf8' ) );
			composerJson.version = nextVersion;
			writeFileSync(
				filePath,
				JSON.stringify( composerJson, null, '\t' ) + '\n'
			);
		} catch ( e ) {
			this.error( 'Unable to update composer.json' );
		}
	}

	/**
	 * Update plugin main file.
	 *
	 * @param plugin plugin to update
	 * @param nextVersion version to bump to
	 */
	private updatePluginFile( plugin: string, nextVersion: string ): void {
		const filePath = `plugins/${ plugin }/${ plugin }.php`;
		try {
			const pluginFileContents = readFileSync( filePath, 'utf8' );

			const updatedPluginFileContents = pluginFileContents.replace(
				/Version: \d.\d.\d.*\n/m,
				`Version: ${ nextVersion }\n`
			);
			writeFileSync( filePath, updatedPluginFileContents );
		} catch ( e ) {
			this.error( 'Unable to update plugin file.' );
		}
	}

	/**
	 * Get a plugin's current version.
	 *
	 * @param plugin plugin to update
	 */
	private getCurrentVersion( plugin: string ): string {
		try {
			const composerJSON = JSON.parse(
				readFileSync( `plugins/${ plugin }/composer.json`, 'utf8' )
			);
			return composerJSON.version;
		} catch ( e ) {
			this.error( 'Unable to read current version.' );
		}
	}
}
