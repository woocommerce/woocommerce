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
	E2E_GRAND_TOTAL,
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
 * @returns An object containing relevant statistics from the Allure report.
 */
const getAllureSummaryStats = ( summaryJSONPath ) => {
	const summary = require( summaryJSONPath );
	const { statistic, time } = summary;
	const { passed, failed, skipped, broken, unknown, total } = statistic;
	const { duration } = time;

	return {
		passed,
		failed,
		skipped,
		broken,
		unknown,
		total,
		duration,
	};
};

/**
 * Extract the test report statistics (the number of tests that passed, failed, skipped, etc.) from the `test-results.json` file.
 *
 * @returns An object containing relevant statistics from the `test-results.json` file.
 */
const getPuppeteerStats = () => {
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

	return {
		passed,
		failed,
		skipped,
		broken,
		unknown,
		total,
		duration,
	};
};

/**
 * Construct the array to be used for the API table row.
 *
 * @returns Array of API test result stats.
 */
const createAPITableRow = () => {
	const { passed, failed, skipped, broken, unknown, total, duration } =
		getAllureSummaryStats( API_SUMMARY_PATH );
	const durationFormatted = getFormattedDuration( duration );

	return [
		'API Tests',
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
 * Construct the array to be used for the E2E table row.
 *
 * @returns Array of E2E test result stats.
 */
const createE2ETableRow = () => {
	const { passed, failed, skipped, broken, unknown, total, duration } =
		E2E_PLAYWRIGHT
			? getAllureSummaryStats( E2E_PW_SUMMARY_PATH )
			: getPuppeteerStats();
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
};

/**
 * Add a warning when the number of executed Playwright E2E tests were fewer than the total.
 */
const addWarningE2EIncomplete = ( warnings ) => {
	if ( ! E2E_PLAYWRIGHT ) {
		return;
	}

	const { statistic } = require( E2E_PW_SUMMARY_PATH );
	const { total } = statistic;
	const expectedTotal = Number( E2E_GRAND_TOTAL );

	if ( total < expectedTotal ) {
		warnings.push(
			`INCOMPLETE E2E RUN. We have a total of ${ expectedTotal } E2E tests, but only ${ total } were executed. E2E tests in CI will automatically end when they encounter too many failures. This is a fail-fast mechanism to save time on testing a buggy build. Keep the failures to a minimum in order to allow the entire E2E tests to run against this pull request.`
		);
	}
};

/**
 *
 * Add a warning when there are failures and broken tests.
 */
const addWarningFailuresBrokenTests = ( warnings ) => {
	const { failed: apiFailed, broken: apiBroken } =
		getAllureSummaryStats( API_SUMMARY_PATH );
	const { failed: e2eFailed, broken: e2eBroken } = E2E_PLAYWRIGHT
		? getAllureSummaryStats( E2E_PW_SUMMARY_PATH )
		: getPuppeteerStats();

	if ( apiFailed || apiBroken || e2eFailed || e2eBroken ) {
		warnings.push(
			'FAILED/BROKEN TESTS. There were failed and/or broken API and E2E tests. Please fix them first prior to merging this pull request. You may refer to the full API/E2E test reports linked below for debugging.'
		);
	}
};

/**
 * Add warnings to the test summary.
 *
 * @param core The GitHub Actions toolkit core object
 */
const addSummaryWarnings = ( core ) => {
	const warnings = [];

	addWarningE2EIncomplete( warnings );
	addWarningFailuresBrokenTests( warnings );
	if ( warnings.length > 0 ) {
		core.summary
			.addHeading( ':warning: Warning', 3 )
			.addRaw(
				'Please address the following issues prior to merging this pull request:'
			)
			.addList( warnings );
	}
};

/**
 * Create the heading, commit SHA, and test results table.
 *
 * @param core The GitHub Actions toolkit core object
 */
const addSummaryHeadingAndTable = ( core ) => {
	const apiTableRow = createAPITableRow();
	const e2eTableRow = createE2ETableRow();

	core.summary
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
			apiTableRow,
			e2eTableRow,
		] );
};

/**
 * Add the summary footer.
 *
 * @param core The GitHub Actions toolkit core object
 */
const addSummaryFooter = ( core ) => {
	core.summary
		.addSeparator()
		.addRaw( 'To view the full API test report, click ' )
		.addLink(
			'here.',
			`https://woocommerce.github.io/woocommerce-test-reports/pr/${ PR_NUMBER }/api/`
		)
		.addBreak()
		.addRaw( 'To view the full E2E test report, click ' )
		.addLink(
			'here.',
			`https://woocommerce.github.io/woocommerce-test-reports/pr/${ PR_NUMBER }/e2e/`
		)
		.addBreak()
		.addRaw( 'To view all test reports, visit the ' )
		.addLink(
			'WooCommerce Test Reports Dashboard.',
			'https://woocommerce.github.io/woocommerce-test-reports/'
		);
};

/**
 * Generate the contents of the test results summary and post it on the workflow run.
 *
 * @param {*} params Objects passed from the calling GitHub Action workflow.
 * @returns Stringified content of the test results summary.
 */
module.exports = async ( { core } ) => {
	addSummaryHeadingAndTable( core );

	addSummaryWarnings( core );

	addSummaryFooter( core );

	const summary = core.summary.stringify();

	await core.summary.write();

	return summary;
};
