const https = require( 'https' );

const COMMENT_HEADING = 'Test the build';
// Heading of the comment this script posted before it listed more than Playground.
const LEGACY_COMMENT_HEADING = 'Test using WordPress Playground';

// A store with no products has little to review, and a preview has nobody to add one.
const CREATE_SAMPLE_PRODUCT_PHP = `<?php
require '/wordpress/wp-load.php';
if ( ! wc_get_products( array( 'limit' => 1 ) ) ) {
	$product = new WC_Product_Simple();
	$product->set_name( 'Sample product' );
	$product->set_regular_price( '10' );
	$product->set_status( 'publish' );
	$product->save();
}
`;

const generateWordpressPlaygroundBlueprint = ( runId, prNumber ) => {
	const defaultSchema = {
		landingPage: '/wp-admin/admin.php?page=wc-admin',

		preferredVersions: {
			php: '8.1',
			wp: 'latest',
		},

		phpExtensionBundles: [ 'kitchen-sink' ],

		features: { networking: true },

		steps: [
			{
				step: 'installPlugin',
				pluginData: {
					resource: 'url',
					url: `https://playground.wordpress.net/plugin-proxy.php?org=woocommerce&repo=woocommerce&workflow=Build%20Live%20Branch&artifact=plugins-${ runId }&pr=${ prNumber }`,
				},
				options: {
					activate: true,
				},
			},
			{
				step: 'installPlugin',
				pluginData: {
					resource: 'url',
					url: 'https://github.com/woocommerce/woocommerce/releases/download/wc-beta-tester-4.0.0/woocommerce-beta-tester.zip',
				},
				options: {
					activate: true,
				},
			},
			{
				step: 'setSiteOptions',
				options: {
					woocommerce_onboarding_profile: {
						skipped: true,
					},
					// A store installed by a Blueprint starts on legacy order storage.
					woocommerce_custom_orders_table_enabled: 'yes',
				},
			},
			{
				step: 'runPHP',
				code: CREATE_SAMPLE_PRODUCT_PHP,
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

/**
 * Encodes a value for use inside the destination of a Markdown link.
 *
 * encodeURIComponent() leaves parentheses and a few other characters as they
 * are, and a parenthesis can end a Markdown link early.
 *
 * @param {string} value Value to encode.
 * @return {string} Encoded value.
 */
const encodeForMarkdownLink = ( value ) =>
	encodeURIComponent( value ).replace(
		/[!'()*]/g,
		( character ) =>
			`%${ character.charCodeAt( 0 ).toString( 16 ).toUpperCase() }`
	);

/**
 * Builds the Playground link for a Blueprint.
 *
 * The Blueprint is encoded so that no character in it can end the Markdown
 * link early.
 *
 * @param {Object} blueprint Playground Blueprint.
 * @return {string} Link that opens the Blueprint in WordPress Playground.
 */
const generatePlaygroundUrl = ( blueprint ) =>
	`https://playground.wordpress.net/#${ encodeForMarkdownLink(
		JSON.stringify( blueprint )
	) }`;

/**
 * Builds the Jurassic Ninja link that launches a site on a branch's build.
 *
 * Jurassic Ninja installs the build through Jetpack Beta, which serves the
 * builds this workflow reports to the Beta Download webhook.
 *
 * @param {string} branch Head branch of the pull request.
 * @return {string} Link that launches the site.
 */
const generateJurassicNinjaUrl = ( branch ) =>
	`https://jurassic.ninja/create/?jetpack-beta&nojetpack&branches.woocommerce=${ encodeForMarkdownLink(
		branch
	).replace( /%2F/g, '/' ) }`;

const generateCommentBody = ( { prNumber, branch, playgroundUrl } ) => `
## ${ COMMENT_HEADING }

### Option 1. Jetpack Beta

- Install and activate [Jetpack Beta](https://jetpack.com/beta/#download-and-install).
- Use this build by searching for PR number \`${ prNumber }\` or branch name \`${ branch }\` in your-test.site/wp-admin/admin.php?page=jetpack-beta&plugin=woocommerce

### Option 2. Jurassic Ninja - available for logged-in A12s

:warning: Log in at [jurassic.ninja](https://jurassic.ninja/) first. Without a login, the link below takes you to the Jurassic Ninja homepage and no site is created.

:rocket: [Launch a JN site with this branch](${ generateJurassicNinjaUrl(
	branch
) }) :rocket:

### Option 3. WordPress Playground - runs in your browser

:test_tube: [Test this pull request with WordPress Playground](${ playgroundUrl }): a store with the setup wizard skipped and a sample product.

:information_source: [WordPress Playground](https://developer.wordpress.org/playground/) is an experimental project that creates a full WordPress instance entirely within the browser. The site runs on SQLite and has no public address, so webhooks and payment provider callbacks cannot reach it.

Note that the Playground URL is valid for 30 days from when this comment was last updated. You can update it by closing/reopening the PR or pushing a commit that changes plugin code.
`;

const isBuildComment = ( comment ) =>
	comment.user.type === 'Bot' &&
	( comment.body.includes( COMMENT_HEADING ) ||
		comment.body.includes( LEGACY_COMMENT_HEADING ) );

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
		if ( isBuildComment( currentComment ) ) {
			existingCommentId = currentComment.id;
			break;
		}
	}

	const defaultSchema = generateWordpressPlaygroundBlueprint(
		context.runId,
		context.issue.number
	);

	const body = generateCommentBody( {
		prNumber: context.issue.number,
		branch: context.payload.pull_request.head.ref,
		playgroundUrl: generatePlaygroundUrl( defaultSchema ),
	} );

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

module.exports = {
	run,
	generateWordpressPlaygroundBlueprint,
	generatePlaygroundUrl,
	generateJurassicNinjaUrl,
	generateCommentBody,
	isBuildComment,
};
