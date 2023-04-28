/**
 * External dependencies
 */
import { readFile, writeFile, stat } from 'fs/promises';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';

/**
 * Update plugin readme stable tag.
 *
 * @param  tmpRepoPath cloned repo path
 * @param  nextVersion version to bump to
 */
export const updateReadmeStableTag = async (
	tmpRepoPath: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join( tmpRepoPath, `plugins/woocommerce/readme.txt` );
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
 * @param  tmpRepoPath cloned repo path
 * @param  nextVersion version to bump to
 */
export const updateReadmeChangelog = async (
	tmpRepoPath: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join( tmpRepoPath, `plugins/woocommerce/readme.txt` );
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
 * @param  tmpRepoPath cloned repo path
 * @param  nextVersion version to bump to
 */
export const updateClassPluginFile = async (
	tmpRepoPath: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join(
		tmpRepoPath,
		`plugins/woocommerce/includes/class-woocommerce.php`
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
 * @param {string} tmpRepoPath cloned repo path
 * @param {string} nextVersion version to bump to
 */
export const updateJSON = async (
	type: 'package' | 'composer',
	tmpRepoPath: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join( tmpRepoPath, `plugins/woocommerce/${ type }.json` );
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
 * @param  tmpRepoPath cloned repo path
 * @param  nextVersion version to bump to
 */
export const updatePluginFile = async (
	tmpRepoPath: string,
	nextVersion: string
): Promise< void > => {
	const filePath = join( tmpRepoPath, `plugins/woocommerce/woocommerce.php` );
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
