'use strict';
const chalk = require( 'chalk' );
const promptly = require( 'promptly' );
const { REPO, pkg } = require( '../config' );
const { make } = require( '../common' );
const { fetchAllPullRequests } = require( './requests' );

/* eslint no-console: 0 */
let ready = false;

const makeChangeLog = async () => {
	if ( ! pkg.changelog.ghApiToken ) {
		console.log(
			chalk.yellow(
				'This program requires an api token. You can create one here: '
			) + 'https://github.com/settings/tokens'
		);
		console.log( '' );
		console.log(
			chalk.yellow(
				'Token scope will require read permissions on public_repo, admin:org, and user.'
			)
		);
		console.log( '' );
		console.log(
			chalk.yellow(
				'Export the token as variable called GH_API_TOKEN from your bash profile.'
			)
		);
		console.log( '' );

		ready = await promptly.confirm( 'Are you ready to continue? ' );
	} else {
		console.log( chalk.green( 'Detected GH_API_TOKEN is set.' ) );
		ready = true;
	}

	if ( ready ) {
		console.log( '' );
		console.log(
			chalk.yellow(
				'In order to generate the changelog, you will have to provide a version number to retrieve the PRs from.'
			)
		);
		console.log( '' );
		console.log(
			chalk.yellow( 'Write it as it appears in the milestones page: ' ) +
				`https://github.com/${ REPO }/milestones`
		);
		console.log( '' );
		const version = await promptly.prompt( 'Version number: ' );
		console.log( '' );
		console.log(
			chalk.green(
				'Here is the generated changelog. Be sure to remove entries ' +
					`not intended for a ${ pkg.title } release.`
			)
		);
		console.log( '' );
		make( fetchAllPullRequests, version );
	} else {
		console.log( '' );
		console.log( chalk.yellow( 'Ok, see you soon.' ) );
		console.log( '' );
	}
};

module.exports = {
	makeChangeLog,
};
