/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify( exec );

/**
 * GitHub API context for version checking.
 */
interface GitHubContext {
	request: ( url: string ) => Promise< { data: Record< string, string > } >;
}

/**
 * GitHub Actions core context for setting outputs.
 */
interface CoreContext {
	setOutput: ( name: string, value: string ) => void;
}

/**
 * Parameters for getVersionWPLatestMinusOne function.
 */
interface VersionCheckParams {
	core: CoreContext;
	github: GitHubContext;
}

/**
 * Get the WordPress version that is one major version behind the latest.
 *
 * @param params        - Parameters object
 * @param params.core   - GitHub Actions core context
 * @param params.github - GitHub API context
 */
export const getVersionWPLatestMinusOne = async ( {
	core,
	github,
}: VersionCheckParams ): Promise< void > => {
	const URL_WP_STABLE_VERSION_CHECK =
		'https://api.wordpress.org/core/stable-check/1.0/';

	const response = await github.request( URL_WP_STABLE_VERSION_CHECK );

	const body: Record< string, string > = response.data;
	const allVersions: string[] = Object.keys( body );
	const previousStableVersions: string[] = allVersions
		.filter( ( version: string ) => body[ version ] === 'outdated' )
		.sort()
		.reverse();
	const latestVersion = allVersions.find(
		( version: string ) => body[ version ] === 'latest'
	);
	const latestMajorAndMinorNumbers =
		latestVersion?.match( /^\d+.\d+/ )?.[ 0 ] ?? '';

	const latestMinus1 = previousStableVersions.find(
		( version: string ) =>
			! version.startsWith( latestMajorAndMinorNumbers )
	);

	core.setOutput( 'version', latestMinus1 ?? '' );
};

/**
 * Get the installed WordPress version from the test environment.
 *
 * @return Promise resolving to the WordPress version as a float
 */
export const getInstalledWordPressVersion = async (): Promise< number > => {
	try {
		const { stdout } = await execAsync(
			`pnpm exec wp-env run tests-cli -- wp core version`
		);

		return Number.parseFloat( stdout.trim() );
	} catch ( error ) {
		const err = error as Error;
		throw new Error( `Error getting WordPress version: ${ err.message }` );
	}
};
