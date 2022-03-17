/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { graphql, GraphqlResponseError } from '@octokit/graphql';

/**
 * Make sure we aren't hardcoding the monorepo into the command.
 */
const MONOREPO_OWNER = 'woocommerce';
const MONOREPO_NAME = 'woocommerce';

/**
 * Describes the label object containing the label name as the key
 * and the GitHub node ID as the value.
 */
interface GitHubLabels {
	[ name: string ]: string;
}

/**
 * Describes the issue object containing the issue ID
 * as the key and the title as the value.
 */
interface GitHubIssue {
	id: string;
	title: string;
}

export default class TransferIssues extends Command {
	static description =
		'Transfers issues from another repository into the monorepo.';

	static args = [
		{
			name: 'source',
			description: 'The GitHub repository we are transferring from.',
			required: true,
		},
	];

	static flags = {
		searchFilter: Flags.string( {
			description:
				'The search filter to apply when searching for issues to transfer.',
			default: 'is:open',
		} ),
		labels: Flags.string( {
			description:
				'A comma-delimited list of labels that should be added to the issue post-migration.',
		} ),
	};

	/**
	 * This method is called to execute the command.
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( TransferIssues );

		let confirmation = await CliUx.ux.confirm(
			'Are you sure you want to transfer issues from ' +
				args.source +
				' into the monorepo? (y/n)'
		);
		if ( ! confirmation ) {
			this.exit( 0 );
		}

		const authenticatedGraphQL = await this.authenticateGraphQL();

		const numberOfIssues = await this.getNumberOfIssues(
			authenticatedGraphQL,
			args.source,
			flags.searchFilter
		);
		confirmation = await CliUx.ux.confirm(
			'This will transfer ' +
				numberOfIssues +
				' issues. There is no command to reverse this, are you sure? (y/n)'
		);
		if ( ! confirmation ) {
			this.exit( 0 );
		}

		const monorepoNodeID = await this.getMonorepoNodeID(
			authenticatedGraphQL
		);
		const labelsToAdd = await this.getLabelsToAdd(
			authenticatedGraphQL,
			flags.labels
		);
		const issuesToTransfer = await this.getIssues(
			authenticatedGraphQL,
			args.source,
			flags.searchFilter
		);

		let transferredIssues = 0;
		for ( const issue of issuesToTransfer ) {
			const success = await this.transferIssue(
				authenticatedGraphQL,
				issue,
				monorepoNodeID,
				labelsToAdd
			);

			if ( success ) {
				transferredIssues++;
			}
		}

		this.log(
			'Successfully transferred ' +
				transferredIssues +
				'/' +
				numberOfIssues +
				' issues.'
		);
	}

	/**
	 * Requests a token and verifies that it can be used to query the API.
	 */
	private async authenticateGraphQL(): Promise< typeof graphql > {
		// Prompt them for a token, rather than storing one. This reduces the likelihood that the command can be accidentally executed.
		const token: string = await CliUx.ux.prompt(
			'Please supply a GitHub API token',
			{ type: 'hide', required: true }
		);
		if ( token === '' ) {
			this.error( 'You must enter a valid GitHub API token' );
		}

		CliUx.ux.action.start( 'Validating GitHub API token' );

		const authenticatedGraphQL = graphql.defaults( {
			headers: {
				authorization: 'token ' + token,
			},
		} );

		try {
			await authenticatedGraphQL( '{ viewer { id } }' );
		} catch ( err: any ) {
			if ( err?.status === 401 ) {
				this.error( 'The given token is invalid' );
			}

			throw err;
		} finally {
			CliUx.ux.action.stop();
		}

		return authenticatedGraphQL;
	}

	/**
	 * Fetches the node ID of the monorepo from GitHub.
	 *
	 * @param {graphql} authenticatedGraphQL The graphql object for making requests.
	 */
	private async getMonorepoNodeID(
		authenticatedGraphQL: typeof graphql
	): Promise< string > {
		CliUx.ux.action.start( 'Finding Monorepo' );

		try {
			const { repository } = await authenticatedGraphQL(
				`
                query ( $monorepoOwner: String!, $monorepoName: String! ) {
                    repository ( owner: $monorepoOwner, name: $monorepoName ) {
                        id,
                        nameWithOwner
                    }
                }
                `,
				{
					monorepoOwner: MONOREPO_OWNER,
					monorepoName: MONOREPO_NAME,
				}
			);

			CliUx.ux.action.stop();

			return repository.id;
		} catch ( err ) {
			CliUx.ux.action.stop();

			if ( err instanceof GraphqlResponseError ) {
				this.error(
					'Could not find the repository "' +
						MONOREPO_OWNER +
						'/' +
						MONOREPO_NAME +
						'"'
				);
			}

			throw err;
		} finally {
			CliUx.ux.action.stop();
		}
	}

