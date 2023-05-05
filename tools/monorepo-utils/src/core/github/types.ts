/**
 * External dependencies
 */
import { Endpoints } from '@octokit/types';

export type PullRequestEndpointResponse =
	Endpoints[ 'POST /repos/{owner}/{repo}/pulls' ][ 'response' ];
