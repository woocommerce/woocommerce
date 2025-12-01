require( '@playwright/test/reporter' );

class SkippedReporter {
	constructor() {
		this.skippedTests = [];
	}

	onTestEnd( testCase, testResult ) {
		if (
			testResult.status === 'skipped' &&
			! testCase.location.file.includes( 'fixtures' )
		) {
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
				`::warning title=${ this.skippedTests.length } tests were skipped::%0ASkipped tests:%0A${ skippedTestsMessage }`
			);
		}
	}
}

module.exports = SkippedReporter;
