const { Octokit } = require('@octokit/action');

// Note that this script assumes you set GITHUB_TOKEN in env, if you don't
// this won't work.
const octokit = new Octokit();

const getPRAuthor = (payload) => {
	return payload?.pull_request?.user?.login || null;
}

const isCommunityContributor = async (owner, repo, username)  => {
	if (user) {
		const permission = await octokit.rest.repos.getCollaboratorPermissionLevel({
			owner,
			repo,
			username,
		});
	
	
		return permission === 'read' || permission === 'none';
	}

	return false;	
}

const addLabel = async(label, owner, repo, issueNumber) => {
	await octokit.rest.issues.addLabels({
		owner,
		repo,
		issue_number: issueNumber,
		labels: [label],
	});
}

const applyLabelToCommunityContributor = async () => {
	const context = require(process.env.GITHUB_EVENT_PATH);
	const username = getPRAuthor(context);
	const [owner, repo] = process.env.GITHUB_REPOSITORY.split("/");
	const { number } = context.issue;
	
	const isCommunityUser = await isCommunityContributor(owner, repo, username);

	if (isCommunityUser) {
		await addLabel('type: community contribution', owner, repo, number);
	}
}

applyLabelToCommunityContributor();
