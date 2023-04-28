/**
 * External dependencies
 */
import { prerelease } from 'semver';

/**
 * Internal dependencies
 */
import { stripPrereleaseParameters } from './lib/validate';
import {
	updatePluginFile,
	updateReadmeChangelog,
	updateJSON,
	updateClassPluginFile,
	updateReadmeStableTag,
} from './lib/update';

export const bumpFiles = async ( tmpRepoPath, version ) => {
	let nextVersion = version;

	const prereleaseParameters = prerelease( nextVersion );
	const isDevVersionBump =
		prereleaseParameters && prereleaseParameters[ 0 ] === 'dev';

	await updatePluginFile( tmpRepoPath, nextVersion );

	// Any updated files besides the plugin file get a version stripped of prerelease parameters.
	nextVersion = stripPrereleaseParameters( nextVersion );

	if ( isDevVersionBump ) {
		// Bumping the dev version means updating the readme's changelog.
		await updateReadmeChangelog( tmpRepoPath, nextVersion );
	} else {
		// Only update stable tag on real releases.
		await updateReadmeStableTag( tmpRepoPath, nextVersion );
	}

	await updateJSON( 'composer', tmpRepoPath, nextVersion );
	await updateJSON( 'package', tmpRepoPath, nextVersion );
	await updateClassPluginFile( tmpRepoPath, nextVersion );
};
