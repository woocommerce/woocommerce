/**
 * External dependencies
 */
import { Repository } from '@octokit/graphql-schema';
import { Endpoints } from '@octokit/types';

/**
 * Internal dependencies
 */
import { graphqlWithAuth, octokitWithAuth } from './api';
import {
	CreatePullRequestEndpointResponse,
	GetPullRequestEndpointResponse,
} from './types';

export const getLatestGithubReleaseVersion = async ( options: {
	owner?: string;
	name?: string;
} ): Promise< string > => {
	const { owner, name } = options;

	const data = await graphqlWithAuth()< { repository: Repository } >( `
			{
			    repository(owner: "${ owner }", name: "${ name }") {
					releases(
						first: 25
						orderBy: { field: CREATED_AT, direction: DESC }
					) {
						nodes {
							tagName
							isLatest
						}
					}
				}
			}
		` );

	return data.repository.releases.nodes.find(
		( tagName ) => tagName.isLatest
	).tagName;
};

export const getRepositoryLabel = async (
	options: {
		owner?: string;
		name?: string;
	},
	label: string
): Promise<
	Endpoints[ 'GET /repos/{owner}/{repo}/labels/{name}' ][ 'response' ][ 'data' ]
> => {
	const { owner, name } = options;
	try {
		const { data } = await octokitWithAuth().request(
			'GET /repos/{owner}/{repo}/labels/{label}',
			{
				owner,
				repo: name,
				label,
			}
		);
		return data;
	} catch ( e ) {
		throw new Error( e );
	}
};

export const getIssuesByLabel = async (
	options: {
		owner?: string;
		name?: string;
		pageSize?: number;
	},
	label: string,
	state: 'open' | 'closed' | 'all' = 'open'
): Promise< {
	results: Endpoints[ 'GET /repos/{owner}/{repo}/issues' ][ 'response' ][ 'data' ];
} > => {
	const { owner, name, pageSize } = options;

	try {
		const { data } = await octokitWithAuth().request(
			'GET /repos/{owner}/{repo}/issues{?labels,state}',
			{
				owner,
				repo: name,
				labels: label,
				per_page: pageSize || 100,
				state,
			}
		);
		return {
			results: data,
		};
	} catch ( e ) {
		throw new Error( e );
	}
};

export const updateIssue = async (
	options: {
		owner?: string;
		name?: string;
	},
	issueNumber: number,
	updates: {
		labels?: string[];
	}
): Promise<
	| Endpoints[ 'PATCH /repos/{owner}/{repo}/issues/{issue_number}' ][ 'response' ]
	| false
> => {
	const { owner, name } = options;

	try {
		const branchOnGithub = await octokitWithAuth().request(
			'PATCH /repos/{owner}/{repo}/issues/{issue_number}',
			{
				owner,
				repo: name,
				issue_number: issueNumber,
				...updates,
			}
		);
		return branchOnGithub;
	} catch ( e ) {
		if (
			e.status === 404 &&
			e.response.data.message === 'Issue not found'
		) {
			return false;
		}
		throw new Error( e );
	}
};

export const doesGithubBranchExist = async (
	options: {
		owner?: string;
		name?: string;
	},
	nextReleaseBranch: string
): Promise< boolean > => {
	const { owner, name } = options;

	try {
		const branchOnGithub = await octokitWithAuth().request(
			'GET /repos/{owner}/{repo}/branches/{branch}',
			{
				owner,
				repo: name,
				branch: nextReleaseBranch,
			}
		);
		return branchOnGithub.data.name === nextReleaseBranch;
	} catch ( e ) {
		if (
			e.status === 404 &&
			e.response.data.message === 'Branch not found'
		) {
			return false;
		}
		throw new Error( e );
	}
};

export const getRefFromGithubBranch = async (
	options: {
		owner?: string;
		name?: string;
	},
	source: string
): Promise< string > => {
	const { owner, name } = options;
	const { repository } = await graphqlWithAuth()< {
		repository: Repository;
	} >( `
			{
			    repository(owner:"${ owner }", name:"${ name }") {
					ref(qualifiedName: "refs/heads/${ source }") {
						target {
						  ... on Commit {
							  history(first: 1) {
								edges{ node{ oid } }
							  }
						  }
						}
					}
				  }
			}
		` );

	// @ts-expect-error: The graphql query is typed, but the response is not.
	return repository.ref.target.history.edges.shift().node.oid;
};

export const createGithubBranch = async (
	options: {
		owner?: string;
		name?: string;
	},
	branch: string,
	ref: string
): Promise< void > => {
	const { owner, name } = options;
	await octokitWithAuth().request( 'POST /repos/{owner}/{repo}/git/refs', {
		owner,
		repo: name,
		ref: `refs/heads/${ branch }`,
		sha: ref,
	} );
};

export const deleteGithubBranch = async (
	options: {
		owner?: string;
		name?: string;
	},
	branch: string
): Promise< void > => {
	const { owner, name } = options;
	await octokitWithAuth().request(
		'DELETE /repos/{owner}/{repo}/git/refs/heads/{ref}',
		{
			owner,
			repo: name,
			ref: branch,
		}
	);
};

/**
 * Create a pull request from branches on Github.
 *
 * @param {Object} options       pull request options.
 * @param {string} options.head  branch name containing the changes you want to merge.
 * @param {string} options.base  branch name you want the changes pulled into.
 * @param {string} options.owner repository owner.
 * @param {string} options.name  repository name.
 * @param {string} options.title pull request title.
 * @param {string} options.body  pull request body.
 * @return {Promise<object>}     pull request data.
 */
export const createPullRequest = async ( options: {
	head: string;
	base: string;
	owner: string;
	name: string;
	title: string;
	body: string;
} ): Promise< CreatePullRequestEndpointResponse[ 'data' ] > => {
	const { head, base, owner, name, title, body } = options;
	const pullRequest = await octokitWithAuth().request(
		'POST /repos/{owner}/{repo}/pulls',
		{
			owner,
			repo: name,
			title,
			body,
			head,
			base,
		}
	);

	return pullRequest.data;
};

/**
 * Get a pull request from GitHub.
 *
 * @param {Object} options
 * @param {string} options.owner    repository owner.
 * @param {string} options.name     repository name.
 * @param {string} options.prNumber pull request number.
 * @return {Promise<object>}     pull request data.
 */
export const getPullRequest = async ( options: {
	owner: string;
	name: string;
	prNumber: string;
} ): Promise< GetPullRequestEndpointResponse[ 'data' ] > => {
	const { owner, name, prNumber } = options;
	const pr = await octokitWithAuth().request(
		'GET /repos/{owner}/{repo}/pulls/{pull_number}',
		{
			owner,
			repo: name,
			pull_number: Number( prNumber ),
		}
	);

	return pr.data;
};

/**
 * Determine if a pull request is coming from a community contribution, i.e., not from a member of the WooCommerce organization.
 *
 * @param {Object} pullRequestData pull request data.
 * @param {string} owner           repository owner.
 * @param {string} name            repository name.
 * @return {boolean} if a pull request is coming from a community contribution.
 */
export const isCommunityPullRequest = (
	pullRequestData: GetPullRequestEndpointResponse[ 'data' ],
	owner: string,
	name: string
) => {
	// We can't use author_association here because it can be changed by PR authors. Instead check PR source.
	return pullRequestData.head.repo.full_name !== `${ owner }/${ name }`;
};
