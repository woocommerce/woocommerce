/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { sync: glob } = require( 'fast-glob' );

const srcDir = path.resolve( process.cwd(), 'src' );
const blocksBuildDir = '/build/blocks';

/**
 * Get all the block meta data files in the src directory.
 *
 * @return {string[]} Block file paths.
 */
const getBlockMetaDataFiles = () => {
	return glob( `${ srcDir.replace( /\\/g, '/' ) }/**/block.json`, {
		absolute: true,
	} );
};

/**
 * Get the block meta data from a block.json file.
 *
 * @param {string} filePath File path to block.json file.
 * @return {BlockConfiguration} Block meta data.
 */
const getBlockMetaData = ( filePath ) => {
	return JSON.parse( fs.readFileSync( filePath ) );
};

/**
 * Get the block file assets with raw file paths.
 *
 * @param {BlockConfiguration} blockMetaData
 * @return {string[]} Asset file paths.
 */
const getBlockFileAssets = ( blockMetaData ) => {
	const { editorScript, script, viewScript, style, editorStyle } =
		blockMetaData;

	return [ editorScript, script, viewScript, style, editorStyle ]
		.flat()
		.filter(
			( rawFilepath ) => rawFilepath && rawFilepath.startsWith( 'file:' )
		);
};

/**
 * Get the block name from the meta data, removing the `woocommerce/` namespace.
 *
 * @param {BlockConfiguration} blockMetaData
 * @return {string} Block name.
 */
const getBlockName = ( blockMetaData ) => {
	return blockMetaData.name.split( '/' ).at( 1 );
};

/**
 * Get the entry point name.
 *
 * @param {string}             entryFilePath
 * @param {BlockConfiguration} blockMetaData
 * @return {string} The entry point name.
 */
const getEntryPointName = ( entryFilePath, blockMetaData ) => {
	const filePathParts = entryFilePath.split( '/' );
	filePathParts[ filePathParts.length - 2 ] = getBlockName( blockMetaData );
	return filePathParts
		.join( '/' )
		.replace( srcDir, blocksBuildDir )
		.replace( '/components', '' );
};

/**
 * Get the entry file path.
 *
 * @param {string} rawFilepath Raw file path from the block.json file.
 * @param {*}      dir         The directory the block exists in.
 * @return {string} Entry file path.
 */
const getEntryFilePath = ( rawFilepath, dir ) => {
	const filepath = path.join( dir, rawFilepath.replace( 'file:', '' ) );

	return filepath
		.replace( path.extname( filepath ), '' )
		.replace( /\\/g, '/' );
};

/**
 * Gets the absolute file path based on the entry file path, including the extension.
 *
 * @param {string} entryFilePath Entry file path.
 * @return {string} Absolute file path.
 */
const getAbsoluteEntryFilePath = ( entryFilePath ) => {
	const [ absoluteEntryFilepath ] = glob(
		`${ entryFilePath }.([jt]s?(x)|?(s)css)`,
		{
			absolute: true,
		}
	);
	return absoluteEntryFilepath;
};

/**
 * Find all directories with block.json files and get entry points for block related assets.
 */
const blockEntryPoints = getBlockMetaDataFiles().reduce(
	( accumulator, blockMetadataFile ) => {
		const blockMetaData = getBlockMetaData( blockMetadataFile );

		getBlockFileAssets( blockMetaData ).forEach( ( rawFilePath ) => {
			const entryFilePath = getEntryFilePath(
				rawFilePath,
				path.dirname( blockMetadataFile )
			);

			const absoluteEntryFilepath =
				getAbsoluteEntryFilePath( entryFilePath );

			if ( ! absoluteEntryFilepath ) {
				// eslint-disable-next-line no-console
				console.warn( 'Block asset file not found.', entryFilePath );
				return;
			}

			const entryPointName = getEntryPointName(
				entryFilePath,
				blockMetaData
			);

			accumulator[ entryPointName ] = absoluteEntryFilepath;
		} );
		return accumulator;
	},
	{}
);

module.exports = {
	blocksBuildDir,
	blockEntryPoints,
	getBlockMetaData,
	getEntryPointName,
};
