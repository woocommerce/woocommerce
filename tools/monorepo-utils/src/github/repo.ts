/**
 * External dependencies
 */
import { Repository } from '@octokit/graphql-schema';

/**
 * Internal dependencies
 */
import { graphqlWithAuth, octokitWithAuth } from './api';

export const getLatestReleaseVersion = async ( options: {
	owner?: string;
	name?: string;
} ): Promise< string > => {
	const { owner, name } = options;

	const data = await graphqlWithAuth< { repository: Repository } >( `
			{
			    repository(owner: "${ owner }", name: "${ name }") {
					releases(
						first: 25
						orderBy: { field: CREATED_AT, direction: DESC }
					) {
						nodes {
							tagName
						}
					}
				}
			}
		` );

	return data.repository.releases.nodes.find(
		( tagName ) => tagName.isLatest
	).tagName;
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
		const branchOnGithub = await octokitWithAuth.request(
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
	const { repository } = await graphqlWithAuth< {
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

	// @ts-ignore: The graphql query is typed, but the response is not.
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
	await octokitWithAuth.request( 'POST /repos/{owner}/{repo}/git/refs', {
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
	await octokitWithAuth.request(
		'DELETE /repos/{owner}/{repo}/git/refs/heads/{ref}',
		{
			owner,
			repo: name,
			ref: branch,
		}
	);
};
