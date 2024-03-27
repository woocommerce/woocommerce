const getVersionWPLatestMinusOne = async ( { core, github } ) => {
	const URL_WP_STABLE_VERSION_CHECK =
		'https://api.wordpress.org/core/stable-check/1.0/';

	const response = await github.request( URL_WP_STABLE_VERSION_CHECK );

	const body = response.data;
	const allVersions = Object.keys( body );
	const previousStableVersions = allVersions
		.filter( ( version ) => body[ version ] === 'outdated' )
		.sort()
		.reverse();
	const latestMajorAndMinorNumbers = allVersions
		.find( ( version ) => body[ version ] === 'latest' )
		.match( /^\d+.\d+/ )[ 0 ];

	const latestMinus1 = previousStableVersions.find(
		( version ) => ! version.startsWith( latestMajorAndMinorNumbers )
	);

	core.setOutput( 'version', latestMinus1 );
};

const getVersionWPLatestBetaRC = async ( { core, github } ) => {
	const URL_WP_BETA_RC_VERSION_CHECK =
		'https://api.wordpress.org/core/version-check/1.7/?channel=beta';

	const response = await github.request( URL_WP_BETA_RC_VERSION_CHECK );

	const body = response.data;
	const developmentOffer = body.offers.find(
		( offer ) => offer.response === 'development'
	);
	const currentDevelopmentURL = developmentOffer.download;

	core.setOutput( 'url', currentDevelopmentURL );
};

module.exports = { getVersionWPLatestMinusOne, getVersionWPLatestBetaRC };
