import fs from 'fs';
import path from 'path';
import { Octokit } from '@octokit/rest';
import { google } from 'googleapis';
import { GoogleAuth } from 'google-auth-library';
import axios from 'axios';
import dotenv from 'dotenv';

dotenv.config();

const FLAKY_THRESHOLD = 5; // The number of flaky instances required for action to be taken

// Load credentials from JSON file
const credentialsPath = path.resolve( './key.json' );
const credentials = JSON.parse( fs.readFileSync( credentialsPath, 'utf8' ) );

// Load environment variables
const {
	BUILDKITE_API_URL,
	BUILDKITE_API_TOKEN,
	GOOGLE_SHEET_ID,
	GITHUB_TOKEN,
	GITHUB_REPO_OWNER,
	GITHUB_REPO_NAME,
} = process.env;

// Set up the authentication client
const auth = new GoogleAuth( {
	credentials,
	scopes: [ 'https://www.googleapis.com/auth/spreadsheets' ],
} );

// Initialize Octokit
const octokit = new Octokit( {
	auth: GITHUB_TOKEN,
} );

// Function to fetch data from Buildkite API with pagination
async function fetchBuildkiteData() {
	let allData = [];
	let url = `${ BUILDKITE_API_URL }?branch=refs%2Fheads%2Ftrunk`; // Add branch filter to the URL so only trunk is queried

	while ( url ) {
		try {
			const response = await axios.get( url, {
				headers: {
					Authorization: `Bearer ${ BUILDKITE_API_TOKEN }`,
				},
			} );

			// Append the data from the current page
			allData = allData.concat( response.data );

			// Check for the 'Link' header to get the next page URL
			const linkHeader = response.headers.link;
			if ( linkHeader ) {
				const links = linkHeader
					.split( ',' )
					.map( ( link ) => link.trim() );
				const nextLink = links.find( ( link ) =>
					link.includes( 'rel="next"' )
				);
				if ( nextLink ) {
					url = nextLink.match( /<(.*?)>/ )[ 1 ];
				} else {
					url = null;
				}
			} else {
				url = null;
			}
		} catch ( error ) {
			console.error( 'Error fetching data from Buildkite API:', error );
			throw error;
		}
	}

	return allData;
}

// Function to fetch existing data from Google Sheets with exponential backoff
// https://docs.google.com/spreadsheets/d/1qCAemoX-LIOsAtxCMYTISJi57KwF4GIb6Pco_N6EDmM/edit?usp=sharing
async function fetchSheetData( authClient, spreadsheetId, range ) {
	const sheets = google.sheets( { version: 'v4', auth: authClient } );

	let retryCount = 0;
	const maxRetries = 5;

	while ( retryCount < maxRetries ) {
		try {
			const response = await sheets.spreadsheets.values.get( {
				spreadsheetId,
				range,
			} );
			return response.data.values;
		} catch ( error ) {
			if ( error.response && error.response.status === 429 ) {
				retryCount++;
				const waitTime = Math.pow( 2, retryCount ) * 1000; // Exponential backoff
				console.log(
					`Quota exceeded for read requests. Retrying in ${
						waitTime / 1000
					} seconds...`
				);
				await new Promise( ( resolve ) =>
					setTimeout( resolve, waitTime )
				);
			} else {
				console.error(
					`Error fetching data from Google Sheets: ${ error.message }`
				);
				throw error;
			}
		}
	}
}

