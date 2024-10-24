/**
 * External dependencies
 */
import { Argument, Command } from '@commander-js/extra-typings';
import { exit } from 'process';
import { error } from 'console';

/**
 * Internal dependencies
 */
import { graphqlWithAuth } from '../../core/github/api';
import { Logger } from '../../core/logger';

const { log } = console;

const run = async (
	prNumberOrUrl: string,
	significance: string,
	type: string,
	changelogMessageWords: string[],
	options: {
		owner: string;
		name: string;
		createModifyCommit: true;
		dryRun: true;
	}
) => {
	// Step 1: Process/validate arguments

	if ( significance === null ) {
		error( 'Error: Invalid significance code' );
		exit( 1 );
	}
	if ( type === null ) {
		error( 'Error: Invalid type code' );
		exit( 1 );
	}

	const prNumberMatch = prNumberOrUrl.match( /\d+$/ );
	if ( prNumberMatch === null ) {
		error( 'Error: Invalid pull request URL or number' );
		exit( 1 );
	}

	const prNumber = parseInt( prNumberMatch[ 0 ], 10 );
	const changelogFileContents = `Significance: ${ significance }\nType: ${ type }\n\n${ changelogMessageWords.join(
		' '
	) }\n`;

	// Step 2: Query GitHub API for the pull request details

	const gql = graphqlWithAuth();

	let prData: any = await gql(
		`
query($pr_number: Int!) { 
	repository(owner: "${ options.owner }", name: "${ options.name }") {
		pullRequest(number: $pr_number) {
			title
			url
			author {
				login
			}
			state
			headRef {
				name
				prefix
			}
			headRefOid
			headRepository {
				owner {
					login
				}
				name
			}
		}
	}
}`,
		{ pr_number: prNumber }
	);

	prData = prData.repository.pullRequest;

	log( `
PR url: ${ prData.url }
Title:  ${ prData.title }
Author: ${ prData.author.login }
` );

	if ( prData.state !== 'OPEN' ) {
		error(
			`Error: this pull request isn't open (state: ${ prData.state })`
		);
		exit( 1 );
	}

	// Step 3: Check if the changelog file already exists

	const changelogFileData: any = await gql( `
query {
	repository(owner: "${ options.owner }", name: "${ options.name }") {
		object(expression: "${ prData.headRef.name }:plugins/woocommerce/changelog/pr-${ prNumber }") {
			... on Blob {
				text
			}
		}
	}
}` );

	let fileAlreadyExists = false;
	if ( changelogFileData.repository.object !== null ) {
		fileAlreadyExists = true;
		const existingFileContents: string =
			changelogFileData.repository.object.text.trim();
		Logger.warn(
			`File 'plugins/woocommerce/changelog/pr-${ prNumber }' already exists with this content:\n`
		);
		log( '-------\n' + existingFileContents + '\n-------\n' );

		if ( ! options.createModifyCommit ) {
			if ( existingFileContents === changelogFileContents.trim() ) {
				Logger.warn(
					'The provided file contents is identical to the existing file contents, nothing to be done.'
				);
			}
			log( 'Run with -c to create a commit that modifies the file.\n' );
			exit( 0 );
		}
	}

	// Step 4: Request the GitHub API to create the commit
	//         (or just simulate the request if dry run requested)

	const mutationCommand = `
mutation ($input: CreateCommitOnBranchInput!) {
	createCommitOnBranch(input: $input) { commit { url } }
}`;

	const mutationData = {
		input: {
			branch: {
				repositoryNameWithOwner: `${ prData.headRepository.owner.login }/${ prData.headRepository.name }`,
				branchName: prData.headRef.name,
			},
			message: {
				headline: fileAlreadyExists
					? 'Modify changelog file'
					: 'Add changelog file',
			},
			fileChanges: {
				additions: [
					{
						path: `plugins/woocommerce/changelog/pr-${ prNumber }`,
						contents: btoa( changelogFileContents ),
					},
				],
			},
			expectedHeadOid: prData.headRefOid,
		},
	};

	if ( options.dryRun ) {
		log( 'Dry run, this is what would be sent to the GitHub API:\n' );
		log(
			JSON.stringify(
				{
					query: mutationCommand.trim().replace( /[\n\t]+/g, ' ' ),
					variables: mutationData,
				},
				null,
				2
			) + '\n'
		);
		log(
			'Changelog file contents (encoded as Base64 in "contents"):\n\n-------\n' +
				changelogFileContents.trim() +
				'\n-------\n'
		);
		exit( 0 );
	}

	const mutationResult: any = await gql( mutationCommand, mutationData );

	Logger.notice( 'Success!' );
	log(
		'Commit URL:\n' + mutationResult.createCommitOnBranch.commit.url + '\n'
	);

	exit( 0 );
};

// Command declaration

const commandSummary = `Adds a changelog file to a pull request.`;

const commandDescription = `
${ commandSummary }
Uses the GitHub REST API, no need to keep a local copy of the repository.
GITHUB_TOKEN environment variable is required.

Significances:

    patch or p:  Backwards-compatible bug fixes
    minor or m:  Added (or deprecated) functionality in a backwards-compatible manner
    major or j:  Broke backwards compatibility in some way

Types:

    fix or f:         Fixes an existing bug
    add or a:         Adds functionality
    update or u:      Update existing functionality
    dev or d:         Development related task
    tweak or t:       A minor adjustment to the codebase
    performance or p: Address performance issues
    enhancement or e: Improve existing functionality

Example:

    GITHUB_TOKEN=$(cat ~/my_github_key) pnpm utils pull-request add-changelog-file 12345 j a Add compatibility with Nextor
`;

const types = [
	'fix',
	'add',
	'update',
	'dev',
	'tweak',
	'performance',
	'enhancement',
];

const significances = {
	p: 'patch',
	m: 'minor',
	j: 'major',
};

const significanceValues = Object.values( significances );

export const addChangelogFileCommand = new Command( 'add-changelog-file' )
	.summary( commandSummary )
	.description( commandDescription.trim() )
	.argument( '<pr-number>', 'Pull request numbers' )
	.addArgument(
		new Argument(
			'<significance>',
			'Significance, first letter is enough ("j" for "major").'
		)
			.choices( significanceValues )
			.argParser( ( x ) =>
				significanceValues.includes( x )
					? x
					: significances[ x ] ?? null
			)
	)
	.addArgument(
		new Argument( '<type>', 'Type, first letter is enough.' )
			.choices( types )
			.argParser( ( x ) =>
				types.includes( x )
					? x
					: types.find( ( t ) => t[ 0 ] === x[ 0 ] ) ?? null
			)
	)
	.argument(
		'<message words...>',
		'Changelog file message, enclosing in quotes is not needed.'
	)
	.option( '-o --owner <owner>', 'Repository owner.', 'woocommerce' )
	.option( '-n --name <name>', 'Repository name.', 'woocommerce' )
	.option(
		'-c --create-modify-commit',
		'If the changelog file already exists, create a commit to modify it.',
		false
	)
	.option(
		'-d --dry-run',
		"Don't actually create/modify the changelog file, only show what would be done.",
		false
	)
	.action( run );
