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
			console.log(
				`::error title=Skipped Tests::The following tests were skipped:`
			);
			this.skippedTests.forEach( ( test ) => {
				console.log( `::error title=Skipped Test::${ test }` );
			} );
		}
	}
}

module.exports = SkippedReporter;
