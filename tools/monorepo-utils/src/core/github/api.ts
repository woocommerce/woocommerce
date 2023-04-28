/**
 * External dependencies
 */
import { graphql } from '@octokit/graphql';
import { Octokit } from 'octokit';

/**
 * Internal dependencies
 */
import { getEnvVar } from '../environment';

export const graphqlWithAuth = graphql.defaults( {
	headers: {
		authorization: `Bearer ${ getEnvVar( 'GITHUB_TOKEN', true ) }`,
	},
} );

export const octokitWithAuth = new Octokit( {
	auth: getEnvVar( 'GITHUB_TOKEN', true ),
} );
