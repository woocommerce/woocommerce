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
	// Check if plugin is installed by getting the list of installed plugins, and then finding the one whose `textdomain` property equals `slug`.
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials( username, password ) }`,
		},
	} );
	const listPluginsResponse = await apiContext.get(
		`/wp-json/wp/v2/plugins`,
		{
			failOnStatusCode: true,
		}
	);
	const pluginsList = await listPluginsResponse.json();
	const pluginToDelete = pluginsList.find(
		( { textdomain } ) => textdomain === slug
	);

	// If installed, get its `plugin` value and use it to deactivate and delete it.
	if ( pluginToDelete ) {
		const { plugin } = pluginToDelete;

		await apiContext.put( `/wp-json/wp/v2/plugins/${ plugin }`, {
			data: { status: 'inactive' },
			failOnStatusCode: true,
		} );

		await apiContext.delete( `/wp-json/wp/v2/plugins/${ plugin }`, {
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
		headers: {
			'user-agent': 'node.js',
		},
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
 * @param {string} zipFilePath Local file path to the ZIP.
 */
export const deleteZip = async ( zipFilePath ) => {
	console.log( `Deleting file located in ${ zipFilePath }...` );
	await fs.unlink( zipFilePath, ( err ) => {
		if ( err ) throw err;
		console.log( `Successfully deleted!` );
	} );
};

/**
 * Get the download URL of the latest release zip for a plugin using GitHub's {@link https://docs.github.com/en/rest/releases/releases Releases API}.
 *
 * @param {{repository: string, authorizationToken: string, prerelease: boolean, perPage: number}} param
 * @param {string} repository The repository owner and name. For example: `woocommerce/woocommerce`.
 * @param {string} authorizationToken Authorization token used to authenticate with the GitHub API if required.
 * @param {boolean} prerelease Flag on whether to get a prelease or not.
 * @param {number} perPage Limit of entries returned from the latest releases list, defaults to 3.
 * @return {string} Download URL for the release zip file.
 */
export const getLatestReleaseZipUrl = async ( {
	repository,
	authorizationToken,
	prerelease = false,
	perPage = 3,
} ) => {
	const requesturl = prerelease
		? `https://api.github.com/repos/${ repository }/releases?per_page=${ perPage }`
		: `https://api.github.com/repos/${ repository }/releases/latest`;

	const options = {
		url: requesturl,
		headers: { 'user-agent': 'node.js' },
	};

	// If provided with a token, use it for authorization
	if ( authorizationToken ) {
		options.headers.Authorization = `token ${ authorizationToken }`;
	}

	// Call the List releases API endpoint in GitHub
	const response = await axios( options );
	const body = response.data;

	// If it's a prerelease, find the first one and return its download URL.
	if ( prerelease ) {
		const latestPrerelease = body.find( ( { prerelease } ) => prerelease );

		return latestPrerelease.assets[ 0 ].browser_download_url;
	} else if ( authorizationToken ) {
		// If it's a private repo, we need to download the archive this way.
		// Use uploaded assets over downloading the zip archive.
		if (
			body.assets &&
			body.assets.length > 0 &&
			body.assets[ 0 ].browser_download_url
		) {
			return body.assets[ 0 ].browser_download_url;
		} else {
			const tagName = body.tag_name;
			return `https://github.com/${ repository }/archive/${ tagName }.zip`;
		}
	} else {
		return body.assets[ 0 ].browser_download_url;
	}
};

/**
 * Use the {@link https://developer.wordpress.org/rest-api/reference/plugins/#create-a-plugin Create plugin endpoint} to install and activate a plugin.
 *
 * @param {object} params
 * @param {APIRequest} params.request
 * @param {string} params.baseURL
 * @param {string} params.slug
 * @param {string} params.username
 * @param {string} params.password
 */
export const createPlugin = async ( {
	request,
	baseURL,
	slug,
	username,
	password,
} ) => {
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials( username, password ) }`,
		},
	} );

	await apiContext.post( '/wp-json/wp/v2/plugins', {
		data: { slug, status: 'active' },
		failOnStatusCode: true,
	} );
};
