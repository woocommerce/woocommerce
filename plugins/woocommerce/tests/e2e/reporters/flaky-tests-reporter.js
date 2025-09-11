/**
 * A **flaky** test is defined as a test which passed after auto-retrying.
 * - By default, all tests run once if they pass.
 * - If a test fails, it will automatically re-run at most 2 times.
 * - If it passes after retrying (below 2 times), then it's marked as **flaky**
 *   but displayed as **passed** in the original test suite.
 * - If it failed all times, then it's a **failed** test.
 */
/**
 * External dependencies
 */
const fs = require( 'fs' );
require( '@playwright/test/reporter' );

// Remove "steps" to prevent stringify circular structure.
function formatTestResult( testResult ) {
	const result = { ...testResult, steps: undefined };
	delete result.steps;
	return result;
}

class FlakyTestsReporter {
	constructor( options ) {
		this.outputFolder = options.outputFolder;
		this.failingTestCaseResults = new Map();
	}

	onBegin() {
		try {
			fs.mkdirSync( this.outputFolder, {
				recursive: true,
			} );
		} catch ( err ) {
			if ( err.code === 'EEXIST' ) {
				// Ignore the error if the directory already exists.
			} else {
				throw err;
			}
		}
	}

	onTestEnd( test, result ) {
		const testPath = test.location.file;
		const testTitle = test.title;

		switch ( test.outcome() ) {
			case 'unexpected': {
				if ( ! this.failingTestCaseResults.has( testTitle ) ) {
					this.failingTestCaseResults.set( testTitle, [] );
				}
				this.failingTestCaseResults
					.get( testTitle )
					.push( formatTestResult( result ) );
				break;
			}
			case 'flaky': {
				const safeFileName = testTitle.replace( /[^a-z0-9]/gi, '_' );
				fs.writeFileSync(
					`${ this.outputFolder }/${ safeFileName }.json`,
					JSON.stringify( {
						version: 1,
						runner: '@playwright/test',
						title: testTitle,
						path: testPath,
						results: this.failingTestCaseResults.get( testTitle ),
					} ),
					'utf-8'
				);
				break;
			}
			default:
				break;
		}
	}
}

module.exports = FlakyTestsReporter;
