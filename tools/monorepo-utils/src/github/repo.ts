/**
 * External dependencies
 */
import { Repository } from '@octokit/graphql-schema';

/**
 * Internal dependencies
 */
import { graphqlWithAuth } from './api';

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
