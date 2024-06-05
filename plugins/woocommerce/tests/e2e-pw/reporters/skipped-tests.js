const {
	Reporter,
	TestCase,
	TestResult,
} = require( '@playwright/test/reporter' );

class SkippedReporter {
	constructor() {
		this.skippedTests = [];
	}

	onTestEnd( testCase, testResult ) {
		if ( testResult.status === 'skipped' ) {
			this.skippedTests.push( testCase.title );
		}
	}

	onEnd() {
		const skippedTestsMessage = this.skippedTests.join( ', ' );
		console.log( 'Skipped tests', this.skippedTests );

		if ( this.skippedTests.length > 0 ) {
			// Output a GitHub Actions annotation
			console.log(
				`::warning title=Skipped Tests::${ skippedTestsMessage }`
			);
		}
	}
}

module.exports = SkippedReporter;
