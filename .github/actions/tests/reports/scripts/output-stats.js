/**
 * Get statistics from `allure-report/widgets/summary.json`.
 *
 * @param {*} defArgs Arguments from `actions/github-script`. See https://github.com/actions/github-script#actionsgithub-script.
 * @yields `stats` output.
 */
module.exports = ( { core } ) => {
	const { ALLURE_REPORT_DIR } = process.env;
	const {
		statistic,
		time,
	} = require( `${ ALLURE_REPORT_DIR }/widgets/summary.json` );

	statistic.duration = time.duration;

	core.setOutput( 'stats', statistic );
};
