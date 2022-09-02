/**
 * External dependencies
 */
import { prerelease } from 'semver';

/**
 * Internal dependencies
 */
import { program } from '../program';
import { validateArgs } from '../lib/validate';
import {
	updatePluginFile,
	updateReadmeChangelog,
	updateComposerJSON,
	updateClassPluginFile,
	updateReadmeStableTag,
} from '../lib/update';

program
	.command( 'bump' )
	.description( 'CLI to automate version bumping.' )
	.argument( '<plugin>', 'Monorepo plugin' )
	.requiredOption( '-v, --version <string>', 'Version to bump to' )
	.action( ( plugin: string, options ) => {
		validateArgs( plugin, options );

		let nextVersion = options.version;

		const prereleaseParameters = prerelease( nextVersion );
		const isPrerelease = !! prereleaseParameters;
		const isDevVersionBump =
			prereleaseParameters && prereleaseParameters[ 0 ] === 'dev';

		updatePluginFile( plugin, nextVersion );

		if ( isPrerelease && ! isDevVersionBump ) {
			// Prereleases such as beta or rc only bump the plugin file.
			return;
		}

		if ( isDevVersionBump ) {
			// When updating to a dev version, only the plugin file gets the '-dev'.
			nextVersion = nextVersion.replace( '-dev', '' );
			// Bumping the dev version means updating the readme's changelog.
			updateReadmeChangelog( plugin, nextVersion );
		}

		updateComposerJSON( plugin, nextVersion );
		updateClassPluginFile( plugin, nextVersion );
		updateReadmeStableTag( plugin, nextVersion );
	} );
