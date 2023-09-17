const { admin } = require( '../test-data/data' );
const { encodeCredentials } = require( './plugin-utils' );

const set_feature_flag = async ( request, baseURL, flagName, enable ) => {
	// const apiContext = await request.newContext( {
	// 	baseURL,
	// 	extraHTTPHeaders: {
	// 		Authorization: `Basic ${ encodeCredentials( username, password ) }`,
	// 		cookie: '',
	// 	},
	// } );

	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				'admin',
				'password'
			) }`,
			cookie: '',
		},
	} );

	const res = await apiContext.post( '/wp-json/e2e-feature-flags/update', {
		failOnStatusCode: true,
		data: { [ flagName ]: enable },
	} );

	console.error( res.body );
	// const url = '/wp-json/e2e-feature-flags/update';
	// const params = { _locale: 'user' };
	// const data = { [ flagName ]: enable ? 'yes' : 'no' };

	// await request.post( url, {
	// 	data,
	// 	params,
	// 	headers,
	// } );
};

// const reset_feature_flags = async ( request, username, password ) => {
// 	const apiContext = await request.newContext( {
// 		baseURL,
// 		extraHTTPHeaders: {
// 			Authorization: `Basic ${ encodeCredentials( username, password ) }`,
// 			cookie: '',
// 		},
// 	} );
// 	const listPluginsResponse = await apiContext.get(
// 		`/wp-json/wp/v2/plugins`,
// 		{
// 			failOnStatusCode: true,
// 		}
// 	);
// 	const url = '/wp-json/e2e-feature-flags/reset';
// 	const params = { _locale: 'user' };

// 	await request.post( url, {
// 		params,
// 		headers,
// 	} );
// };

function is_enabled( feature ) {
	const phase = process.env.WC_ADMIN_PHASE;
	let config = 'development.json';
	if ( ! [ 'core', 'developer' ].includes( phase ) ) {
		config = 'core.json';
	}

	const features =
		require( `../../../client/admin/config/${ config }` ).features;
	return features[ feature ] && features[ feature ] === true;
}

module.exports = {
	is_enabled,
	set_feature_flag,
	// reset_feature_flags,
};
