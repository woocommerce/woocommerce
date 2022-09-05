/**
 * External dependencies
 */
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { readFile, writeFile } from 'fs/promises';

/**
 * Internal dependencies
 */
import { Logger } from './logger';

/**
 * Update plugin readme stable tag.
 *
 * @param  plugin      plugin to update
 * @param  nextVersion version to bump to
 */
export const updateReadmeStableTag = (
	plugin: string,
	nextVersion: string
): void => {
	const filePath = `plugins/${ plugin }/readme.txt`;
	try {
		const readmeContents = readFileSync( filePath, 'utf8' );

		const updatedReadmeContents = readmeContents.replace(
			/Stable tag: \d.\d.\d\n/m,
			`Stable tag: ${ nextVersion }\n`
		);

		writeFileSync( filePath, updatedReadmeContents );
	} catch ( e ) {
		Logger.error( 'Unable to update readme stable tag' );
	}
};

/**
 * Update plugin readme changelog.
 *
 * @param  plugin      plugin to update
 * @param  nextVersion version to bump to
 */
export const updateReadmeChangelog = (
	plugin: string,
	nextVersion: string
): void => {
	const filePath = `plugins/${ plugin }/readme.txt`;
	try {
		const readmeContents = readFileSync( filePath, 'utf8' );

		const updatedReadmeContents = readmeContents.replace(
			/= \d.\d.\d \d\d\d\d-XX-XX =\n/m,
			`= ${ nextVersion } ${ new Date().getFullYear() }-XX-XX =\n`
		);

		writeFileSync( filePath, updatedReadmeContents );
	} catch ( e ) {
		Logger.error( 'Unable to update readme changelog' );
	}
};

/**
 * Update plugin class file.
 *
 * @param  plugin      plugin to update
 * @param  nextVersion version to bump to
 */
export const updateClassPluginFile = (
	plugin: string,
	nextVersion: string
): void => {
	const filePath = `plugins/${ plugin }/includes/class-${ plugin }.php`;

	if ( ! existsSync( filePath ) ) {
		return;
	}

	try {
		const classPluginFileContents = readFileSync( filePath, 'utf8' );

		const updatedClassPluginFileContents = classPluginFileContents.replace(
			/public \$version = '\d.\d.\d';\n/m,
			`public $version = '${ nextVersion }';\n`
		);

		writeFileSync( filePath, updatedClassPluginFileContents );
	} catch ( e ) {
		Logger.error( 'Unable to update plugin file.' );
	}
};

/**
 * Update plugin composer.json.
 *
 * @param  plugin      plugin to update
 * @param  nextVersion version to bump to
 */
export const updateComposerJSON = (
	plugin: string,
	nextVersion: string
): void => {
	const filePath = `plugins/${ plugin }/composer.json`;
	try {
		const composerJson = JSON.parse( readFileSync( filePath, 'utf8' ) );
		composerJson.version = nextVersion;
		writeFileSync(
			filePath,
			JSON.stringify( composerJson, null, '\t' ) + '\n'
		);
	} catch ( e ) {
		Logger.error( 'Unable to update composer.json' );
	}
};

/**
 * Update plugin main file.
 *
 * @param  plugin      plugin to update
 * @param  nextVersion version to bump to
 */
export const updatePluginFile = async (
	plugin: string,
	nextVersion: string
): Promise< void > => {
	const filePath = `plugins/${ plugin }/${ plugin }.php`;
	try {
		const pluginFileContents = await readFile( filePath, 'utf8' );

		const updatedPluginFileContents = pluginFileContents.replace(
			/Version: \d.\d.\d.*\n/m,
			`Version: ${ nextVersion }\n`
		);
		await writeFile( filePath, updatedPluginFileContents );
	} catch ( e ) {
		Logger.error( 'Unable to update plugin file.' );
	}
};
