const { createReadStream } = require( 'fs' );
const { WebClient, ErrorCode } = require( '@slack/web-api' );

// Read a token from the environment variables
const slackToken = process.env.SLACK_TOKEN;

// Slack channel where the messages and screenshots will be sent to upon test failing
const slackChannel = process.env.SLACK_CHANNEL;

// if the current job is a pull request, the name of the branch from which the PR originated
// if the current job is a push build, this variable is empty ("")
const travisPullRequestBranch = process.env.TRAVIS_PULL_REQUEST_BRANCH;

// The commit that the current build is testing
const travisCommit = process.env.TRAVIS_COMMIT;

// URL to the build log
const travisBuildWebLog = process.env.TRAVIS_BUILD_WEB_URL;

// Initialize
const web = new WebClient( slackToken );

// A file name is required for the upload
const filename = 'screenshot_of_failed_test_on_Travis.jpeg';

export async function sendFailedTestMessageToSlack( testName ) {

	try {
		// For details, see: https://api.slack.com/methods/chat.postMessage
		await web.chat.postMessage({
			text: `Test failed on Travis on *${ travisPullRequestBranch }* branch. \n 
            The commit this build is testing is *${ travisCommit }*. \n 
            The name of the test that failed: *${ testName }*. \n 
            See screenshot of the failed test below. *Build log* could be found here: ${ travisBuildWebLog }`,
			channel: slackChannel,
		});
	} catch ( error ) {
		// Check the code property and log the response
		if ( error.code === ErrorCode.PlatformError || error.code === ErrorCode.RequestError ||
			error.code === ErrorCode.RateLimitedError || error.code === ErrorCode.HTTPError ) {
			console.log( error.data );
		} else {
			// Some other error, oh no!
			console.log( 'The error occurred does not match an error we are checking for in this block.' );
		}
	}
}

export async function sendFailedTestScreenshotToSlack( screenshotOfFailedTest ) {

	try {
		// For details, see: https://api.slack.com/methods/files.upload
		await web.files.upload({
			filename,
			file: createReadStream(screenshotOfFailedTest),
			channels: slackChannel,
		});
	} catch ( error ) {
		// Check the code property and log the response
		if ( error.code === ErrorCode.PlatformError || error.code === ErrorCode.RequestError ||
			error.code === ErrorCode.RateLimitedError || error.code === ErrorCode.HTTPError ) {
			console.log( error.data );
		} else {
			// Some other error, oh no!
			console.log( 'The error occurred does not match an error we are checking for in this block.' );
		}
	}
}