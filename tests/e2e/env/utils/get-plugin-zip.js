const path = require( 'path' );
const getAppRoot = require( './app-root' );
const fs = require('fs');
const mkdirp = require( 'mkdirp' );
const request = require('request');
const StreamZip = require('node-stream-zip');

/**
 * Upload a plugin zip from a remote location, such as a GitHub URL or other hosted location.
 *
 * @param {string} fileUrl The URL where the zip file is located.
 * @returns {string} The path where the zip file is located.
 */
const getRemotePluginZip = async ( fileUrl ) => {
	const appPath = getAppRoot();
	const savePath = path.resolve( appPath, 'tests/e2e/plugins' );
	mkdirp.sync( savePath );

	// Pull the filename from the end of the URL
	let fileName = fileUrl.split('/').pop();
	let filePath = path.join( savePath, fileName );

	// First, download the zip file
	await downloadZip( fileUrl, filePath );

	// Check for a nested zip and update the filepath
	filePath = await checkNestedZip( filePath, savePath );

	return filePath;
};

/**
 * Check the zip file for any nested zips. If one is found, extract it.
 *
 * @param {string} zipFilePath The location of the zip file.
 * @param {string} savePath The location where to save a nested zip if found.
 * @returns {string} The path where the zip file is located.
 */
const checkNestedZip = async ( zipFilePath, savePath ) => {
	const zip = new StreamZip.async( { file: zipFilePath } );
	const entries = await zip.entries();

	for (const entry of Object.values( entries )) {
		if ( entry.name.match( /.zip/ )) {
			await zip.extract( null, savePath );
			await zip.close();
			return path.join( savePath, entry.name );
		}
	}

	return zipFilePath;
}

/**
 * Download the zip file from a remote location.
 *
 * @param {string} fileUrl The URL where the zip file is located.
 * @param {string} downloadPath The location where to download the zip to.
 * @returns {Promise<void>}
 */
const downloadZip = async ( fileUrl, downloadPath ) => {
	const options = {
		url: fileUrl,
		method: 'GET',
		encoding: null,
	};

	// Wrap in a promise to make the request async
	return new Promise( function( resolve, reject ) {
		request.get(options, function( err, resp, body ) {
			if ( err ) {
				reject( err );
			} else {
				resolve( body );
			}
		})
	  .pipe( fs.createWriteStream( downloadPath ) );
	});
};

module.exports = {
	getRemotePluginZip,
	checkNestedZip,
	downloadZip,
};
