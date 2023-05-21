const {
	API_STATS,
	E2E_STATS,
	API_HPOS_STATS,
	E2E_HPOS_STATS,
	API_REPORT_URL,
	E2E_REPORT_URL,
	API_HPOS_REPORT_URL,
	E2E_HPOS_REPORT_URL,
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
 * Construct array of row values.
 *
 * @returns {string[]} Array of test result stats.
 */
const addRow = ( rowTitle, reportURL, stats ) => {
	const { passed, failed, skipped, broken, unknown, total, duration } = stats;
	const durationFormatted = getFormattedDuration( duration );
	const reportLink = `[${ rowTitle }](${ reportURL })`;

	return [
		reportLink,
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
 * Add results table to core object.
 *
 * @param core The GitHub Actions toolkit core object
 */
const addTable = ( core ) => {
	const apiStats = JSON.parse( API_STATS );
	const e2eStats = JSON.parse( E2E_STATS );
	const apiHposStats = JSON.parse( API_HPOS_STATS );
	const e2eHposStats = JSON.parse( E2E_HPOS_STATS );

	const apiReportURL = JSON.parse( API_REPORT_URL );
	const e2eReportURL = JSON.parse( E2E_REPORT_URL );
	const apiHposReportURL = JSON.parse( API_HPOS_REPORT_URL );
	const e2eHposReportURL = JSON.parse( E2E_HPOS_REPORT_URL );

	const apiRow = addRow( 'API Tests', apiReportURL, apiStats );
	const e2eRow = addRow( 'E2E Tests', e2eReportURL, e2eStats );
	const apiHposRow = addRow(
		'API Tests (HPOS enabled)',
		apiHposReportURL,
		apiHposStats
	);
	const e2eHposRow = addRow(
		'E2E Tests (HPOS enabled)',
		e2eHposReportURL,
		e2eHposStats
	);

	core.summary.addTable( [
		[
			{
				data: 'Test (click to view full report) :test_tube:',
				header: true,
			},
			{
				data: 'Full report :chart_with_upwards_trend:',
				header: true,
			},
			{ data: 'Passed :white_check_mark:', header: true },
			{ data: 'Failed :rotating_light:', header: true },
			{ data: 'Broken :construction:', header: true },
			{ data: 'Skipped :next_track_button:', header: true },
			{ data: 'Unknown :grey_question:', header: true },
			{ data: 'Total :bar_chart:', header: true },
			{ data: 'Duration :stopwatch:', header: true },
		],
		apiRow,
		e2eRow,
		apiHposRow,
		e2eHposRow,
	] );
};

const addHeading = ( core ) => {
	core.summary.addHeading( `Test results for ${ SHA }` );
};

const addCommitMessage = async ( github, core ) => {
	const params = {
		owner: 'woocommerce',
		repo: 'woocommerce',
		pull_number: PR_NUMBER,
	};

	const response = await github.rest.pulls.listCommits( params );
	const message = response.data.find( ( { sha } ) => sha === SHA ).commit
		.message;

	core.summary.addRaw( 'Commit Message:', true );
	core.summary.addQuote( message );
};

/**
 * Generate the contents of the test results summary and post it on the workflow run.
 *
 * @param {*} params Objects passed from the calling GitHub Action workflow.
 * @yields `summary` step output. It is the stringified content of the test results summary.
 */
module.exports = async ( { github, core } ) => {
	addHeading( core );
	await addCommitMessage( github, core );
	addTable( core );

	const summary = core.summary.stringify();

	await core.summary.write();

	core.setOutput( 'summary', summary );
};
