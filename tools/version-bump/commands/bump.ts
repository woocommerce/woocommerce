/**
 * External dependencies
 */
import { prerelease } from 'semver';

/**
 * Internal dependencies
 */
import { program } from '../program';
import { validateArgs, stripPrereleaseParameters } from '../lib/validate';
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
		const isDevVersionBump =
			prereleaseParameters && prereleaseParameters[ 0 ] === 'dev';

		updatePluginFile( plugin, nextVersion );

		// Any updated files besides the plugin file get a version stripped of prerelease parameters.
		nextVersion = stripPrereleaseParameters( nextVersion );

		if ( isDevVersionBump ) {
			// Bumping the dev version means updating the readme's changelog.
			updateReadmeChangelog( plugin, nextVersion );
		} else {
			// Only update stable tag on real releases.
			updateReadmeStableTag( plugin, nextVersion );
		}

		updateComposerJSON( plugin, nextVersion );
		updateClassPluginFile( plugin, nextVersion );
	} );
