const { site } = require( '../e2e-pw/utils' );

module.exports = async ( config ) => {
	// If BASE_URL is configured, we can assume we're on CI
	if ( process.env.API_BASE_URL ) {
		await site.reset( process.env.USER_KEY, process.env.USER_SECRET );
	}
};
