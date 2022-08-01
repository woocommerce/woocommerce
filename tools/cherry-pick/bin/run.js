/**
 * Tool to automate steps to cherry pick fixes into release branch.
 *
 * @package
 */

import fetch from 'node-fetch';
import os from 'os';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync, spawn } from 'node:child_process';
import ora from 'ora';

const args                  = process.argv.slice( 2 );
const usage                 = 'Usage: pnpm cherry-pick <release_branch> <pull_request_number>. Separate pull request numbers with a comma (no space) if more than one.';
const releaseBranch         = args[ 0 ];
const prs                   = args[ 1 ];
let   githubToken           = '';
let   tempWooDir            = '';
let   changelogsToBeDeleted = [];
let   prCommits             = {};
let   githubRemoteURL       = 'https';

if ( typeof process.env.GITHUB_CHERRY_PICK_TOKEN === 'undefined' ) {
	console.error( 'A GitHub token needs to be assigned to the "GITHUB_CHERRY_PICK_TOKEN" environment variable' );
	process.exit( 1 );
}

if ( typeof process.env.GITHUB_REMOTE_URL !== 'undefined' ) {
	githubRemoteURL = process.env.GITHUB_REMOTE_URL;
}

// If no arguments are passed.
if ( ! args.length ) {
	console.error( usage );
	process.exit( 1 );
}

// If missing one of the arguments.
if ( typeof releaseBranch === 'undefined' || typeof prs === 'undefined' ) {
	console.error( usage );
	process.exit( 1 );
}

// Accounts for multiple PRs.
const prsArr           = prs.split( ',' );
const version          = releaseBranch.replace( 'release/', '' );
const cherryPickBranch = 'cherry-pick-' + version + '/' + prsArr.toString().replace( ',', '-' );
githubToken            = process.env.GITHUB_CHERRY_PICK_TOKEN;

async function getCommitFromPrs( prsArr ) {
	let properties = {
			method: 'GET',
			headers: {
				'Accept': 'application/vnd.github.v3+json',
				'Authorization': `token ${githubToken}`
			}
		};

	for ( const pr of prsArr ) {
		const response = await fetch( 'https://api.github.com/repos/woocommerce/woocommerce/pulls/' + pr, properties );

		if ( response.status !== 200 ) {
			throw 'One or more of the PR reference was not found.';
		}

		await response.json().then( data => {
			prCommits[ pr ] = data.merge_commit_sha;
		} );
	}
}

/**
 * Checks out a branch.
 *
 * @param string branch The branch to checkout.
 * @param boolean newBranch A flag to create a new branch.
 */
function checkoutBranch( branch, newBranch = false ) {
	const spinner = ora( {
		spinner: 'bouncingBar',
		color: 'green'
	} );

	let output = [];

	return new Promise( ( resolve, reject ) => {
		const response = newBranch ? spawn( 'git', [ 'checkout', '-b', branch ] ) : spawn( 'git', [ 'checkout', branch ] );

		response.stdout.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.stderr.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.on( 'close', ( code ) => {
			if ( `${code}` == 0 ) {
				spinner.succeed( `Switched to '${branch}'` );
				resolve();
			}

			reject( `error: ${output.toString().replace( ',', "\n" )}` );
		} );
	} ).catch( err => {
		spinner.fail( 'Failed to switch branch' );
		throw err;
	} );
}

/**
 * Create the temp directory in system temp.
 *
 * @param string path The path to create.
 */
function createDir( path ) {
	return new Promise( ( resolve, reject ) => {
		fs.mkdtemp( path, ( err, directory ) => {
			if ( err ) {
				reject( err );
			}

			tempWooDir = directory;

			resolve();
		} );
	} ).catch( err => {
		throw err;
	} );
}

/**
 * Clones the WooCommerce repo into temp dir.
 *
 * @param string woodir The temporary system directory.
 */
function cloneWoo() {
	const spinner = ora( {
		text: `Cloning WooCommerce into ${tempWooDir}/woocommerce`,
		spinner: 'bouncingBar',
		color: 'green'
	} );

	spinner.start();

	let output = [];

	return new Promise( ( resolve, reject ) => {
		let url = 'https://github.com/woocommerce/woocommerce.git';

		if ( githubRemoteURL === 'ssh' ) {
			url = 'git@github.com:woocommerce/woocommerce.git';
		}

		const response = spawn( 'git', [ 'clone', url ] );

		response.stdout.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.stderr.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.on( 'close', ( code ) => {
			if ( `${code}` == 0 ) {
				spinner.succeed();
				resolve();
			}

			reject( `error: ${output.toString().replace( ',', "\n" )}` );
		} );
	} ).catch( err => {
		spinner.fail( 'Fail to clone WooCommerce!' );
		throw err;
	} );
}

/**
 * Cherry picks.
 *
 * @param string commit The commit hash to cherry pick.
 */
