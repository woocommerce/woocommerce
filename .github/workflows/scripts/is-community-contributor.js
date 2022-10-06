// Note you'll need to install this dependency as part of your workflow.
const { Octokit } = require('@octokit/action');

// Note that this script assumes you set GITHUB_TOKEN in env, if you don't
// this won't work.
const octokit = new Octokit();

const getPRAuthor = (payload) => {
	return payload?.pull_request?.user?.login || null;
}

const isCommunityContributor = async (owner, repo, username)  => {
	if (username) {
		const {data: {permission}} = await octokit.rest.repos.getCollaboratorPermissionLevel({
			owner,
			repo,
			username,
		});

		console.log("User has permission: ", permission)
	
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
	const eventPayload = require(process.env.GITHUB_EVENT_PATH);
	const username = getPRAuthor(eventPayload);
	const [owner, repo] = process.env.GITHUB_REPOSITORY.split("/");
	const { number } = eventPayload?.issue || eventPayload?.pull_request;
	
	const isCommunityUser = await isCommunityContributor(owner, repo, username);

	if (isCommunityUser) {
		await addLabel('type: community contribution', owner, repo, number);
	} else {
		console.log('User is not a community contributor');
	}
}

applyLabelToCommunityContributor();
