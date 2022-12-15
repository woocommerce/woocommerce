/**
 * External dependencies
 */
import { readFile, writeFile, stat } from 'fs/promises';
import { join } from 'path';
import { Logger } from 'cli-core/src/logger';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from './const';

/**
 * Update plugin readme stable tag.
 *
 * @param  plugin      plugin to update
 * @param  nextVersion version to bump to
 */
export const updateReadmeStableTag = async (
	plugin: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join( MONOREPO_ROOT, `plugins/${ plugin }/readme.txt` );
	try {
		const readmeContents = await readFile( filePath, 'utf8' );

		const updatedReadmeContents = readmeContents.replace(
			/Stable tag: \d+\.\d+\.\d+\n/m,
			`Stable tag: ${ nextVersion }\n`
		);

		await writeFile( filePath, updatedReadmeContents );
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
export const updateReadmeChangelog = async (
	plugin: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join( MONOREPO_ROOT, `plugins/${ plugin }/readme.txt` );
	try {
		const readmeContents = await readFile( filePath, 'utf8' );

		const updatedReadmeContents = readmeContents.replace(
			/= \d+\.\d+\.\d+ \d\d\d\d-XX-XX =\n/m,
			`= ${ nextVersion } ${ new Date().getFullYear() }-XX-XX =\n`
		);

		await writeFile( filePath, updatedReadmeContents );
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
export const updateClassPluginFile = async (
	plugin: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join(
		MONOREPO_ROOT,
		`plugins/${ plugin }/includes/class-${ plugin }.php`
	);

	try {
		await stat( filePath );
	} catch ( e ) {
		// Class file does not exist, return early.
		return;
	}

	try {
		const classPluginFileContents = await readFile( filePath, 'utf8' );

		const updatedClassPluginFileContents = classPluginFileContents.replace(
			/public \$version = '\d+\.\d+\.\d+';\n/m,
			`public $version = '${ nextVersion }';\n`
		);

		await writeFile( filePath, updatedClassPluginFileContents );
	} catch ( e ) {
		Logger.error( 'Unable to update plugin file.' );
	}
};

/**
 * Update plugin JSON files.
 *
 * @param {string} type        plugin to update
 * @param {string} plugin      plugin to update
 * @param {string} nextVersion version to bump to
 */
export const updateJSON = async (
	type: 'package' | 'composer',
	plugin: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join(
		MONOREPO_ROOT,
		`plugins/${ plugin }/${ type }.json`
	);
	try {
		const composerJson = JSON.parse( await readFile( filePath, 'utf8' ) );
		composerJson.version = nextVersion;
		await writeFile(
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
	const filePath = join(
		MONOREPO_ROOT,
		`plugins/${ plugin }/${ plugin }.php`
	);
	try {
		const pluginFileContents = await readFile( filePath, 'utf8' );

		const updatedPluginFileContents = pluginFileContents.replace(
			/Version: \d+\.\d+\.\d+.*\n/m,
			`Version: ${ nextVersion }\n`
		);
		await writeFile( filePath, updatedPluginFileContents );
	} catch ( e ) {
		Logger.error( 'Unable to update plugin file.' );
	}
};
