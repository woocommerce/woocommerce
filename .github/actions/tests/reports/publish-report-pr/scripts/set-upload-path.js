/**
 * Set upload path based on feature flag, test type.
 *
 * @param {*} contexts GitHub contexts passed by caller action
 * @param {string} reportsHome Bucket URL
 * @param {string} featSlug Slug to separate reports by feature flag
 * @param {string} testType e2e, api, or k6
 * @returns {string} Full upload path
 */
module.exports = ( { github }, reportsHome, featSlug, testType ) => {
	const prNumber = github.event.pull_request.number;
	const prPath = `${ reportsHome }/public/pr/${ prNumber }`;
	const testTypePath = featSlug
		? `${ featSlug }/${ testType }`
		: `${ testType }`;
	const uploadPath = `${ prPath }/${ testTypePath }`;

	return uploadPath;
};
