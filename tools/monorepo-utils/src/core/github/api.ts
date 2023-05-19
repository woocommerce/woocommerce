/**
 * External dependencies
 */
import { graphql as gql } from '@octokit/graphql';
import { Octokit } from 'octokit';
import { graphql } from '@octokit/graphql/dist-types/types';

/**
 * Internal dependencies
 */
import { getEnvVar } from '../environment';

let graphqlWithAuthInstance;
let octokitWithAuthInstance;

/**
 * Returns a graphql instance with auth headers, throws an Exception if
 * `GITHUB_TOKEN` env var is not present.
 *
 * @return graphql instance
 */
export const graphqlWithAuth = (): graphql => {
	if ( graphqlWithAuthInstance ) {
		return graphqlWithAuthInstance;
	}

	graphqlWithAuthInstance = gql.defaults( {
		headers: {
			authorization: `Bearer ${ getEnvVar( 'GITHUB_TOKEN', true ) }`,
		},
	} );

	return graphqlWithAuthInstance;
};

/**
 * Returns an Octokit instance with auth headers, throws an Exception if
 * `GITHUB_TOKEN` env var is not present.
 *
 * @return graphql instance
 */
export const octokitWithAuth = (): Octokit => {
	if ( octokitWithAuthInstance ) {
		return octokitWithAuthInstance;
	}

	octokitWithAuthInstance = new Octokit( {
		auth: getEnvVar( 'GITHUB_TOKEN', true ),
	} );

	return octokitWithAuthInstance;
};
