/**
 * Script to generate the test results summary to be posted as a GitHub Job Summary and as a PR comment.
 */
const {
	E2E_PLAYWRIGHT,
	API_SUMMARY_PATH,
	E2E_PW_SUMMARY_PATH,
	E2E_PPTR_SUMMARY_PATH,
	GITHUB_SHA,
} = process.env;

const getFormattedDuration = ( duration ) => {
	const durationMinutes = Math.floor( duration / 1000 / 60 );
	const durationSeconds = Math.floor( ( duration / 1000 ) % 60 );
	return `${ durationMinutes }m ${ durationSeconds }s`;
};

const getAPIStatsArr = () => {
	const apiSummary = require( API_SUMMARY_PATH );
	const { passed, failed, skipped, broken, unknown, total } =
		apiSummary.statistic;
	const { duration } = apiSummary.time;
	const durationFormatted = getFormattedDuration( duration );

	return [
		'API Tests',
		passed,
		failed,
		broken,
		skipped,
		unknown,
		total,
		durationFormatted,
	];
};

const getE2EStatsArr = () => {
	if ( E2E_PLAYWRIGHT === 'true' ) {
		const e2eSummary = require( E2E_PW_SUMMARY_PATH );
		const { passed, failed, skipped, broken, unknown, total } =
			e2eSummary.statistic;
		const { duration } = e2eSummary.time;
		const durationFormatted = getFormattedDuration( duration );

		return [
			'E2E Tests',
			passed,
			failed,
			broken,
			skipped,
			unknown,
			total,
			durationFormatted,
		];
	} else {
		const e2eSummary = require( E2E_PPTR_SUMMARY_PATH );
		const {
			numPassedTests: passed,
			numFailedTests: failed,
			numTotalTests: total,
			startTime,
			testResults,
		} = e2eSummary;
		const endTime = testResults[ testResults.length - 1 ].endTime;
		const duration = endTime - startTime;
		const durationFormatted = getFormattedDuration( duration );

		return [
			'E2E Tests',
			passed,
			failed,
			0,
			0,
			0,
			total,
			durationFormatted,
		];
	}
};

module.exports = async ( { core } ) => {
	await core.summary
		.addHeading( 'Test Results Summary' )
		.addRaw( `Commit SHA: ${ GITHUB_SHA }` )
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
			getAPIStatsArr(),
			getE2EStatsArr(),
		] )
		.addLink(
			'Link to the full API test report.',
			'https://github.com'
		)
		.addBreak()
		.addLink( 'Link to the full E2E test report.', 'https://github.com' )
		.write();
};
