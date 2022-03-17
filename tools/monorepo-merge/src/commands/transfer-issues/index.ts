 /**
  * External dependencies
  */
 import { CliUx, Command, Flags } from '@oclif/core';
import { graphql, GraphqlResponseError } from '@octokit/graphql';

/**
 * Describes the information for a user that the command needs to operate.
 */
interface APIUser {
    id: string;
    monorepoID: string;
    token: string;
}

/**
 * Describes the results from an issue lookup.
 */
interface IssueResults {
    totalIssues: number,
    cursor: string,
    issues: { id: string, title: string }[];
}
 
 export default class TransferIssues extends Command {
     static description = 'Transfers issues from another repository into the monorepo.';
 
     static args = [
         {
             name: 'source',
             description: 'The GitHub repository we are transferring from.',
             required: true,
         }
     ];

     static flags = {
		filter: Flags.string( 
			{ 
				description: 'A search filter to apply when searching for issues to transfer.',
				default: 'is:open' 
			} 
		)
	};
 
     /**
      * This method is called to execute the command.
      */
     async run(): Promise< void > {
        const { args, flags } = await this.parse( TransferIssues );

        this.validateArgs( args.source );

        const apiUser = await this.getAPIUser();

        let confirmation = await CliUx.ux.confirm('Are you sure you want to transfer issues from ' + args.source + ' into the monorepo? (y/n)' );
        if (!confirmation) {
            this.exit( 0 );
        }

        confirmation = await CliUx.ux.confirm('This command CANNOT reverse the transfer, are you sure? (y/n)' );
        if (!confirmation) {
            this.exit( 0 );
        }

         // Iterate over all of the issues and transfer them to the monorepo.
         let cursor: string | null = null;
         let totalTransferred = 0;
         let totalIssues = null;
         do {
            const issues: IssueResults = await this.loadIssues( apiUser, args.source, flags.filter, cursor );
            if (issues.issues.length === 0) {
                break;
            }

            if (totalIssues === null) {
                totalIssues = issues.totalIssues;

                confirmation = await CliUx.ux.confirm('This will transfer ' + totalIssues + ' issues, are you sure? (y/n)' );
                if (!confirmation) {
                    this.exit( 0 );
                }
            }

            totalTransferred += await this.transferIssues(apiUser, issues);
            cursor = issues.cursor;
         } while (cursor !== null) {}

         this.log( 'Successfully transferred ' + totalTransferred + '/' + totalIssues + ' issues.' );
     }

    /**
	 * Validates all of the arguments to make sure they're compatible with the command.
	 * 
	 * @param {string} source The GitHub repository we are transferring from.
	 */
	private validateArgs( source: string ): void {
		// We only support pulling from GitHub so the format needs to match that.
		if ( ! source.match( /^[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_]+$/ ) ) {
			this.error(
				'The "source" argument must be in "organization/repository" format'
			);
		}
	}

    /**
     * Requests an API token from the user, validates it, and returns information about them if successful.
     */
    private async getAPIUser(): Promise< APIUser > {
        // Prompt them for a token, rather than storing one. This reduces the likelihood that the command can be accidentally executed.
        const token: string = await CliUx.ux.prompt( 'Please supply a GitHub API token', { type: 'hide', required: true });
        if (token === '') {
            this.error( 'You must enter a valid GitHub API token' );
        }

        try {
            const { viewer } = await graphql(
                '{ viewer { id } }',
                {
                    headers: {
                        authorization: 'token ' + token
                    }
                }
            );

            const { repository } = await graphql(
                '{ repository (owner: "woocommerce", name: "woocommerce" ) { id } }',
                {
                    headers: {
                        authorization: 'token ' + token
                    }
                }
            );

            return { 
                id: viewer.id, 
                monorepoID: repository.id, 
                token 
            };
        } catch ( err: any ) {
            if ( err?.status == 401 ) {
                this.error( 'The given token is invalid' );
            }

            throw err;
        }
    }

    /**
     * Loads a set of issues from the 
     * 
     * @param {APIUser} apiUser The API user that is making the transfer request.
     * @param {string} source The GitHub repository we are transferring issues from.
     * @param {string} filter The search filter for the issue search.
     * @param {string|null} cursor The cursor for the current in-progress issue search.
     */
    private async loadIssues( apiUser: APIUser, source: string, filter: string, cursor: string | null ): Promise< IssueResults > {
        const cursorString = cursor ? ', after: "' + cursor + '"' : '';

        const { search } = await graphql(
            `
            {
                search(type: ISSUE, query: "repo:${source} is:issue ${filter}", first: 50${cursorString}) {
                    nodes {
                      ... on Issue {
                        id,
                        title
                      }
                    },
                    issueCount,
                    pageInfo {
                      endCursor
                    }
                  }
            }
            `,
            {
                headers: {
                    authorization: 'token ' + apiUser.token
                }
            }
        );

        const nextCursor = search.pageInfo.endCursor;
        const issues: { id: string, title: string }[] = [];
        for ( const issue of search.nodes ) {
            issues.push({
                id: issue.id,
                title: issue.title
            });
        }

        return {
            totalIssues: search.issueCount,
            cursor: nextCursor,
            issues: issues
        };
    }

    /**
     * Transfers a set of issues to the monorepo.
     * 
     * @param {APIUser} apiUser The API user making the transfer request.
     * @param {IssueResult} issues The issues to be transferred to the monorepo.
     */
    private async transferIssues( apiUser: APIUser, issues: IssueResults ): Promise< number > {
        // Track the number of issues so that we can keep up with them.
        let issuesTransferred = 0;
        for (const issue of issues.issues) {
            CliUx.ux.action.start( 'Transferring "' + issue.title + '"' );

            issuesTransferred++;
            CliUx.ux.action.stop();
        }

        return issuesTransferred;
    }
 }
 