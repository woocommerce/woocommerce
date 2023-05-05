/**
 * External dependencies
 */
import { graphql } from '@octokit/graphql';
import { Octokit } from 'octokit';

export const graphqlWithAuth = graphql.defaults( {
	headers: {
		authorization: `Bearer ${ process.env.GITHUB_TOKEN }`,
	},
} );

export const octokitWithAuth = new Octokit( {
	auth: process.env.GITHUB_TOKEN,
} );
