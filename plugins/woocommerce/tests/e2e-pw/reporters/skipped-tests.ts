import type {
	Reporter,
	TestCase,
	TestResult,
	FullResult,
} from '@playwright/test/reporter';

class SkippedReporter implements Reporter {
	private skippedTests: string[] = [];

	onTestEnd( testCase: TestCase, testResult: TestResult ): void {
		if (
			testResult.status === 'skipped' &&
			! testCase.location.file.includes( 'fixtures' )
		) {
			this.skippedTests.push(
				`- ${ testCase.title } in ${ testCase.location.file }:${ testCase.location.line }`
			);
		}
	}

	onEnd( _result: FullResult ): void {
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
