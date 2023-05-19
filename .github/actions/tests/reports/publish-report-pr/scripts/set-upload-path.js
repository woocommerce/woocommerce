module.exports = ( { github }, reportsHome, featSlug, testType ) => {
	const prNumber = github.event.pull_request.number;
	const prPath = `${ reportsHome }/public/pr/${ prNumber }`;
	const testTypePath = featSlug
		? `${ featSlug }/${ testType }`
		: `${ testType }`;
	const uploadPath = `${ prPath }/${ testTypePath }`;

	return uploadPath;
};