// Function to create a GitHub pull request to skip the test
async function createGitHubPullRequest( data ) {
	const branchName = `skip-flaky-test-${ data.id }`;
	const commitMessage = `Skip flaky test: ${ data.name }`;
	const prTitle = `[Automated] Skip flaky test: ${ data.name }`;
	const prBody = `This pull request skips the flaky test \`${ data.name }\` located at \`${ data.location }\`.`;

	try {
		// Get the reference for the trunk branch
		const { data: trunkRef } = await octokit.git.getRef( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			ref: 'heads/trunk',
		} );

		// Get the latest commit on the trunk branch
		const { data: trunkCommit } = await octokit.git.getCommit( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			commit_sha: trunkRef.object.sha,
		} );

		// Check if the branch already exists
		let branchExists = false;
		try {
			await octokit.git.getRef( {
				owner: process.env.GITHUB_REPO_OWNER,
				repo: process.env.GITHUB_REPO_NAME,
				ref: `heads/${ branchName }`,
			} );
			branchExists = true;
		} catch ( error ) {
			if ( error.status !== 404 ) {
				throw error;
			}
		}

		// Create a new branch if it doesn't exist
		if ( ! branchExists ) {
			await octokit.git.createRef( {
				owner: process.env.GITHUB_REPO_OWNER,
				repo: process.env.GITHUB_REPO_NAME,
				ref: `refs/heads/${ branchName }`,
				sha: trunkRef.object.sha,
			} );
		}

		// Extract the file path from data.location (ignoring line and column numbers)
		const filePath = `plugins/woocommerce/${
			data.location.split( ':' )[ 0 ]
		}`;
		console.log( `Fetching file content from path: ${ filePath }` );

		// Fetch the file content
		const { data: fileContent } = await octokit.repos.getContent( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			path: filePath,
			ref: trunkRef.object.sha,
		} );

		// Decode the file content from base64
		const content = Buffer.from( fileContent.content, 'base64' ).toString(
			'utf8'
		);
		// console.log(`Original content:\n${content}`);

		// Modify the content to skip the test or describe block
		const modifiedContent = content
			.replace(
				new RegExp(
					`test.describe\\(\\s*['"\`]${ data.name }['"\`]\\s*,`
				),
				`test.describe.skip( '${ data.name }', `
			)
			.replace(
				new RegExp(
					`test\\(\\s*['"\`]${ data.name }['"\`]\\s*,\\s*async\\s*\\(`
				),
				`test.skip( '${ data.name }', async (`
			);

		// console.log(`Modified content:\n${modifiedContent}`);

		// Create a new blob with the modified content
		const { data: newBlob } = await octokit.git.createBlob( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			content: Buffer.from( modifiedContent ).toString( 'base64' ),
			encoding: 'base64',
		} );

		// console.log(`New blob created with SHA: ${newBlob.sha}`);

		// Generate a unique name for the changelog file
		const changelogFileName = `changelog-${ data.id }`;
		const changelogFilePath = `plugins/woocommerce/changelog/${ changelogFileName }`;

		// Create the content for the changelog file
		const changelogContent = `Significance: patch\nType: dev\n\nSkipped flaky test: ${ data.name }`;
		// console.log(`Changelog content:\n${changelogContent}`);

		// Create a new blob for the changelog file
		const { data: changelogBlob } = await octokit.git.createBlob( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			content: Buffer.from( changelogContent ).toString( 'base64' ),
			encoding: 'base64',
		} );

		// console.log(`Changelog blob created with SHA: ${changelogBlob.sha}`);

		// Create a new tree with the updated blob and the changelog file
		const { data: newTree } = await octokit.git.createTree( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			base_tree: trunkCommit.tree.sha,
			tree: [
				{
					path: filePath,
					mode: '100644',
					type: 'blob',
					sha: newBlob.sha,
				},
				{
					path: changelogFilePath,
					mode: '100644',
					type: 'blob',
					sha: changelogBlob.sha,
				},
			],
		} );

		// console.log(`New tree created with SHA: ${newTree.sha}`);

		// Create a commit with the new tree
		const { data: newCommit } = await octokit.git.createCommit( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			message: commitMessage,
			tree: newTree.sha,
			parents: [ trunkRef.object.sha ],
		} );

		//console.log(`New commit created with SHA: ${newCommit.sha}`);

		// Force update the branch to point to the new commit
		await octokit.git.updateRef( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			ref: `heads/${ branchName }`,
			sha: newCommit.sha,
			force: true,
		} );

		// console.log(
		//   `Branch ${branchName} updated to new commit SHA: ${newCommit.sha}`
		// );

		// Check if a pull request already exists for the branch
		const { data: existingPRs } = await octokit.pulls.list( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			head: `${ process.env.GITHUB_REPO_OWNER }:${ branchName }`,
		} );

		if ( existingPRs.length > 0 ) {
			console.log(
				`Pull request already exists for branch ${ branchName }. URL: ${ existingPRs[ 0 ].html_url }`
			);
			return existingPRs[ 0 ].html_url;
		}

		// Create a pull request
		const { data: pr } = await octokit.pulls.create( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			title: prTitle,
			body: prBody,
			head: branchName,
			base: 'trunk',
		} );

		console.log( `Pull request created: ${ pr.html_url }` );

		// Request reviewers for the pull request
		await octokit.pulls.requestReviewers( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			pull_number: pr.number,
			reviewers: [ 'woocommerce/vortex' ], // Assign the reviewer also triggers Slack notification
		} );

		// console.log(`Reviewer woocommerce/vortex assigned to the pull request.`);

		// Add labels to the pull request
		await octokit.issues.addLabels( {
			owner: process.env.GITHUB_REPO_OWNER,
			repo: process.env.GITHUB_REPO_NAME,
			issue_number: pr.number,
			labels: [ 'focus: e2e tests', 'plugin: woocommerce' ],
		} );

		// console.log(
		//   `Labels "focus: e2e tests" and "plugin: woocommerce" added to the pull request.`
		// );

		return pr.html_url;
	} catch ( error ) {
		console.error( 'Error creating GitHub pull request:', error );
		throw error;
	}
}

