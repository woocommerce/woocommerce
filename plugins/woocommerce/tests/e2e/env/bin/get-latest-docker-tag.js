#!/usr/bin/env node

const https = require( 'https' );
const semver = require( 'semver' );
const getLatestMinusVersion = require( './get-previous-version' );

/**
 * Fetches the latest tag from a page using the Docker HTTP api.
 *
 * @param {string} image The image we're looking at the tags for.
 * @param {string} nameSearch The string we're searching for in the name.
 * @param {number} page The page we want to request.
 * @return {Promise} A promise resolving to the JSON content for the page.
 */
async function fetchLatestTagFromPage( image, nameSearch, page ) {
	return new Promise(
		( resolve, reject ) => {
			const queryParams = new URLSearchParams( {
				page_size: 100,
				ordering: 'last_updated',
				page: page,
				name: nameSearch
			} );
			const options = {
				hostname: 'hub.docker.com',
				port: 443,
				path: '/v2/repositories/library/' + image + '/tags?' + queryParams.toString(),
				method: 'GET',
			};

			const req = https.request(options, (res) => {
				let data = '';

				res.on( 'data', d => { data += d; } );
				res.on( 'end', () => {
					data = JSON.parse( data );
					if ( ! data.count ) {
						reject( "No image '" + image + '" found' );
					} else {
						// Implement a 12 hour delay on pulling newly released docker tags.
						const delayMilliseconds = 12 * 3600 * 1000;
						const currentTime = Date.now();
						let latestTag = null;
						let lastUpdated = null;
						for ( let tag of data.results ) {
							tag.semver = tag.name.match( /^\d+\.\d+(.\d+)*$/ );
							if ( ! tag.semver ) {
								continue;
							}

							lastUpdated = Date.parse( tag.last_updated );
							if ( currentTime - lastUpdated < delayMilliseconds ) {
								continue;
							}

							tag.semver = semver.coerce( tag.semver[0] );
							if ( ! latestTag || semver.gt( tag.semver, latestTag.semver ) ) {
								latestTag = tag;
							}
						}

						resolve( {
							latestTag,
							isLastPage: ! data.next,
						} );
					}
				} )
			});
			req.end();
		}
	)
}

/**
 * Finds the latest version of the image that meets our name criteria.
 *
 * @param {string} image The image we're searching for.
 * @param {string} nameSearch The name we're searching for.
 * @return {Promise} A promise resolving to the latest version of a package.
 */
function findLatestVersion( image, nameSearch ) {
	let page = 1;
	let earliestUpdated = null;
	let latestVersion = null;

	// Repeat the requests until we've read as many pages as necessary.
	const paginationFn = function ( result ) {
		if ( result.latestTag ) {
			// We can save on unnecessarily loading every page by short-circuiting when
			// the number of days between the first recorded version and the
			// one from this page becomes excessive.
			const lastUpdate = Date.parse( result.latestTag.last_updated );
			if ( ! earliestUpdated ) {
				earliestUpdated = lastUpdate;
			} else {
				const daysSinceEarliestUpdate = ( earliestUpdated - lastUpdate ) / ( 1000 * 3600 * 24 );
				if ( daysSinceEarliestUpdate > 15 ) {
					result.isLastPage = true;
				}
			}

			if ( ! latestVersion || semver.gt( result.latestTag.semver, latestVersion ) ) {
				latestVersion = result.latestTag.semver;
			}
		}

		if ( ! result.isLastPage ) {
			return fetchLatestTagFromPage( image, nameSearch, ++page ).then( paginationFn );
		}

		if ( image === 'wordpress' && process.env.LATEST_WP_VERSION_MINUS ) {
			return getLatestMinusVersion( latestVersion.toString(), process.env.LATEST_WP_VERSION_MINUS );
		}

		return latestVersion.toString();
	};

	return fetchLatestTagFromPage( image, nameSearch, page ).then( paginationFn );
}

const image = process.argv[2];
const nameSearch = process.argv[3];
if ( ! image || ! nameSearch ) {
	console.error( 'Usage: get-latest-docker-tag.js <image> <name-search>' );
	process.exit( 1 );
}

findLatestVersion( image, nameSearch ).then(
	( latestVersion ) => {
		console.info( latestVersion );
		process.exit( 0 );
	},
	( error ) => {
		console.error( error );
		process.exit( 1 );
	}
)
