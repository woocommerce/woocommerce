/**
 * External dependencies
 */
import { Endpoints } from '@octokit/types';

export type PullRequestEndpointResponse =
	Endpoints[ 'POST /repos/{owner}/{repo}/pulls' ][ 'response' ];

// PullRequestEndpointResponse.data.head.repo.has_discussions<boolean>
