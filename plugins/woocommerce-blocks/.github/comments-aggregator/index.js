/**
 * External dependencies
 */
const { getOctokit, context } = require( '@actions/github' );
const { setFailed, getInput } = require( '@actions/core' );

/**
 * Internal dependencies
 */
const { updateComment, isMergedComment } = require( './utils' );

const runner = async () => {
	try {
		const token = getInput( 'repo-token', { required: true } );
		const octokit = getOctokit( token );
		const payload = context.payload;
		const repo = payload.repository.name;
		const owner = payload.repository.owner.login;

		// Only run this action on pull requests.
		if ( ! payload.pull_request?.number ) {
			return;
		}

		const sectionId = getInput( 'section-id', {
			required: true,
		} );

		const content = getInput( 'content' );
		const order = getInput( 'order' );

		if ( ! sectionId || ! content ) {
			return;
		}

		let commentId, commentBody;

		const currentComments = await octokit.rest.issues.listComments( {
			owner,
			repo,
			issue_number: payload.pull_request.number,
		} );

		if (
			Array.isArray( currentComments.data ) &&
			currentComments.data.length > 0
		) {
			const comment = currentComments.data.find( ( comment ) =>
				isMergedComment( comment )
			);

			if ( comment ) {
				commentId = comment.id;
				commentBody = comment.body;
			}
		}

		commentBody = updateComment( commentBody, {
			sectionId,
			content,
			order,
		} );

		if ( commentId ) {
			await octokit.rest.issues.updateComment( {
				owner,
				repo,
				comment_id: commentId,
				body: commentBody,
			} );
		} else {
			await octokit.rest.issues.createComment( {
				owner,
				repo,
				issue_number: payload.pull_request.number,
				body: commentBody,
			} );
		}
	} catch ( error ) {
		setFailed( error.message );
	}
};

runner();
