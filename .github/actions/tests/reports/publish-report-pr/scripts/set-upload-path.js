/**
 * Output bucket destination as `upload-path`.
 * Output report link as `report-url`.
 *
 * @param {*} args Default arguments available from `actions/github-script`
 */
module.exports = ( { core } ) => {
	const { PR_NUMBER, REPORTS_HOME, FEAT_SLUG, TEST_TYPE } = process.env;
	const prPath = `${ REPORTS_HOME }/public/pr/${ PR_NUMBER }`;

	// Set bucket destination
	const testTypePath = FEAT_SLUG
		? `${ FEAT_SLUG }/${ TEST_TYPE }`
		: `${ TEST_TYPE }`;
	const uploadPath = `${ prPath }/${ testTypePath }`;

	// Set report path
	const prReportsWebHome =
		'https://woocommerce.github.io/woocommerce-test-reports/pr';
	const reportURL = `${ prReportsWebHome }/${ PR_NUMBER }/${ testTypePath }`;

	core.setOutput( 'upload-path', uploadPath );
	core.setOutput( 'report-url', reportURL );
};
