/**
 * External dependencies
 */
import { Endpoints } from '@octokit/types';

export type CreatePullRequestEndpointResponse =
	Endpoints[ 'POST /repos/{owner}/{repo}/pulls' ][ 'response' ];

export type PullRequestEndpointResponse =
	Endpoints[ 'GET /repos/{owner}/{repo}/pulls/{pull_number}' ][ 'response' ];
