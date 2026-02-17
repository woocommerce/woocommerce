/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify( exec );

const getVersionWPLatestMinusOne = async ( {
	core,
	github,
}: {
	core: { setOutput: ( name: string, value: string ) => void };
	github: {
		request: (
			url: string
		) => Promise< { data: Record< string, string > } >;
	};
} ) => {
	const URL_WP_STABLE_VERSION_CHECK =
		'https://api.wordpress.org/core/stable-check/1.0/';

	const response = await github.request( URL_WP_STABLE_VERSION_CHECK );

	const body = response.data;
	const allVersions = Object.keys( body );
	const previousStableVersions = allVersions
		.filter( ( version ) => body[ version ] === 'outdated' )
		.sort()
		.reverse();
	const latestVersion = allVersions.find(
		( version ) => body[ version ] === 'latest'
	);
	if ( ! latestVersion ) {
		throw new Error( 'No latest WordPress version found in API response' );
	}
	const match = latestVersion.match( /^\d+\.\d+/ );
	if ( ! match ) {
		throw new Error( `Unexpected version format: ${ latestVersion }` );
	}
	const latestMajorAndMinorNumbers = match[ 0 ];

	const latestMinus1 = previousStableVersions.find(
		( version ) => ! version.startsWith( latestMajorAndMinorNumbers )
	);

	if ( ! latestMinus1 ) {
		throw new Error(
			'Unable to find the previous stable WordPress version'
		);
	}

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

export { getVersionWPLatestMinusOne, getInstalledWordPressVersion };
