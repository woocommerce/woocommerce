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
} = process.env;

/**
 * Convert the given `duration` from milliseconds to a more user-friendly string.
 * For example, if `duration = 323000`, this function would return `5m 23s`.
 *
 * @param {Number} duration Duration in millisecods, as read from either the `summary.json` file in the Allure report, or from the `test-results.json` file from the Jest-Puppeteer report.
 * @returns String in "5m 23s" format.
 */
const formatDuration = ( duration ) => {
	const durationMinutes = Math.floor( duration / 1000 / 60 );
	const durationSeconds = Math.floor( ( duration / 1000 ) % 60 );
	return `${ durationMinutes }m ${ durationSeconds }s`;
};

const parseRow = async ( { github, content, stats, rowTitle, reportUrl } ) => {
	const duration = formatDuration( stats.duration );

	return content.replace( '$API_TITLE', rowTitle ).replace( '' );
};

const getCommitMessage = async () => {
	const requestURL = `https://api.github.com/repos/woocommerce/woocommerce/commits/${ SHA }`;

	const response = await github.request( requestURL );
	const message = response.data.commit.message;
};

module.exports = async ( { github, core } ) => {
	const fs = require( 'fs' );

	const apiStats = JSON.parse( API_STATS );
	const e2eStats = JSON.parse( E2E_STATS );
	const apiHposStats = JSON.parse( API_HPOS_STATS );
	const e2eHposStats = JSON.parse( E2E_HPOS_STATS );

	const commitMessage = getCommitMessage();

	const content = fs.readFileSync(
		'./.github/actions/tests/reports/publish-report-pr/scripts/test-summary-template.md'
	);

	// Parse API row
	content = await parseRow( {
		github,
		content,
		stats: apiStats,
		rowTitle: 'API Tests',
		reportUrl: API_REPORT_URL,
	} );

	core.setOutput( 'summary', summary );
};
