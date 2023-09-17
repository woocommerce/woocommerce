const { admin } = require( '../test-data/data' );
const { encodeCredentials } = require( './plugin-utils' );

const set_feature_flag = async ( request, baseURL, flagName, enable ) => {
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

	await apiContext.post( '/wp-json/e2e-feature-flags/update', {
		failOnStatusCode: true,
		data: { [ flagName ]: enable },
	} );
};

const reset_feature_flags = async ( request, baseURL ) => {
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

	await apiContext.get( '/wp-json/e2e-feature-flags/reset', {
		failOnStatusCode: true,
	} );
};

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
	reset_feature_flags,
};
