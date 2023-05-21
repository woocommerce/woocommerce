/**
 * Output `statistic` field from `allure-report/widgets/summary.json`.
 *
 * @param {*} defArgs Arguments from `actions/github-script`. See https://github.com/actions/github-script#actionsgithub-script.
 * @returns {string} `statistic` JSON object automatically stringified by `actions/github-script`.
 */
module.exports = ( { core } ) => {
	const { ALLURE_REPORT_DIR } = process.env;
	const {
		statistic,
	} = require( `${ ALLURE_REPORT_DIR }/widgets/summary.json` );

	core.setOutput( 'stats', statistic );
};
