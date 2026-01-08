/**
 * Helper function to find an existing email editor test reminder comment.
 *
 * @param {Object} github  - Pre-authenticated octokit/rest.js client
 * @param {Object} context - Context of the workflow run
 * @return {Promise<Object|null>} Promise resolving to the comment object or null if not found
 */
async function findExistingComment( github, context ) {
	const comments = await github.paginate( github.rest.issues.listComments, {
		owner: context.repo.owner,
		repo: context.repo.repo,
		issue_number: context.issue.number,
		per_page: 100,
	} );

	return (
		comments.find(
			( comment ) =>
				comment.user.login === 'github-actions[bot]' &&
				comment.body.includes( '<!-- email-editor-test-reminder -->' )
		) || null
	);
}

/**
 * Generate the comment body for the email editor test reminder.
 *
 * @param {number} prNumber - The PR number
 * @return {string} The formatted comment body
 */
function generateCommentBody( prNumber ) {
	return `<!-- email-editor-test-reminder -->
## Email Editor Testing Required

Are you an Automattician? This PR touches the email editor package and will need to be tested on WordPress.com.

### Testing Steps

1. Log into your sandbox. You'll need to authenticate with Github if you haven't already.
   \`\`\`bash
   gh auth login
   \`\`\`
   You can choose https and complete the authentication in your local browser with the code provided.

2. Run the downloader script on your sandbox:
   \`\`\`bash
   bash bin/woocommerce-email-editor-downloader test ${ prNumber }
   \`\`\`
   You'll see the PR title, author, and state, and then be asked to confirm the download. You may see a bunch of composer deprecation notices. Once it completes, check that there's a \`/dev\` directory in \`wp-content/lib/woocommerce-email-editor/\`.

3. Sandbox the API and turn on write access:
   \`\`\`bash
   allow-sandbox-production-writes "10 minutes"
   \`\`\`

4. On a test WP.com site, create a new post. Use the Newsletter panel in the editor to send yourself a test email.

5. To remove the changes after testing:
   \`\`\`bash
   bash bin/woocommerce-email-editor-downloader reset
   \`\`\``;
}

/**
 * Create or update the email editor test reminder comment on a PR.
 *
 * @param {Object} github  - Pre-authenticated octokit/rest.js client
 * @param {Object} context - Context of the workflow run
 * @param {Object} core    - A reference to the `@actions/core` package
 * @return {Promise<void>}
 */
async function createOrUpdateTestReminderComment( github, context, core ) {
	const prNumber = context.issue.number;
	const commentBody = generateCommentBody( prNumber );

	core.info( `Creating or updating test reminder comment for PR #${ prNumber }` );

	const existingComment = await findExistingComment( github, context );

	if ( existingComment ) {
		// Update existing comment
		await github.rest.issues.updateComment( {
			owner: context.repo.owner,
			repo: context.repo.repo,
			comment_id: existingComment.id,
			body: commentBody,
		} );
		core.info( `Updated existing test reminder comment for PR #${ prNumber }` );
	} else {
		// Create new comment
		await github.rest.issues.createComment( {
			owner: context.repo.owner,
			repo: context.repo.repo,
			issue_number: context.issue.number,
			body: commentBody,
		} );
		core.info( `Created new test reminder comment for PR #${ prNumber }` );
	}
}

/**
 * Delete the email editor test reminder comment from a PR if it exists.
 *
 * @param {Object} github  - Pre-authenticated octokit/rest.js client
 * @param {Object} context - Context of the workflow run
 * @param {Object} core    - A reference to the `@actions/core` package
 * @return {Promise<void>}
 */
async function deleteTestReminderComment( github, context, core ) {
	const prNumber = context.issue.number;

	core.info( `Checking for test reminder comment to delete for PR #${ prNumber }` );

	const existingComment = await findExistingComment( github, context );

	if ( existingComment ) {
		await github.rest.issues.deleteComment( {
			owner: context.repo.owner,
			repo: context.repo.repo,
			comment_id: existingComment.id,
		} );
		core.info( `Deleted test reminder comment for PR #${ prNumber }` );
	} else {
		core.info( `No test reminder comment found for PR #${ prNumber }` );
	}
}

module.exports = {
	createOrUpdateTestReminderComment,
	deleteTestReminderComment,
};

