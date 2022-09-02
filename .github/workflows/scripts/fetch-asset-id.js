/**
 * A script that fetches the asset id of a given release and sets it as the output for the step that calls it
 */
const https = require('https');

const options = {
	hostname: 'api.github.com',
	port: 443,
	path: `/repos/${process.env.REPO}/releases/${process.env.RELEASE_ID}/assets`,
	method: 'GET',
	headers: {
		'Content-Type': 'application/json',
		'Authorization': `Bearer ${ process.env.GITHUB_TOKEN }`,
		'User-Agent': 'WooCommerce Smoke Build'
	},
};

/**
 *
 * @returns {Promise}
 */
const fetchAssetId = () => {
	return new Promise( ( resolve, reject ) => {
		const request = https.get( options, ( response ) => {
			response.setEncoding('utf8');

			let responseBody = '';

			response.on( 'data', ( chunk ) => {
				responseBody += chunk;
			} );

			response.on( 'end', () => {
				const assets = JSON.parse( responseBody );
				// use the most recently uploaded asset
				resolve( assets[ assets.length - 1 ].id );
			} );
		} );

		request.on('error', ( error ) => {
			reject( error );
		} );

		request.end();

	} );
}

module.exports = async ( { github, context, core } ) => {
	const id = await fetchAssetId();

	// set asset_id as the output
	core.setOutput( 'asset_id', id );
}