// Function to create a GitHub issue
async function createGitHubIssue( data, pullRequestUrl ) {
	const issueTitle = `[Automated] Flaky Test: ${ data.name }`;
	const issueBody = `The test \`${ data.name }\` located at \`${ data.location }\` is flaky and has been skipped. Please fix the test.\n\nPull Request: ${ pullRequestUrl }\n\nFurther details: ${ data.web_url }`;

	try {
		const { data: issue } = await octokit.issues.create( {
			owner: GITHUB_REPO_OWNER,
			repo: GITHUB_REPO_NAME,
			title: issueTitle,
			body: issueBody,
			labels: [ 'focus: e2e tests', 'metric: flaky e2e test' ], // Assign the labels
		} );

		return issue.html_url;
	} catch ( error ) {
		console.error( 'Error creating GitHub issue:', error );
		throw error;
	}
}

// Function to update or append data to Google Sheets with exponential backoff
async function updateOrAppendToGoogleSheet( data ) {
	// Only proceed if data.instances is greater than or equal to the flaky threshold
	if ( data.instances < FLAKY_THRESHOLD ) {
		console.log(
			`Skipping record with ID ${ data.id } because instances are less than ${ FLAKY_THRESHOLD }.`
		);
		return;
	}

	const authClient = await auth.getClient();
	const spreadsheetId = GOOGLE_SHEET_ID;

	// Fetch existing data from the sheet
	const sheetData =
		( await fetchSheetData(
			authClient,
			spreadsheetId,
			'Flaky Tests!A:L'
		) ) || [];

	// Find the row index with the matching ID
	let rowIndex = -1;
	let existingIssueUrl = null;
	for ( let i = 1; i < sheetData.length; i++ ) {
		// Start from 1 to skip the header row
		if ( sheetData[ i ][ 0 ] === data.id ) {
			rowIndex = i + 1; // +1 to account for 1-based index in Google Sheets
			existingIssueUrl = sheetData[ i ][ 10 ]; // Column K is the 11th column (0-based index)
			break;
		}
	}

	// Determine the value for the status column
	let status = '';
	if ( data.instances >= FLAKY_THRESHOLD ) {
		status = 'skipped';
	} else {
		status = 'clear'; // these aren't written to the sheet, but are still logged in the console
	}

	// Prepare the data to write
	const values = [
		[
			data.id,
			data.web_url,
			data.scope,
			data.name,
			data.location,
			data.file_name,
			data.instances,
			data.latest_occurrence_at,
			data.most_recent_instance_at,
			status,
		],
	];

	const sheets = google.sheets( { version: 'v4', auth: authClient } );

	let retryCount = 0;
	const maxRetries = 5;

	while ( retryCount < maxRetries ) {
		try {
			if ( rowIndex !== -1 ) {
				// Update the existing row
				const updateRequest = {
					spreadsheetId,
					range: `Flaky Tests!A${ rowIndex }:L${ rowIndex }`, // Adjust the range to match the number of columns
					valueInputOption: 'RAW',
					resource: {
						values: values,
					},
				};
				await sheets.spreadsheets.values.update( updateRequest );
				console.log( `Row with ID ${ data.id } updated successfully.` );
			} else {
				// Append as a new row
				const appendRequest = {
					spreadsheetId,
					range: 'Flaky Tests!A:L', // Adjust the range to match the number of columns
					valueInputOption: 'RAW',
					resource: {
						values: values,
					},
				};
				const appendResponse = await sheets.spreadsheets.values.append(
					appendRequest
				);
				rowIndex =
					appendResponse.data.updates.updatedRange.match(
						/(\d+)$/
					)[ 0 ]; // Get the new row index
				console.log(
					`Row with ID ${ data.id } appended successfully.`
				);
			}

			// Check if instances are >= 10 and create a GitHub pull request and issue if true and no existing URL
			if ( data.instances >= FLAKY_THRESHOLD && ! existingIssueUrl ) {
				const pullRequestUrl = await createGitHubPullRequest( data );
				const issueUrl = await createGitHubIssue(
					data,
					pullRequestUrl
				);

				// Update spreadsheet with issue URL and pull request URL
				const issueUrlRange = `Flaky Tests!K${ rowIndex }`; // Column K is where you want to log the issue URL
				const pullRequestUrlRange = `Flaky Tests!L${ rowIndex }`; // Column L is where you want to log the pull request URL
				const updateIssueUrlRequest = {
					spreadsheetId,
					range: issueUrlRange,
					valueInputOption: 'RAW',
					resource: {
						values: [ [ issueUrl ] ],
					},
				};
				const updatePullRequestUrlRequest = {
					spreadsheetId,
					range: pullRequestUrlRange,
					valueInputOption: 'RAW',
					resource: {
						values: [ [ pullRequestUrl ] ],
					},
				};
				await sheets.spreadsheets.values.update(
					updateIssueUrlRequest
				);
				await sheets.spreadsheets.values.update(
					updatePullRequestUrlRequest
				);
				console.log(
					`GitHub issue and pull request created and URLs logged for ID ${ data.id }.`
				);
			} else if ( existingIssueUrl ) {
				console.log(
					`GitHub issue already exists for ID ${ data.id }. URL: ${ existingIssueUrl }`
				);
			}

			break; // Exit loop if successful
		} catch ( error ) {
			if ( error.response && error.response.status === 429 ) {
				retryCount++;
				const waitTime = Math.pow( 2, retryCount ) * 1000; // Exponential backoff
				console.log(
					`Quota exceeded for write requests. Retrying in ${
						waitTime / 1000
					} seconds...`
				);
				await new Promise( ( resolve ) =>
					setTimeout( resolve, waitTime )
				);
			} else {
				console.error(
					`Error updating or appending to Google Sheets: ${ error.message }`
				);
				throw error;
			}
		}
	}
}

