/**
 * Internal dependencies
 */
import { stripPrereleaseParameters } from './lib/validate';
import {
	updatePluginFile,
	updateReadmeChangelog,
	updateJSON,
	updateClassPluginFile,
} from './lib/update';

export const bumpFiles = async ( tmpRepoPath, version ) => {
	let nextVersion = version;

	await updatePluginFile( tmpRepoPath, nextVersion );

	// Any updated files besides the plugin file get a version stripped of prerelease parameters.
	nextVersion = stripPrereleaseParameters( nextVersion );

	// Bumping the dev version means updating the readme's changelog.
	await updateReadmeChangelog( tmpRepoPath, nextVersion );

	await updateJSON( 'composer', tmpRepoPath, nextVersion );
	await updateJSON( 'package', tmpRepoPath, nextVersion );
	await updateClassPluginFile( tmpRepoPath, nextVersion );
};
