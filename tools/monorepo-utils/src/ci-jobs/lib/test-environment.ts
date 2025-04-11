/**
 * External dependencies
 */
import https from 'node:http';

/**
 * Internal dependencies
 */
import { TestEnvConfigVars } from './config';

/**
 * The response for the WordPress.org stability check API.
 */
interface StableCheckResponse {
	[ version: string ]: 'latest' | 'outdated' | 'insecure';
}

interface WordPressOffer {
	version: string;
	downloadUrl: string;
}

/**
 * Gets all the available WordPress versions and their associated stability.
 *
 * @return {Promise.<Object>} The response from the WordPress.org API.
 */
function getWordPressVersions(): Promise< StableCheckResponse > {
	return new Promise< StableCheckResponse >( ( resolve, reject ) => {
		// We're going to use the WordPress.org API to get information about available versions of WordPress.
		const request = https.get(
			'http://api.wordpress.org/core/stable-check/1.0/',
			( response ) => {
				// Listen for the response data.
				let responseData = '';
				response.on( 'data', ( chunk ) => {
					responseData += chunk;
				} );

				// Once we have the entire response we can process it.
				response.on( 'end', () =>
					resolve( JSON.parse( responseData ) )
				);
			}
		);

		request.on( 'error', ( error ) => {
			reject( error );
		} );
	} );
}

async function getStableVersion() {
	const allVersions = await getWordPressVersions();
	return Object.keys( allVersions ).find(
		( key ) => allVersions[ key ] === 'latest'
	);
}

/**
 * Uses the WordPress API to get the download URL to the latest version of an X.X version line. This
 * also accepts "latest-X" to get an offset from the latest version of WordPress.
 *
 * @param {string} wpVersion The version of WordPress to look for.
 * @return {Promise.<string>} The precise WP version download URL.
 */
async function getPreciseWPVersion(
	wpVersion: string
): Promise< WordPressOffer > {
	const allVersions = await getWordPressVersions();

	// If we're requesting a "latest" offset then we need to figure out what version line we're offsetting from.
	const latestSubMatch = wpVersion.match( /^latest(?:-([0-9]+))?$/i );
	if ( latestSubMatch ) {
		for ( const version in allVersions ) {
			if ( allVersions[ version ] !== 'latest' ) {
				continue;
			}

			// We don't care about the patch version because we will
			// the latest version from the version line below.
			const versionParts = version.match( /^([0-9]+)\.([0-9]+)/ );

			// We're going to subtract the offset to figure out the right version.
			let offset = latestSubMatch[ 1 ]
				? parseInt( latestSubMatch[ 1 ], 10 )
				: 0;
			let majorVersion = parseInt( versionParts[ 1 ], 10 );
			let minorVersion = parseInt( versionParts[ 2 ], 10 );
			while ( offset > 0 ) {
				minorVersion--;
				if ( minorVersion < 0 ) {
					majorVersion--;
					minorVersion = 9;
				}
				offset--;
			}

			// Set the version that we found in the offset.
			wpVersion = majorVersion + '.' + minorVersion;
		}
	}

	// Scan through all the versions to find the latest version in the version line.
	let latestVersion = null;
	let latestPatch = -1;
	for ( const v in allVersions ) {
		// Parse the version so we can make sure we're looking for the latest.
		const matches = v.match( /([0-9]+)\.([0-9]+)(?:\.([0-9]+))?/ );

		// We only care about the correct minor version.
		const minor = `${ matches[ 1 ] }.${ matches[ 2 ] }`;
		if ( minor !== wpVersion ) {
			continue;
		}

		// Track the latest version in the line.
		const patch =
			matches[ 3 ] === undefined ? 0 : parseInt( matches[ 3 ], 10 );

		if ( patch > latestPatch ) {
			latestPatch = patch;
			latestVersion = v;
		}
	}

	if ( ! latestVersion ) {
		throw new Error(
			`Unable to find latest version for version line ${ wpVersion }.`
		);
	}

	return {
		version: latestVersion,
		downloadUrl: `https://wordpress.org/wordpress-${ latestVersion }.zip`,
	};
}

async function getWordPressOffers(
	wpVersion: string,
	channel: string
): Promise< any > {
	return new Promise< any >( ( resolve, reject ) => {
		const url = new URL(
			'http://api.wordpress.org/core/version-check/1.7/'
		);
		const params = new URLSearchParams();

		if ( channel ) {
			params.append( 'channel', channel );
		}

		if ( wpVersion ) {
			params.append( 'version', wpVersion );
		}

		url.search = params.toString();

		const request = https.get( url.toString(), ( response ) => {
			// Listen for the response data.
			let responseData = '';
			response.on( 'data', ( chunk ) => {
				responseData += chunk;
			} );

			// Once we have the entire response we can process it.
			response.on( 'end', () => resolve( JSON.parse( responseData ) ) );
		} );

		request.on( 'error', ( error ) => {
			reject( error );
		} );
	} );
}

