/**
 * External dependencies
 */
import axios, { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import * as fs from 'fs';
import * as path from 'path';
import type { APIRequest } from '@playwright/test';

/**
 * Internal dependencies
 */
import { wpCLI } from './cli';

/**
 * Parameters for getting latest release zip URL.
 */
interface GetLatestReleaseZipUrlParams {
	repository: string;
	authorizationToken?: string;
	prerelease?: boolean;
	perPage?: number;
}

/**
 * Parameters for downloading a zip file.
 */
interface DownloadZipParams {
	url?: string;
	repository: string;
	authorizationToken?: string;
	prerelease?: boolean;
	downloadDir?: string;
}

/**
 * Parameters for deleting a plugin.
 */
interface DeletePluginParams {
	request: APIRequest;
	baseURL: string;
	slug: string;
	username: string;
	password: string;
}

/**
 * GitHub release interface.
 */
interface GitHubRelease {
	tag_name: string;
	prerelease: boolean;
	assets: Array< {
		url: string;
		name: string;
		browser_download_url: string;
	} >;
}

/**
 * WordPress plugin interface.
 */
interface WordPressPlugin {
	plugin: string;
	textdomain: string;
	name: string;
	version: string;
}

/**
 * Encode basic auth username and password to be used in HTTP Authorization header.
 *
 * @param username - The username
 * @param password - The password
 * @return Base64-encoded string
 */
export const encodeCredentials = (
	username: string,
	password: string
): string => {
	return Buffer.from( `${ username }:${ password }` ).toString( 'base64' );
};

/**
 * Get the download URL of the latest release zip for a plugin using GitHub API.
 *
 * @param params - Parameters for the request.
 * @return Download URL for the release zip file.
 */
export const getLatestReleaseZipUrl = async ( {
	repository,
	authorizationToken,
	prerelease = false,
	perPage = 3,
}: GetLatestReleaseZipUrlParams ): Promise< string > => {
	const requesturl = prerelease
		? `https://api.github.com/repos/${ repository }/releases?per_page=${ perPage }`
		: `https://api.github.com/repos/${ repository }/releases/latest`;

	const options: AxiosRequestConfig = {
		method: 'get',
		url: requesturl,
		headers: {
			Authorization: authorizationToken
				? `token ${ authorizationToken }`
				: '',
		},
	};

	// Get the first prerelease, or the latest release.
	let response: AxiosResponse< GitHubRelease | GitHubRelease[] >;
	try {
		response = await axios( options );
	} catch ( error ) {
		let errorMessage =
			'Something went wrong when downloading the plugin.\n';

		const axiosError = error as AxiosError;

		if ( axiosError.response ) {
			// The request was made and the server responded with a status code
			// that falls out of the range of 2xx
			errorMessage = errorMessage.concat(
				`Response status: ${ axiosError.response.status } ${ axiosError.response.statusText }`,
				'\n',
				`Response body:`,
				'\n',
				JSON.stringify( axiosError.response.data, null, 2 ),
				'\n'
			);
		} else if ( axiosError.request ) {
			// The request was made but no response was received
			// `error.request` is an instance of XMLHttpRequest in the browser and an instance of
			// http.ClientRequest in node.js
			errorMessage = errorMessage.concat(
				JSON.stringify( axiosError.request, null, 2 ),
				'\n'
			);
		} else if ( axiosError.toJSON ) {
			// Something happened in setting up the request that triggered an Error
			errorMessage = errorMessage.concat(
				JSON.stringify( axiosError.toJSON(), null, 2 ),
				'\n'
			);
		}

		throw new Error( errorMessage );
	}

	const release: GitHubRelease = prerelease
		? ( response.data as GitHubRelease[] ).find(
				( r: GitHubRelease ) => r.prerelease
		  )!
		: ( response.data as GitHubRelease );

	// If response contains assets, return URL of first asset.
	// Otherwise, return the github.com URL from the tag name.
	const { assets } = release;
	if ( assets && assets.length ) {
		return assets[ 0 ].url;
	}
	const tagName = release.tag_name;
	return `https://github.com/${ repository }/archive/${ tagName }.zip`;
};

/**
 * Deactivate and delete a plugin specified by the given `slug` using the WordPress API.
 *
 * @param params - Parameters for the delete operation.
 */
export const deletePlugin = async ( {
	request,
	baseURL,
	slug,
	username,
	password,
}: DeletePluginParams ): Promise< void > => {
	// Check if plugin is installed by getting the list of installed plugins, and then finding the one whose `textdomain` property equals `slug`.
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials( username, password ) }`,
			cookie: '',
		},
	} );
	const listPluginsResponse = await apiContext.get(
		`/wp-json/wp/v2/plugins`,
		{
			failOnStatusCode: true,
		}
	);
	const pluginsList =
		( await listPluginsResponse.json() ) as WordPressPlugin[];
	const pluginToDelete = pluginsList.find(
		( { textdomain } ) => textdomain === slug
	);

	// If installed, get its `plugin` value and use it to deactivate and delete it.
	if ( pluginToDelete ) {
		const { plugin } = pluginToDelete;
		const requestURL = `/wp-json/wp/v2/plugins/${ plugin }`;

		await apiContext.put( requestURL, {
			data: { status: 'inactive' },
		} );

		await apiContext.delete( requestURL );
	}
};

/**
 * Download the zip file from a remote location.
 *
 * @param params - Parameters for the download operation.
 * @return Absolute path to the downloaded zip.
 */
export const downloadZip = async ( {
	url,
	repository,
	authorizationToken,
	prerelease = false,
	downloadDir = 'tmp',
}: DownloadZipParams ): Promise< string > => {
	let zipFilename = path.basename( url || repository );
	zipFilename = zipFilename.endsWith( '.zip' )
		? zipFilename
		: zipFilename.concat( '.zip' );
	const zipFilePath = path.resolve( downloadDir, zipFilename );

	// Create destination folder.
	fs.mkdirSync( downloadDir, { recursive: true } );

	const downloadURL =
		url ??
		( await getLatestReleaseZipUrl( {
			repository,
			authorizationToken,
			prerelease,
		} ) );

	// Download the zip.
	const options: AxiosRequestConfig = {
		method: 'get',
		url: downloadURL,
		responseType: 'stream',
		headers: {
			Authorization: authorizationToken
				? `token ${ authorizationToken }`
				: '',
			Accept: 'application/octet-stream',
		},
	};

	const response = await axios( options ).catch( ( error: AxiosError ) => {
		if ( error.response ) {
			console.error( error.response.data );
		}
		throw new Error( error.message );
	} );

	response.data.pipe( fs.createWriteStream( zipFilePath ) );

	return zipFilePath;
};

/**
 * Delete a zip file. Useful when cleaning up downloaded plugin zips.
 *
 * @param zipFilePath - Local file path to the ZIP.
 */
export const deleteZip = async ( zipFilePath: string ): Promise< void > => {
	await fs.unlink( zipFilePath, ( err ) => {
		if ( err ) throw err;
	} );
};

/**
 * Install a plugin using WP CLI within a WP ENV environment.
 * This is a workaround to the "The uploaded file exceeds the upload_max_filesize directive in php.ini" error encountered when uploading a plugin to the local WP Env E2E environment through the UI.
 *
 * @see https://github.com/WordPress/gutenberg/issues/29430
 *
 * @param pluginPath - The path to the plugin zip file.
 */
export const installPluginThruWpCli = async (
	pluginPath: string
): Promise< void > => {
	const wpEnvPluginPath = pluginPath.replace(
		/.*\/plugins\/woocommerce/,
		'wp-content/plugins/woocommerce'
	);

	await wpCLI( `ls  ${ wpEnvPluginPath }` );

	await wpCLI( `wp plugin install --activate --force ${ wpEnvPluginPath }` );

	await wpCLI( `wp plugin list` );
};
