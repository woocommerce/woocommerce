/**
 * External dependencies
 */
import { prerelease } from 'semver';
import { Logger } from 'cli-core/src/logger';

/**
 * Internal dependencies
 */
import { program } from '../program';
import { validateArgs, stripPrereleaseParameters } from '../lib/validate';
import {
	updatePluginFile,
	updateReadmeChangelog,
	updateJSON,
	updateClassPluginFile,
	updateReadmeStableTag,
} from '../lib/update';

program
	.command( 'bump' )
	.description( 'CLI to automate version bumping.' )
	.argument( '<plugin>', 'Monorepo plugin' )
	.requiredOption( '-v, --version <string>', 'Version to bump to' )
	.action( async ( plugin: string, options ) => {
		Logger.startTask( `Bumping versions to ${ options.version }` );

		await validateArgs( plugin, options );

		let nextVersion = options.version;

		const prereleaseParameters = prerelease( nextVersion );
		const isDevVersionBump =
			prereleaseParameters && prereleaseParameters[ 0 ] === 'dev';

		await updatePluginFile( plugin, nextVersion );

		// Any updated files besides the plugin file get a version stripped of prerelease parameters.
		nextVersion = stripPrereleaseParameters( nextVersion );

		if ( isDevVersionBump ) {
			// Bumping the dev version means updating the readme's changelog.
			await updateReadmeChangelog( plugin, nextVersion );
		} else {
			// Only update stable tag on real releases.
			await updateReadmeStableTag( plugin, nextVersion );
		}

		await updateJSON( 'composer', plugin, nextVersion );
		await updateJSON( 'package', plugin, nextVersion );
		await updateClassPluginFile( plugin, nextVersion );

		Logger.endTask();
	} );
