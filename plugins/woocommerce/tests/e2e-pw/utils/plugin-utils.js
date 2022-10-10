const { APIRequest } = require( '@playwright/test' );
const axios = require( 'axios' ).default;
const fs = require( 'fs' );
const path = require( 'path' );

/**
 * Encode basic auth username and password to be used in HTTP Authorization header.
 *
 * @param {string} username
 * @param {string} password
 * @returns Base64-encoded string
 */
const encodeCredentials = ( username, password ) => {
	return Buffer.from( `${ username }:${ password }` ).toString( 'base64' );
};

/**
 * Deactivate and delete a plugin specified by the given `slug` using the WordPress API.
 *
 * @param {object} params
 * @param {APIRequest} params.request
 * @param {string} params.baseURL
 * @param {string} params.slug
 * @param {string} params.username
 * @param {string} params.password
 */
export const deletePlugin = async ( {
	request,
	baseURL,
	slug,
	username,
	password,
} ) => {
	// Check if plugin is installed.
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials( username, password ) }`,
		},
	} );

	const response = await apiContext.get( `/wp-json/wp/v2/plugins/${ slug }`, {
		failOnStatusCode: true,
	} );

	// If installed, deactivate and delete it.
	if ( response.ok() ) {
		await apiContext.put( `/wp-json/wp/v2/plugins/${ slug }`, {
			data: { status: 'inactive' },
			failOnStatusCode: true,
		} );

		await apiContext.delete( `/wp-json/wp/v2/plugins/${ slug }`, {
			failOnStatusCode: true,
		} );
	}
};

/**
 * Download the zip file from a remote location.
 *
 * @param {object} param
 * @param {string} param.url The URL where the zip file is located.
 * @param {string} param.downloadPath The location where to download the zip to.
 * @param {string} param.authToken Authorization token used to authenticate with the GitHub API if required.
 */
export const downloadZip = async ( { url, downloadPath, authToken } ) => {
	// Create destination folder.
	const dir = path.dirname( downloadPath );
	fs.mkdirSync( dir, { recursive: true } );

	// Download the zip.
	const options = {
		url,
		responseType: 'stream',
	};

	// If provided with a token, use it for authorization
	if ( authToken ) {
		options.headers.Authorization = `token ${ authToken }`;
	}

	const response = await axios( options );
	response.data.pipe( fs.createWriteStream( downloadPath ) );
};

/**
 * Delete a zip file. Useful when cleaning up downloaded plugin zips.
 *
 * @param {string} path Local file path to the ZIP.
 */
export const deleteZip = async ( path ) => {
	await fs.unlink( path, ( err ) => {
		if ( err ) throw err;
		console.log( `Successfully deleted: ${ path }` );
	} );
};
