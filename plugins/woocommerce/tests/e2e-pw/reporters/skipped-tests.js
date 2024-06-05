const {
	Reporter,
	TestCase,
	TestResult,
} = require( '@playwright/test/reporter' );

class SkippedReporter {
	constructor() {
		this.skippedTests = [];
	}

	onTestEnd( TestCase, TestResult ) {
		if ( TestResult.status === 'skipped' ) {
			this.skippedTests.push( TestCase.title );
		}
	}

	onEnd() {
		const skippedTestsMessage = this.skippedTests.join( ', ' );
		if ( this.skippedTests.length > 0 ) {
			// Output a GitHub Actions annotation
			console.log(
				`::warning title=Skipped Tests::${ skippedTestsMessage }`
			);
		}
	}
}

module.exports = SkippedReporter;
