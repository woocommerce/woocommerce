// Note that this script assumes you set GITHUB_TOKEN in env, if you don't
// this won't work.

const getIssueAuthor = ( payload ) => {
	return (
		payload?.issue?.user?.login ||
		payload?.pull_request?.user?.login ||
		null
	);
};

const isCommunityContributor = async ( owner, repo, username ) => {
	if ( username ) {
		const {
			data: { permission },
		} = await github.rest.repos.getCollaboratorPermissionLevel( {
			owner,
			repo,
			username,
		} );

		return permission === 'read' || permission === 'none';
	}

	return false;
};

const addLabel = async ( label, owner, repo, issueNumber ) => {
	await github.rest.issues.addLabels( {
		owner,
		repo,
		issue_number: issueNumber,
		labels: [ label ],
	} );
};

const applyLabelToCommunityContributor = async () => {
	const eventPayload = require( process.env.GITHUB_EVENT_PATH );
	const username = getIssueAuthor( eventPayload );
	const [ owner, repo ] = process.env.GITHUB_REPOSITORY.split( '/' );
	const { number } = eventPayload?.issue || eventPayload?.pull_request;

	const isCommunityUser = await isCommunityContributor(
		owner,
		repo,
		username
	);

	core.setOutput( 'is-community', isCommunityUser ? 'yes' : 'no' );

	if ( isCommunityUser ) {
		console.log( 'Adding community contributor label' );
		await addLabel( 'type: community contribution', owner, repo, number );
	}
};

applyLabelToCommunityContributor();
