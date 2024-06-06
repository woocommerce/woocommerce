require( '@playwright/test/reporter' );

class SkippedReporter {
	constructor() {
		this.skippedTests = [];
	}

	onTestEnd( testCase, testResult ) {
		if ( testResult.status === 'skipped' ) {
			this.skippedTests.push(
				`- ${ testCase.title } in ${ testCase.location.file }:${ testCase.location.line }`
			);
		}
	}

	onEnd() {
		if ( this.skippedTests.length > 0 ) {
			const skippedTestsMessage = this.skippedTests.join( '%0A' );
			// Output a GitHub Actions annotation with line breaks
			console.log(
				`::error title=Skipped Tests::${ skippedTestsMessage }`
			);
		}
	}
}

module.exports = SkippedReporter;
