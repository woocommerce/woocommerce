require( '@playwright/test/reporter' );

class SkippedReporter {
	constructor() {
		this.skippedTests = [];
	}

	onTestEnd( testCase, testResult ) {
		if ( testResult.status === 'skipped' ) {
			this.skippedTests.push(
				`- ${ testCase.title } in ${ testCase.file } on line ${ testCase.line }`
			);
		}
	}

	onEnd() {
		const skippedTestsMessage = this.skippedTests.join( '%0A' );
		if ( this.skippedTests.length > 0 ) {
			// Output a GitHub Actions annotation
			console.log(
				`::error title=Skipped Tests::${ skippedTestsMessage }`
			);
		}
	}
}

module.exports = SkippedReporter;
