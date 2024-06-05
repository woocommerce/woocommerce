require( '@playwright/test/reporter' );

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
		if ( this.skippedTests.length > 0 ) {
			// Output a GitHub Actions annotation
			console.log(
				`::error title=Skipped Tests::${ skippedTestsMessage }`
			);
		}
	}
}

module.exports = SkippedReporter;
