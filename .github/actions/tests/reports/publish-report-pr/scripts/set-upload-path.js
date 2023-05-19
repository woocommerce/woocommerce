/**
 * Set upload path based on feature flag, test type.
 *
 * @returns {string} Full upload path
 */
module.exports = () => {
	const { PR_NUMBER, REPORTS_HOME, FEAT_SLUG, TEST_TYPE } = process.env;
	const prPath = `${ REPORTS_HOME }/public/pr/${ PR_NUMBER }`;
	const testTypePath = FEAT_SLUG
		? `${ FEAT_SLUG }/${ TEST_TYPE }`
		: `${ TEST_TYPE }`;
	const uploadPath = `${ prPath }/${ testTypePath }`;

	return uploadPath;
};