/**
 * Uses the WordPress API to get the version number and the download URL to the beta offer, it such offer exists.
 *
 * @param {string} forVersion The current version.
 * @return {Promise.<string>} The precise WP version download URL.
 */
async function getBetaChannelOffer(
	forVersion: string
): Promise< WordPressOffer > {
	const response = await getWordPressOffers( forVersion, 'beta' );
	const targetOffer = response.offers.find(
		( offer: any ) => offer.response === 'development'
	);

	if ( targetOffer ) {
		return {
			version: targetOffer.version,
			downloadUrl: targetOffer.download,
		};
	}
	return null;
}

/**
 * Parses a display-friendly WordPress version and returns a link to download the given version.
 *
 * @param {string} wpVersion A display-friendly WordPress version. Supports ("master", "trunk", "nightly", "latest", "latest-X", "X.X" for version lines, and "X.X.X" for specific versions)
 * @return {Promise.<string>} A link to download the given version of WordPress.
 */
async function parseWPVersion( wpVersion: string ): Promise< WordPressOffer > {
	// Start with versions we can infer immediately.
	switch ( wpVersion ) {
		case 'master':
		case 'trunk': {
			return {
				version: 'master',
				downloadUrl: 'WordPress/WordPress#master',
			};
		}

		case 'nightly': {
			return {
				version: 'nightly',
				downloadUrl:
					'https://wordpress.org/nightly-builds/wordpress-latest.zip',
			};
		}

		case 'latest': {
			return {
				version: 'latest',
				downloadUrl: 'https://wordpress.org/latest.zip',
			};
		}

		// Use 'prerelease' as a broader terms to catch both beta and RC.
		// The version-check API returns both beta and RC offers for channel=beta under the 'development' response.
		case 'prerelease': {
			const stableVersion = await getStableVersion();
			return getBetaChannelOffer( stableVersion );
		}
	}

	// We can also infer X.X.X versions immediately.
	const parsedVersion = wpVersion.match( /^([0-9]+)\.([0-9]+)\.([0-9]+)$/ );
	if ( parsedVersion ) {
		// Note that X.X.0 versions use an X.X download URL.
		let urlVersion = `${ parsedVersion[ 1 ] }.${ parsedVersion[ 2 ] }`;
		if ( parsedVersion[ 3 ] !== '0' ) {
			urlVersion += `.${ parsedVersion[ 3 ] }`;
		}

		return {
			version: urlVersion,
			downloadUrl: `https://wordpress.org/wordpress-${ urlVersion }.zip`,
		};
	}

	// Since we haven't found a URL yet, we're going to use the WordPress.org API to try and infer one.
	return await getPreciseWPVersion( wpVersion );
}

/**
 * The environment variables that should be set for the test environment.
 */
export interface TestEnvVars {
	WP_VERSION?: string;
	WP_ENV_CORE?: string;
	WP_ENV_PHP_VERSION?: string;
}

/**
 * Parses the test environment's configuration and returns any environment variables that
 * should be set.
 *
 * @param {Object} config The test environment configuration.
 * @return {Promise.<Object>} The environment variables for the test environment.
 */
export async function parseTestEnvConfig(
	config: TestEnvConfigVars
): Promise< TestEnvVars > {
	const envVars: TestEnvVars = {};

	// Convert `wp-env` configuration options to environment variables.
	if ( config.wpVersion ) {
		try {
			const wpVersion = await parseWPVersion( config.wpVersion );
			envVars.WP_VERSION = wpVersion.version;
			envVars.WP_ENV_CORE = wpVersion.downloadUrl;
		} catch ( error ) {
			// Pre-release offers like beta or RC are not always available, and we should not throw if that's the case.
			// We should only throw if the version is one that should always be available.
			if (
				! [ 'beta', 'rc', 'prerelease', 'pre-release' ].includes(
					config.wpVersion
				)
			) {
				throw new Error(
					`Failed to parse WP version: ${ error.message }.`
				);
			}
		}
	}
	if ( config.phpVersion ) {
		envVars.WP_ENV_PHP_VERSION = config.phpVersion;
	}

	return envVars;
}
