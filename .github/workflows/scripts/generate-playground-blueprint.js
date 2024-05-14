const https = require( 'https' );

const generateWordpressPlaygroundBlueprint = ( runId, prNumber ) => {
	const defaultSchema = {
		landingPage: '/wp-admin/admin.php?page=wc-admin',

		preferredVersions: {
			php: '8.0',
			wp: 'latest',
		},

		phpExtensionBundles: [ 'kitchen-sink' ],

		features: { networking: true },

		steps: [
			{
				step: 'installPlugin',
				pluginZipFile: {
					resource: 'url',
					url: `https://playground.wordpress.net/plugin-proxy.php?org=woocommerce&repo=woocommerce&workflow=Build%20Live%20Branch&artifact=plugins-${ runId }&pr=${ prNumber }`,
				},
				options: {
					activate: true,
				},
			},
			{
				step: 'login',
				username: 'admin',
				password: 'password',
			},
		],
		plugins: [],
	};

	return defaultSchema;
};

async function run( { github, context, core } ) {
	const commentInfo = {
		owner: context.repo.owner,
		repo: context.repo.repo,
		issue_number: context.issue.number,
	};

	const comments = ( await github.rest.issues.listComments( commentInfo ) )
		.data;
	let existingCommentId = null;

	for ( const currentComment of comments ) {
		if (
			currentComment.user.type === 'Bot' &&
			currentComment.body.includes( 'Test using WordPress Playground' )
		) {
			existingCommentId = currentComment.id;
			break;
		}
	}

	const defaultSchema = generateWordpressPlaygroundBlueprint(
		context.runId,
		context.issue.number
	);

	const url = `https://playground.wordpress.net/#${ JSON.stringify(
		defaultSchema
	) }`;

	const body = `
## Test using WordPress Playground
The changes in this pull request can be previewed and tested using a [WordPress Playground](https://developer.wordpress.org/playground/) instance.
[WordPress Playground](https://developer.wordpress.org/playground/) is an experimental project that creates a full WordPress instance entirely within the browser.

[Test this pull request with WordPress Playground](${ url }).

Note that this URL is valid for 30 days from when this comment was last updated. You can update it by closing/reopening the PR or pushing a new commit.
`;

	if ( existingCommentId ) {
		await github.rest.issues.updateComment( {
			owner: commentInfo.owner,
			repo: commentInfo.repo,
			comment_id: existingCommentId,
			body: body,
		} );
	} else {
		commentInfo.body = body;
		await github.rest.issues.createComment( commentInfo );
	}
}

module.exports = { run };
