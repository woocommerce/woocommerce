const { encodeCredentials } = require( './plugin-utils' );
const { admin } = require( '../test-data/data' );

const setFeatureFlag = async ( request, baseURL, flagName, enable ) => {
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				admin.username,
				admin.password
			) }`,
			cookie: '',
		},
	} );

	await apiContext.post( '/wp-json/e2e-feature-flags/update', {
		failOnStatusCode: true,
		data: { [ flagName ]: enable },
	} );
};

const resetFeatureFlags = async ( request, baseURL ) => {
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				admin.username,
				admin.password
			) }`,
			cookie: '',
		},
	} );

	await apiContext.get( '/wp-json/e2e-feature-flags/reset', {
		failOnStatusCode: true,
	} );
};

module.exports = {
	setFeatureFlag,
	resetFeatureFlags,
};
