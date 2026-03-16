/**
 * External dependencies
 */
import type { TestCase, TestResult } from '@playwright/test/reporter';

class SkippedReporter {
	skippedTests: string[];

	constructor() {
		this.skippedTests = [];
	}

	onTestEnd( testCase: TestCase, testResult: TestResult ) {
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

export default SkippedReporter;