async function removeMissingRowsFromGoogleSheet( buildkiteData ) {
	const authClient = await auth.getClient();
	const spreadsheetId = GOOGLE_SHEET_ID;

	// Fetch existing data from the sheet
	const sheetData =
		( await fetchSheetData(
			authClient,
			spreadsheetId,
			'Flaky Tests!A:L'
		) ) || []; // Adjust the range to match the number of columns

	// Create a set of data.id values from the Buildkite data
	const buildkiteIds = new Set( buildkiteData.map( ( item ) => item.id ) );

	const sheets = google.sheets( { version: 'v4', auth: authClient } );

	let retryCount = 0;
	const maxRetries = 5;

	while ( retryCount < maxRetries ) {
		try {
			// Iterate through the existing Google Sheet data and remove rows where the data.id is not present in the Buildkite data
			for ( let i = 1; i < sheetData.length; i++ ) {
				// Start from 1 to skip the header row
				const sheetId = sheetData[ i ][ 0 ];
				if ( ! buildkiteIds.has( sheetId ) ) {
					const deleteRequest = {
						spreadsheetId,
						range: `Flaky Tests!A${ i + 1 }:L${ i + 1 }`, // Adjust the range to match the number of columns
					};
					await sheets.spreadsheets.values.clear( deleteRequest );
					console.log(
						`Row with ID ${ sheetId } removed successfully.`
					);
				}
			}
			break; // Exit loop if successful
		} catch ( error ) {
			if ( error.response && error.response.status === 429 ) {
				retryCount++;
				const waitTime = Math.pow( 2, retryCount ) * 1000; // Exponential backoff
				console.log(
					`Quota exceeded for write requests. Retrying in ${
						waitTime / 1000
					} seconds...`
				);
				await new Promise( ( resolve ) =>
					setTimeout( resolve, waitTime )
				);
			} else {
				console.error(
					`Error removing rows from Google Sheets: ${ error.message }`
				);
				throw error;
			}
		}
	}
}

async function performTask() {
	try {
		// Fetch the data from Buildkite
		const buildkiteData = await fetchBuildkiteData();

		// Assuming buildkiteData is an array of objects, iterate over each object and update/append to Google Sheets
		for ( const data of buildkiteData ) {
			await updateOrAppendToGoogleSheet( data );
		}

		// Remove rows from Google Sheets if the data.id is no longer present in the Buildkite data
		await removeMissingRowsFromGoogleSheet( buildkiteData );
	} catch ( error ) {
		console.error( 'Error performing task:', error );
	}
}

performTask();
