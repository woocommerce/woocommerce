const https = require( 'https' );

const generateWordpressPlaygroundBlueprint = ( runId, prNumber ) => {
	const defaultSchema = {
		$schema: 'https://playground.wordpress.net/blueprint-schema.json',

		landingPage: '/wp-admin/',

		preferredVersions: {
			php: '8.0',
			wp: 'latest',
		},

		phpExtensionBundles: [ 'kitchen-sink' ],

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
				step: 'installPlugin',
				pluginZipFile: {
					resource: 'url',
					url: 'https://github.com/woocommerce/wc-smooth-generator/releases/download/1.1.0/wc-smooth-generator.zip',
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
	// Retrieve the PR branch name from the GitHub context
	// const prBranchName = context.payload.pull_request.head.ref;
	// const branchesUrl =
	// 	'https://betadownload.jetpack.me/woocommerce-branches.json';

	// Function to fetch JSON data using https
	// async function fetchJson( url ) {
	// 	return new Promise( ( resolve, reject ) => {
	// 		https.get( url, ( res ) => {
	// 			let data = '';

	// 			res.on( 'data', ( chunk ) => {
	// 				data += chunk;
	// 			} );

	// 			res.on( 'end', () => {
	// 				resolve( JSON.parse( data ) );
	// 			} );

	// 			res.on( 'error', ( err ) => {
	// 				reject( err );
	// 			} );
	// 		} );
	// 	} );
	// }

	// Fetch the branches data and extract the download URL
	try {
		// const payload = await fetchJson( branchesUrl );
		// const branches = Object.values( payload.pr );

		// const branch = branches.find( ( branch ) => {
		// 	return branch.branch === prBranchName;
		// } );

		// const artifactUrl = branch?.download_url;

		// if ( ! artifactUrl ) {
		// 	console.error(
		// 		'Download URL not found for the branch:',
		// 		prBranchName
		// 	);
		// 	return;
		// }

		const commentInfo = {
			owner: context.repo.owner,
			repo: context.repo.repo,
			issue_number: context.issue.number,
		};

		const comments = (
			await github.rest.issues.listComments( commentInfo )
		 ).data;

		for ( const currentComment of comments ) {
			if (
				currentComment.user.type === 'Bot' &&
				currentComment.body.includes(
					'Test using WordPress Playground'
				)
			) {
				return;
			}
		}

		const defaultSchema = generateWordpressPlaygroundBlueprint(
			context.runId,
			context.issue.number
		);

		const url = `https://playground.wordpress.net/#${ Buffer.from(
			JSON.stringify( defaultSchema )
		).toString( 'base64' ) }`;

		const proxyUrl = ( commentInfo.body = `
## Test using WordPress Playground
The changes in this pull request can be previewed and tested using a [WordPress Playground](https://developer.wordpress.org/playground/) instance.
[WordPress Playground](https://developer.wordpress.org/playground/) is an experimental project that creates a full WordPress instance entirely within the browser.

[Test this pull request with WordPress Playground](${ url }).
` );

		await github.rest.issues.createComment( commentInfo );
	} catch ( error ) {
		console.error( 'Error fetching branches data:', error );
	}
}

module.exports = { run };
