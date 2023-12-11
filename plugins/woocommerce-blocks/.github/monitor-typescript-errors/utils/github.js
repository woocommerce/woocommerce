const getReportCommentId = async ( { octokit, owner, repo, payload } ) => {
	const currentComments = await octokit.rest.issues.listComments( {
		owner,
		repo,
		issue_number: payload.pull_request.number,
	} );

	if (
		Array.isArray( currentComments.data ) &&
		currentComments.data.length > 0
	) {
		const comment = currentComments.data.find(
			( comment ) =>
				comment.body.includes( 'TypeScript Errors Report' ) &&
				comment.user.login === 'github-actions[bot]'
		);

		return comment?.id;
	}
};

exports.addComment = async ( { octokit, owner, repo, message, payload } ) => {
	const commentId = await getReportCommentId( {
		octokit,
		owner,
		repo,
		payload,
	} );

	if ( commentId ) {
		return await octokit.rest.issues.updateComment( {
			owner,
			repo,
			comment_id: commentId,
			body: message,
		} );
	}
	await octokit.rest.issues.createComment( {
		owner,
		repo,
		issue_number: payload.pull_request.number,
		body: message,
	} );
};
