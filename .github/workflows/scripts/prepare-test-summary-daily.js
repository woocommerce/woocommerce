/**
 * Script to generate the test results summary.
 */
const { API_SUMMARY_PATH, E2E_PW_SUMMARY_PATH } = process.env;

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
		getAllureSummaryStats( E2E_PW_SUMMARY_PATH );
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
 * Create the heading and test results table.
 *
 * @param core The GitHub Actions toolkit core object
 */
const addSummaryHeadingAndTable = ( core ) => {
	const apiTableRow = createAPITableRow();
	const e2eTableRow = createE2ETableRow();

	core.summary.addHeading( 'Smoke tests on nightly build' ).addTable( [
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
			'https://woocommerce.github.io/woocommerce-test-reports/daily/api'
		)
		.addBreak()
		.addRaw( 'To view the full E2E test report, click ' )
		.addLink(
			'here.',
			'https://woocommerce.github.io/woocommerce-test-reports/daily/e2e'
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

	addSummaryFooter( core );

	const summary = core.summary.stringify();

	await core.summary.write();

	return summary;
};
