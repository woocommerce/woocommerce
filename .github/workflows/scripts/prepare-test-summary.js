/**
 * Script to generate the test results summary to be posted as a GitHub Job Summary and as a PR comment.
 */
const {
	E2E_PLAYWRIGHT,
	API_SUMMARY_PATH,
	E2E_PW_SUMMARY_PATH,
	E2E_PPTR_SUMMARY_PATH,
	SHA,
	PR_NUMBER,
} = process.env;

/**
 * Convert the given `duration` from milliseconds to a more user-friendly string.
 * For example, if `duration = 323000`, this function would return `5m 23s`.
 *
 * @param {Number} duration Duration in millisecods, as read from either the `summary.json` file in the Allure report, or from the `test-results.json` file from the Jest-Puppeteer report.
 * @returns String in "5m 23s" format.
 */
const getFormattedDuration = ( duration ) => {
	const durationMinutes = Math.floor( duration / 1000 / 60 );
	const durationSeconds = Math.floor( ( duration / 1000 ) % 60 );
	return `${ durationMinutes }m ${ durationSeconds }s`;
};

/**
 * Extract the test report statistics (the number of tests that passed, failed, skipped, etc.) from Allure report's `summary.json` file.
 *
 * @param {string} summaryJSONPath Path to the Allure report's `summary.json` file.
 * @param {string} testHeader The kind of test that generated the Allure report. For example, "E2E Tests".
 * @returns Array containing stringified values of test stats.
 */
const getAllureSummaryStats = ( summaryJSONPath, testHeader ) => {
	const summary = require( summaryJSONPath );
	const { passed, failed, skipped, broken, unknown, total } =
		summary.statistic;
	const { duration } = summary.time;
	const durationFormatted = getFormattedDuration( duration );

	return [
		testHeader,
		passed.toString(),
		failed.toString(),
		broken.toString(),
		skipped.toString(),
		unknown.toString(),
		total.toString(),
		durationFormatted,
	];
};

/**
 * Get API test result stats.
 *
 * @returns Array of API test result stats.
 */
const getAPIStatsArr = () => {
	return getAllureSummaryStats( API_SUMMARY_PATH, 'API Tests' );
};

/**
 * Get E2E test result stats.
 *
 * @returns Array of E2E test result stats.
 */
const getE2EStatsArr = () => {
	if ( E2E_PLAYWRIGHT === 'true' ) {
		return getAllureSummaryStats( E2E_PW_SUMMARY_PATH, 'E2E Tests' );
	} else {
		const summary = require( E2E_PPTR_SUMMARY_PATH );
		const {
			numPassedTests: passed,
			numFailedTests: failed,
			numTotalTests: total,
			numPendingTests: skipped,
			numRuntimeErrorTestSuites: broken,
			numTodoTests: unknown,
			startTime,
			testResults,
		} = summary;
		const endTime = testResults[ testResults.length - 1 ].endTime;
		const duration = endTime - startTime;
		const durationFormatted = getFormattedDuration( duration );

		return [
			'E2E Tests',
			passed.toString(),
			failed.toString(),
			broken.toString(),
			skipped.toString(),
			unknown.toString(),
			total.toString(),
			durationFormatted,
		];
	}
};

/**
 * Generate the contents of the test results summary and post it on the workflow run.
 *
 * @param {*} params Objects passed from the calling GitHub Action workflow.
 * @returns Stringified content of the test results summary.
 */
module.exports = async ( { core } ) => {
	const apiStats = getAPIStatsArr();
	const e2eStats = getE2EStatsArr();

	const contents = core.summary
		.addHeading( 'Test Results Summary' )
		.addRaw( `Commit SHA: ${ SHA }` )
		.addBreak()
		.addBreak()
		.addTable( [
			[
				{ data: 'Test :test_tube:', header: true },
				{ data: 'Passed :white_check_mark:', header: true },
				{ data: 'Failed :rotating_light:', header: true },
				{ data: 'Broken :construction:', header: true },
				{ data: 'Skipped :next_track_button:', header: true },
				{ data: 'Unknown :grey_question:', header: true },
				{ data: 'Total :bar_chart:', header: true },
				{ data: 'Duration :stopwatch:', header: true },
			],
			apiStats,
			e2eStats,
		] )
		.addRaw( 'To view the full API test report, click ' )
		.addLink(
			'here.',
			`https://woocommerce.github.io/woocommerce-test-reports/pr/${ PR_NUMBER }/api/`
		)
		.addBreak()
		.addRaw( 'To view the full E2E test report, click ' )
		.addLink(
			'here.',
			'https://woocommerce.github.io/woocommerce-test-reports'
		)
		.addBreak()
		.addRaw( 'To view all test reports, visit the ' )
		.addLink(
			'WooCommerce Test Reports Dashboard',
			'https://woocommerce.github.io/woocommerce-test-reports/'
		)
		.stringify();

	await core.summary.write();

	return contents;
};
