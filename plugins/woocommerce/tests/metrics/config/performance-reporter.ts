/* eslint-disable @typescript-eslint/no-unused-vars */
/**
 * External dependencies
 */
import path from 'path';
import { writeFileSync } from 'fs';
import type {
	Reporter,
	FullResult,
	TestCase,
	TestResult,
} from '@playwright/test/reporter';
/* eslint-enable @typescript-eslint/no-unused-vars */

export type WPPerformanceResults = Record< string, number >;

class PerformanceReporter implements Reporter {
	private results: Record< string, WPPerformanceResults >;

	constructor() {
		this.results = {};
	}

	onTestEnd( test: TestCase, result: TestResult ): void {
		for ( const attachment of result.attachments ) {
			if ( attachment.name !== 'results' ) {
				continue;
			}

			if ( ! attachment.body ) {
				throw new Error( 'Empty results attachment' );
			}

			const testSuite = path.basename( test.location.file, '.spec.js' );
			const resultsId = process.env.RESULTS_ID || testSuite;
			const resultsPath = process.env.WP_ARTIFACTS_PATH as string;
			const resultsBody = attachment.body.toString();
			const resultsObj = JSON.parse( resultsBody );
			const firstKey = Object.keys( resultsObj )[ 0 ];
			const results = resultsObj[ firstKey ];

			// Save curated results to file.
			writeFileSync(
				path.join(
					resultsPath,
					`${ resultsId }.performance-results.json`
				),
				JSON.stringify( results, null, 2 )
			);

			this.results[ testSuite ] = results;
		}
	}

	onEnd( result: FullResult ): void {
		if ( result.status !== 'passed' ) {
			return;
		}

		if ( process.env.CI ) {
			return;
		}

		// Print the results.
		for ( const [ testSuite, results ] of Object.entries( this.results ) ) {
			const printableResults: Record< string, { value: string } > = {};

			for ( const [ key, value ] of Object.entries( results ) ) {
				printableResults[ key ] = { value: `${ value } ms` };
			}

			// eslint-disable-next-line no-console
			console.log( `\n${ testSuite }\n` );
			// eslint-disable-next-line no-console
			console.table( printableResults );
		}
	}
}

export default PerformanceReporter;
