const { spawnSync } = require( 'node:child_process' );
const { mkdtempSync, readFileSync, rmSync } = require( 'node:fs' );
const { tmpdir } = require( 'node:os' );
const path = require( 'node:path' );

const { collectProjectFiles } = require( './duration-sharding' );

const BLOCKS_PROJECT = 'blocks-chromium';
const PLAYWRIGHT_BASE_ARGUMENTS = [
	'test',
	'--config=tests/e2e/playwright.config.ts',
	`--project=${ BLOCKS_PROJECT }`,
];

function discoverBlocksFiles( spawn = spawnSync, testListPath ) {
	const temporaryDirectory = mkdtempSync(
		path.join( tmpdir(), 'wc-blocks-discovery-' )
	);

	try {
		const reportPath = path.join(
			temporaryDirectory,
			'playwright-report.json'
		);
		const result = spawn(
			process.execPath,
			[
				require.resolve( '@playwright/test/cli' ),
				...PLAYWRIGHT_BASE_ARGUMENTS,
				...( testListPath ? [ `--test-list=${ testListPath }` ] : [] ),
				'--list',
				'--reporter=json',
			],
			{
				stdio: [ 'inherit', 'ignore', 'inherit' ],
				env: {
					...process.env,
					PLAYWRIGHT_JSON_OUTPUT_FILE: reportPath,
				},
			}
		);
		if ( result.error ) {
			throw result.error;
		}
		if ( result.signal ) {
			throw new Error(
				`Playwright terminated with signal ${ result.signal }`
			);
		}
		if ( result.status !== 0 ) {
			return { status: result.status ?? 1, files: [] };
		}

		let report;
		try {
			report = JSON.parse( readFileSync( reportPath, 'utf8' ) );
		} catch ( error ) {
			throw new Error( 'Unable to parse Playwright test inventory', {
				cause: error,
			} );
		}

		const files = collectProjectFiles( report, BLOCKS_PROJECT );
		if ( files.length === 0 ) {
			throw new Error(
				`No tests found for Playwright project ${ BLOCKS_PROJECT }`
			);
		}
		return { status: 0, files };
	} finally {
		rmSync( temporaryDirectory, { recursive: true, force: true } );
	}
}

module.exports = {
	BLOCKS_PROJECT,
	discoverBlocksFiles,
	PLAYWRIGHT_BASE_ARGUMENTS,
};
