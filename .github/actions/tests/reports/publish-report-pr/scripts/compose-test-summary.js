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

const insertCommitDetails = async ( github, content ) => {
	const requestURL = `https://api.github.com/repos/woocommerce/woocommerce/commits/${ SHA }`;
	const response = await github.request( requestURL );
	const message = response.data.commit.message;

	return content
		.replace( '$COMMIT_SHA', SHA )
		.replace( '$COMMIT_MESSAGE', message );
};

const insertRow = ( content, rowTitle, reportURL, stats ) => {
	const placeHolder = '\n<!-- EOT -->';
	const { passed, failed, broken, skipped, unknown, total, duration } = stats;
	const fmtDuration = formatDuration( duration );
	const testLink = `[${ rowTitle }](${ reportURL })`;
	const cells = [
		testLink,
		passed.toString(),
		failed.toString(),
		broken.toString(),
		skipped.toString(),
		unknown.toString(),
		total.toString(),
		fmtDuration,
	];
	const row = `| ${ cells.join( ' | ' ) } |`;

	return content.replace( placeHolder, row + placeHolder );
};

module.exports = async ( { github, core } ) => {
	const fs = require( 'fs' );
	const apiStats = JSON.parse( API_STATS );
	const e2eStats = JSON.parse( E2E_STATS );
	const apiHposStats = JSON.parse( API_HPOS_STATS );
	const e2eHposStats = JSON.parse( E2E_HPOS_STATS );
	let content = fs
		.readFileSync(
			'./.github/actions/tests/reports/publish-report-pr/scripts/test-summary-template.md'
		)
		.toString();

	content = await insertCommitDetails( github, content );
	content = insertRow( content, 'API Tests', API_REPORT_URL, apiStats );
	content = insertRow( content, 'E2E Tests', E2E_REPORT_URL, e2eStats );
	content = insertRow(
		content,
		'API Tests (HPOS enabled)',
		API_HPOS_REPORT_URL,
		apiHposStats
	);
	content = insertRow(
		content,
		'E2E Tests (HPOS enabled)',
		E2E_HPOS_REPORT_URL,
		e2eHposStats
	);

	core.setOutput( 'summary', content );
};