	/**
	 * Gets all of the labels we want to add from GitHub.
	 *
	 * @param {graphql} authenticatedGraphQL The graphql object for making requests.
	 * @param {Array.<string>} labels The labels we want to add after the transfer.
	 */
	private async getLabelsToAdd(
		authenticatedGraphQL: typeof graphql,
		labels?: string
	): Promise< GitHubLabels > {
		if ( ! labels ) {
			return {};
		}
		const addLabels = labels.split( ',' );

		CliUx.ux.action.start( 'Getting labels to add' );

		// Gather all of the labels from the monorepo so that
		// we can validate the labels we want to add and
		// get the IDs of them to assign on transfer.
		const allLabels: GitHubLabels = {};
		let cursor: string | null = null;
		do {
			const { repository } = await authenticatedGraphQL(
				`
                query ( $monorepoOwner: String!, $monorepoName: String!, $cursor: String ) {
                    repository ( owner: $monorepoOwner, name: $monorepoName ) {
                        labels ( first: 100, after: $cursor ) {
                            nodes {
                                id,
                                name
                            },
                            pageInfo {
                                hasNextPage,
                                endCursor
                            }
                        }
                    }
                }
                `,
				{
					monorepoOwner: MONOREPO_OWNER,
					monorepoName: MONOREPO_NAME,
					cursor,
				}
			);

			// Record all of the labels so that we can scan for the ones we are adding.
			for ( const label of repository.labels.nodes ) {
				allLabels[ label.name ] = label.id;
			}

			// Continue following the cursor until we have no labels left to get.
			if ( repository.labels.pageInfo.hasNextPage ) {
				cursor = repository.labels.pageInfo.endCursor as string;
			} else {
				cursor = null;
			}
		} while ( cursor !== null );

		// Find all of the labels we are going to add to the issues after the transfer.
		const gitHubLabels: GitHubLabels = {};
		for ( const label of addLabels ) {
			if ( ! allLabels[ label ] ) {
				this.error(
					'The monorepo does not have the label ' + label + '.'
				);
			}

			gitHubLabels[ label ] = allLabels[ label ];
		}

		CliUx.ux.action.stop();

		return gitHubLabels;
	}

	/**
	 * Gets the number of issues that this command is going to migrate.
	 *
	 * @param {graphql} authenticatedGraphQL The graphql object for making requests.
	 * @param {string} source The repository to transfer issues from.
	 * @param {string} searchFilter The GitHub search filters for the issues to transfer.
	 */
	private async getNumberOfIssues(
		authenticatedGraphQL: typeof graphql,
		source: string,
		searchFilter: string
	): Promise< number > {
		const searchQuery = 'repo:' + source + ' is:issue ' + searchFilter;

		const { search } = await authenticatedGraphQL(
			`
            query ($searchQuery: String!) {
                search ( 
                    type: ISSUE, 
                    query: $searchQuery,
                    first: 0
                ) {
                    issueCount,
                }
            }
            `,
			{ searchQuery }
		);

		return search.issueCount;
	}

	/**
	 * Gets all of the issues that we are going to transfer into the monorepo.
	 *
	 * @param {graphql} authenticatedGraphQL The graphql object for making requests.
	 * @param {string} source The repository to transfer issues from.
	 * @param {string} searchFilter The GitHub search filters for the issues to transfer.
	 */
	private async getIssues(
		authenticatedGraphQL: typeof graphql,
		source: string,
		searchFilter: string
	): Promise< GitHubIssue[] > {
		const searchQuery = 'repo:' + source + ' is:issue ' + searchFilter;

		const issues: GitHubIssue[] = [];
		let cursor: string | null = null;
		do {
			const { search } = await authenticatedGraphQL(
				`
                query ($searchQuery: String!, $cursor: String) {
                    search ( 
                        type: ISSUE, 
                        query: $searchQuery,
                        first: 100,
                        after: $cursor
                    ) {
                        nodes {
                            ... on Issue {
                                id,
                                title
                            }
                        },
                        pageInfo {
                            hasNextPage,
                            endCursor
                        }
                    }
                }
                `,
				{
					searchQuery,
					cursor,
				}
			);

			// Record all of the issues that we've found
			for ( const issue of search.nodes ) {
				issues.push( {
					id: issue.id,
					title: issue.title,
				} );
			}

			// Continue following the cursor until we have no issues left to get.
			if ( search.pageInfo.hasNextPage ) {
				cursor = search.pageInfo.endCursor as string;
			} else {
				cursor = null;
			}
		} while ( cursor !== null );

		return issues;
	}

	/**
	 * Transfers an issue into the monorepo.
	 *
	 * @param {graphql} authenticatedGraphQL The graphql object for making requests.
	 * @param {GitHubIssue} issue The issue that we are going to transfer.
	 * @param {string} monorepoNodeID The global node ID of the monorepo.
	 * @param {GitHubLabels} labelsToAdd The labels we want to apply after the transfer.
	 */
	private async transferIssue(
		authenticatedGraphQL: typeof graphql,
		issue: GitHubIssue,
		monorepoNodeID: string,
		labelsToAdd: GitHubLabels
	): Promise< boolean > {
		CliUx.ux.action.start( 'Transferring "' + issue.title + '"' );

		CliUx.ux.action.stop();
		return true;
	}
}