function cherryPick( commit ) {
	const spinner = ora( {
		text: `Cherry picking ${commit}`,
		spinner: 'bouncingBar',
		color: 'green'
	} );

	spinner.start();

	const response = spawnSync( 'git', [ 'cherry-pick', commit ] );

	if ( response.status == 0 ) {
		spinner.succeed();
		return;
	}

	if ( response.stderr ) {
		if ( response.stderr.match( 'fatal: bad revision' ) || response.stderr.match( 'error: could not apply' ) ) {
			spinner.fail( `Fail cherry picking ${commit}` );
			throw `stderr: ${response.stderr}`;
		}
	};
}

/**
 * Function to change directories.
 *
 * @param string dir The directory to change to.
 */
function chdir( dir ) {
	try {
		process.chdir( dir );
	} catch ( e ) {
		throw e;
	}
}

/**
 * Generates the changelog for readme.txt.
 *
 * @param string pr The PR to use.
 * @param string commit The commit to use.
 */
async function generateChangelog( pr, commit ) {
	let properties = {
			method: 'GET',
			headers: {
				'Content-Type': 'application/vnd.github.v3+json'
			}
		};

	if ( githubToken ) {
		properties.headers.Authorization = 'token ' + githubToken;
	}

	const response = await fetch( 'https://api.github.com/repos/woocommerce/woocommerce/commits/' + commit, properties );

	if ( response.status !== 200 ) {
		throw 'Commit was not found.';
	}

	let changelogTxt = '';

	await response.json().then( data => {
		for ( const file of data.files ) {
			if ( file.filename.match( 'plugins/woocommerce/changelog/' ) ) {
				if ( changelogsToBeDeleted.indexOf( file.filename ) === -1 ) {
					changelogsToBeDeleted.push( file.filename );
				}

				let changelogEntry     = '';
				let changelogEntryType = '';

				fs.readFile( './' + file.filename, 'utf-8', function( err, data ) {
					if ( err ) {
						throw err;
					}

					const changelogEntryArr  = data.split( "\n" );
					changelogEntryType = data.match( /Type: (.+)/i );
					changelogEntryType = changelogEntryType[ 1 ].charAt( 0 ).toUpperCase() + changelogEntryType[ 1 ].slice( 1 );

					changelogEntry = changelogEntryArr.filter( el => {
						return el !== null && typeof el !== 'undefined' && el !== '';
					} );
					changelogEntry = changelogEntry[ changelogEntry.length - 1 ];

					// Check if changelogEntry is what we want.
					if ( changelogEntry.length < 1 ) {
						changelogEntry = false;
					}

					if ( changelogEntry.match( /significance:/i ) ) {
						changelogEntry = false;
					}

					if ( changelogEntry.match( /type:/i ) ) {
						changelogEntry = false;
					}

					if ( changelogEntry.match( /comment:/i ) ) {
						changelogEntry = false;
					}
				} );

				if ( changelogEntry === false ) {
					continue;
				}

				fs.readFile( './plugins/woocommerce/readme.txt', 'utf-8', function( err, data ) {
					if ( err ) {
						throw err;
					}

					const spinner = ora( {
						text: `Generating changelog entry for PR ${pr}`,
						spinner: 'bouncingBar',
						color: 'green'
					} );

					spinner.start();

					changelogTxt = data.split( "\n" );
					let isInRange = false;
					let newChangelogTxt = [];

					for ( const line of changelogTxt ) {
						if ( isInRange === false && line === '== Changelog ==' ) {
							isInRange = true;
						}

						if ( isInRange === true && line.match( /\*\*WooCommerce Blocks/ ) ) {
							isInRange = false;
						}

						// Find the first match of the entry "Type".
						if ( isInRange && line.match( `\\* ${changelogEntryType} -` ) ) {
							newChangelogTxt.push( '* ' + changelogEntryType + ' - ' + changelogEntry + ` [#${pr}](https://github.com/woocommerce/woocommerce/pull/${pr})` );
							newChangelogTxt.push( line );
							isInRange = false;
							continue;
						}

						newChangelogTxt.push( line );
					}

					fs.writeFile( './plugins/woocommerce/readme.txt', newChangelogTxt.join( "\n" ), err => {
						if ( err ) {
							spinner.fail( `Unable to generate the changelog entry for PR ${pr}` );
							throw err;
						}

						spinner.succeed();
					} );
				} );
			}
		}
	} );
}

/**
 * Git remove changelog files.
 *
 */
function deleteChangelogFiles() {
	const spinner = ora( {
		spinner: 'bouncingBar',
		color: 'green'
	} );

	const files = changelogsToBeDeleted.join( ' ' );
	const filesFormatted = "\n" + files.replace( ' ', "\n" );
	let output = [];

	return new Promise( ( resolve, reject ) => {
		const response = spawn( 'git', [ 'rm', files ], { shell: true } );

		response.stdout.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.stderr.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.on( 'close', ( code ) => {
			if ( `${code}` == 0 ) {
				spinner.succeed( `Removed changelog files:${filesFormatted}` );
				resolve();
			}

			reject( `error: ${output.toString().replace( ',', "\n" )}` );
		} );
	} ).catch( err => {
		spinner.fail( 'Fail to delete changelog files' );
		throw err;
	} );
}

