const { APIRequest } = require( '@playwright/test' );
const axios = require( 'axios' ).default;
const fs = require( 'fs' );
const path = require( 'path' );
const { promisify } = require( 'util' );
const execAsync = promisify( require( 'child_process' ).exec );

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
		const requestURL = `/wp-json/wp/v2/plugins/${ plugin }`;
		let response;

		// Some plugins do not respond to some of the WP REST API endpoints like Contact Form 7.
		// Print warning in such cases.
		const warn = async ( response, warningMsg ) => {
			console.warn( warningMsg );
			console.warn(
				`Response status: ${ response.status() } ${ response.statusText() }`
			);
			console.warn( `Response body:` );
			console.warn( await response.json() );
			console.warn( '\n' );
		};

		response = await apiContext.put( requestURL, {
			data: { status: 'inactive' },
		} );
		if ( ! response.ok() ) {
			await warn(
				response,
				`WARNING: Failed to deactivate plugin ${ plugin }`
			);
		}

		response = await apiContext.delete( requestURL );
		if ( ! response.ok() ) {
			await warn(
				response,
				`WARNING: Failed to delete plugin ${ plugin }`
			);
		}
	}
};

/**
 * Download the zip file from a remote location.
 *
 * @param {object} param
 * @param {string} param.url
 * @param {string} param.repository
 * @param {string} param.authorizationToken
 * @param {boolean} param.prerelease
 * @param {string} param.downloadDir
 *
 * @param {string} url The URL where the zip file is located. Takes precedence over `repository`.
 * @param {string} repository The repository owner and name. For example: `woocommerce/woocommerce`. Ignored when `url` was given.
 * @param {string} authorizationToken Authorization token used to authenticate with the GitHub API if required.
 * @param {boolean} prerelease Flag on whether to get a prelease or not. Default `false`.
 * @param {string} downloadDir Relative path to the download directory. Non-existing folders will be auto-created. Defaults to `tmp` under current working directory.
 *
 * @return {string} Absolute path to the downloaded zip.
 */
export const downloadZip = async ( {
	url,
	repository,
	authorizationToken,
	prerelease = false,
	downloadDir = 'tmp',
} ) => {
	let zipFilename = path.basename( url || repository );
	zipFilename = zipFilename.endsWith( '.zip' )
		? zipFilename
		: zipFilename.concat( '.zip' );
	const zipFilePath = path.resolve( downloadDir, zipFilename );

	let response;

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
	const options = {
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

	response = await axios( options ).catch( ( error ) => {
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
 * Get the download URL of the latest release zip for a plugin using GitHub API.
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
	let release;

	const requesturl = prerelease
		? `https://api.github.com/repos/${ repository }/releases?per_page=${ perPage }`
		: `https://api.github.com/repos/${ repository }/releases/latest`;

	const options = {
		method: 'get',
		url: requesturl,
		headers: {
			Authorization: authorizationToken
				? `token ${ authorizationToken }`
				: '',
		},
	};

	// Get the first prerelease, or the latest release.
	let response;
	try {
		response = await axios( options );
	} catch ( error ) {
		let errorMessage =
			'Something went wrong when downloading the plugin.\n';

		if ( error.response ) {
			// The request was made and the server responded with a status code
			// that falls out of the range of 2xx
			errorMessage = errorMessage.concat(
				`Response status: ${ error.response.status } ${ error.response.statusText }`,
				'\n',
				`Response body:`,
				'\n',
				JSON.stringify( error.response.data, null, 2 ),
				'\n'
			);
		} else if ( error.request ) {
			// The request was made but no response was received
			// `error.request` is an instance of XMLHttpRequest in the browser and an instance of
			// http.ClientRequest in node.js
			errorMessage = errorMessage.concat(
				JSON.stringify( error.request, null, 2 ),
				'\n'
			);
		} else {
			// Something happened in setting up the request that triggered an Error
			errorMessage = errorMessage.concat( error.toJSON(), '\n' );
		}

		throw new Error( errorMessage );
	}

	release = prerelease
		? response.data.find( ( { prerelease } ) => prerelease )
		: response.data;

	// If response contains assets, return URL of first asset.
	// Otherwise, return the github.com URL from the tag name.
	const { assets } = release;
	if ( assets && assets.length ) {
		return assets[ 0 ].url;
	} else {
		const tagName = release.tag_name;
		return `https://github.com/${ repository }/archive/${ tagName }.zip`;
	}
};

/**
 * Install a plugin using WP CLI within a WP ENV environment.
 * This is a workaround to the "The uploaded file exceeds the upload_max_filesize directive in php.ini" error encountered when uploading a plugin to the local WP Env E2E environment through the UI.
 *
 * @see https://github.com/WordPress/gutenberg/issues/29430
 *
 * @param {string} pluginPath
 */
export const installPluginThruWpCli = async ( pluginPath ) => {
	const runWpCliCommand = async ( command ) => {
		const { stdout, stderr } = await execAsync(
			`pnpm exec wp-env run tests-cli "${ command }"`
		);

		console.log( stdout );
		console.error( stderr );
	};

	const wpEnvPluginPath = pluginPath.replace(
		/.*\/plugins\/woocommerce/,
		'wp-content/plugins/woocommerce'
	);

	await runWpCliCommand( `ls  ${ wpEnvPluginPath }` );

	await runWpCliCommand(
		`wp plugin install --activate --force ${ wpEnvPluginPath }`
	);

	await runWpCliCommand( `wp plugin list` );
};
