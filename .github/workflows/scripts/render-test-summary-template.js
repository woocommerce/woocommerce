const { GITHUB_WORKSPACE } = process.env;

module.exports = async () => {
	return `GITHUB_WORKSPACE: ${ GITHUB_WORKSPACE }`;
};
