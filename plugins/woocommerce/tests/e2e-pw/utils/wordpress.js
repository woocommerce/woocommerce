const { promisify } = require( 'util' );
const execAsync = promisify( require( 'child_process' ).exec );

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

const getInstalledWordPressVersion = async () => {
	try {
		const { stdout } = await execAsync(
			`pnpm exec wp-env run tests-cli -- wp core version`
		);

		return Number.parseFloat( stdout.trim() );
	} catch ( error ) {
		throw new Error(
			`Error getting WordPress version: ${ error.message }`
		);
	}
};

module.exports = { getVersionWPLatestMinusOne, getInstalledWordPressVersion };