/**
 * Commit changes.
 *
 * @param string msg The message for the commit.
 */
function commitChanges( msg ) {
	const spinner = ora( {
		spinner: 'bouncingBar',
		color: 'green'
	} );

	let output = [];

	return new Promise( ( resolve, reject ) => {
		const response = spawn( 'git', [ 'commit', '--no-verify', '-am', msg ] );

		response.stdout.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.stderr.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.on( 'close', ( code ) => {
			if ( `${code}` == 0 ) {
				spinner.succeed( `Commited changes.` );
				resolve();
			}

			reject( `error: ${output.toString().replace( ',', "\n" )}` );
		} );
	} ).catch( err => {
		spinner.fail( 'Fail to commit changes.' );
		throw err;
	} );
}

/**
 * Push the branch up to GitHub.
 *
 * @param string branch The branch to push to GitHub.
 */
function pushBranch( branch ) {
	const spinner = ora( {
		spinner: 'bouncingBar',
		color: 'green'
	} );

	spinner.start( `Pushing ${branch} to GitHub...` );

	let output = [];

	return new Promise( ( resolve, reject ) => {
		const response = spawn( 'git', [ 'push', 'origin', branch ] );

		response.stdout.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.stderr.on( 'data', ( data ) => {
			output.push( `${data}` );
		} );

		response.on( 'close', ( code ) => {
			if ( `${code}` == 0 ) {
				spinner.succeed();
				resolve();
			}

			reject( `error: ${output.toString().replace( ',', "\n" )}` );
		} );
	} ).catch( err => {
		spinner.fail( `Fail to push ${branch} up.` );
		throw err;
	} );
}

/**
 * Create pull request on GitHub.
 *
 * @param string title The title of the PR.
 * @param string body The body content of the PR.
 * @param string head The head of the branch to use.
 * @param string base The base branch to targe against.
 */
async function createPR( title, body, head, base ) {
	const spinner = ora( {
		spinner: 'bouncingBar',
		color: 'green'
	} );

	spinner.start( `Creating pull request for ${head} on GitHub...` );

	const response = await fetch( 'https://api.github.com/repos/woocommerce/woocommerce/pulls', {
		method: 'POST',
		headers: {
			'Accept': 'application/vnd.github.v3+json',
			'Authorization': `token ${githubToken}`,
			'Content-Type': 'application/json'
		},
		body: JSON.stringify( {
			"title": title,
			"body": body,
			"head": head,
			"base": base
		} )
	} );

	if ( response.status !== 201 ) {
		throw 'Fail to create PR on GitHub.';
	}

	return await response.json().then( data => {
		spinner.succeed();
		return {
			"url": data.url,
			"number": data.number
		}
	} );
}

( async function() {
	try {
		// Creates a temp directory to work with.
		await createDir( path.join( os.tmpdir(), 'woo-cherry-pick-') );

		// Gets the commits from GitHub based on the PRs passed in.
		await getCommitFromPrs( prsArr );

		chdir( tempWooDir );

		await cloneWoo();

		chdir( tempWooDir + '/woocommerce' );

		// This checks out the release branch.
		await checkoutBranch( releaseBranch );

		// This checks out a new branch based on the release branch.
		await checkoutBranch( cherryPickBranch, true );

		let cherryPickPRBody = "This PR cherry-picks the following PRs into the release branch:\n";

		for ( const pr of Object.keys( prCommits ) ) {
			cherryPickPRBody = `${cherryPickPRBody}` + `* #${pr}` + "\n";
			cherryPick( prCommits[ pr ] );
			await generateChangelog( pr,  prCommits[ pr ] );
		}

		// Deletes the changelog files from the release branch.
		await deleteChangelogFiles();

		await commitChanges( `Prep for cherry pick ${prsArr.toString()}` );

		await pushBranch( cherryPickBranch );

		const cherryPickPR = await createPR(
			`Prep for cherry pick ${prsArr.toString()}`,
			cherryPickPRBody,
			cherryPickBranch,
			releaseBranch
		);

		await checkoutBranch( 'trunk' );

		const deleteChangelogBranch = `delete-changelogs/${prsArr.toString().replace( ',', '-' )}`;

		// This checks out a new branch based on the trunk branch.
		await checkoutBranch( `${deleteChangelogBranch}`, true );

		// Deletes the changelog files from the trunk branch.
		await deleteChangelogFiles();

		await commitChanges( `Delete changelog files for ${prsArr.toString()}` );

		await pushBranch( `${deleteChangelogBranch}` );

		const deleteChangelogsPR = await createPR(
			`Delete changelog files based on PR ${cherryPickPR.number}`,
			`Delete changelog files based on PR #${cherryPickPR.number}`,
			deleteChangelogBranch,
			'trunk'
		);

		console.log( `Two PRs created by this process:` );
		console.log( cherryPickPR.url );
		console.log( deleteChangelogsPR.url );
	} catch ( e ) {
		console.error( e );
		process.exit( 1 );
	}
} )();
