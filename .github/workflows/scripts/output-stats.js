/**
 * Output `statistic` field from `allure-report/widgets/summary.json`.
 *
 * @param {*} defArgs Default arguments passed by actions/github-script.
 * @returns {string} `statistic` JSON object automatically stringified by `actions/github-script`.
 */
module.exports = ( { core } ) => {
	const { ALLURE_REPORT_DIR } = process.env;
	const {
		statistic,
	} = require( `${ ALLURE_REPORT_DIR }/widgets/summary.json` );

	core.setOutput( 'stats', statistic );
};
