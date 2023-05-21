/**
 * Extract `statistic` field from `allure-report/widgets/summary.json` and output as `stats`.
 *
 * @param {*} defArgs Arguments from `actions/github-script`. See https://github.com/actions/github-script#actionsgithub-script.
 * @yields `stats` output. It is the `statistic` field from `allure-report/widgets/summary.json`, automatically stringified by `actions/github-script`.
 */
module.exports = ( { core } ) => {
	const { ALLURE_REPORT_DIR } = process.env;
	const {
		statistic,
	} = require( `${ ALLURE_REPORT_DIR }/widgets/summary.json` );

	core.setOutput( 'stats', statistic );
};
