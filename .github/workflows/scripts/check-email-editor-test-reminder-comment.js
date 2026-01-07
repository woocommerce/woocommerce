/**
 * Check if a PR touches the email editor package and needs a test reminder comment.
 *
 * @param {Object} github  - Pre-authenticated octokit/rest.js client
 * @param {Object} context - Context of the workflow run
 * @param {Object} core    - A reference to the `@actions/core` package
 * @return {Promise<Object>} Promise resolving to an object with:
 *   - {boolean} touchesEmailEditor - Whether the PR touches packages/php/email-editor
 *   - {number} commentId - The comment ID if one exists, or 0
 */
async function checkEmailEditorTestReminderComment( github, context, core ) {
	const { repo, issue } = context;
	const { owner, repo: repoName } = repo;
	const prNumber = issue.number;

	core.info( `Checking if PR #${ prNumber } touches packages/php/email-editor` );

	// Get the list of files changed in this PR (paginated to get all files)
	const files = await github.paginate( github.rest.pulls.listFiles, {
		owner,
		repo: repoName,
		pull_number: prNumber,
		per_page: 100,
	} );

	// Check if any file is in packages/php/email-editor
	const touchesEmailEditor = files.some( ( file ) =>
		file.filename.startsWith( 'packages/php/email-editor/' )
	);

	if ( touchesEmailEditor ) {
		core.info(
			`PR #${ prNumber } touches packages/php/email-editor - test reminder needed`
		);
	} else {
		core.info(
			`PR #${ prNumber } does not touch packages/php/email-editor - no test reminder needed`
		);
	}

	// Check if a comment already exists (paginated to get all comments)
	const comments = await github.paginate( github.rest.issues.listComments, {
		owner,
		repo: repoName,
		issue_number: prNumber,
		per_page: 100,
	} );

	const existingComment = comments.find(
		( comment ) =>
			comment.user.login === 'github-actions[bot]' &&
			comment.body.includes( '<!-- email-editor-test-reminder -->' )
	);

	return {
		touchesEmailEditor,
		commentId: existingComment ? existingComment.id : 0,
	};
}

module.exports = {
	checkEmailEditorTestReminderComment,
};

