'use strict';

/* eslint no-console: 0 */

const ZenHub = require( 'zenhub-api' );
const { REPO, pkg } = require( '../config' );
const { authedGraphql } = require( '../common' );
const { pull } = require( 'lodash' );

const api = new ZenHub( pkg.changelog.zhApiToken );

const getQuery = ( before ) => {
	const [ owner, repo ] = REPO.split( '/' );
	const paging = before ? `, before: "${ before }"` : '';
	const query = `
	{
		repository(owner: "${ owner }", name: "${ repo }") {
			pullRequests(last: 100, states: [MERGED]${ paging }) {
				totalCount
				pageInfo {
					startCursor
				}
				nodes {
					number
					title
					url
					author {
						login
					}
					body
					labels(last: 10) {
						nodes {
							name
						}
					}
				}
			}
		}
	}
	`;
	return query;
};

const fetchAllIssuesForRelease = async ( releaseId ) => {
	const releaseIssues = await api.getReleaseReportIssues( {
		release_id: releaseId,
	} );
	return releaseIssues.map( ( releaseIssue ) => releaseIssue.issue_number );
};

const extractPullRequestsMatchingReleaseIssue = (
	releaseIds,
	pullRequests
) => {
	return pullRequests.filter( ( pullRequest ) => {
		const hasPullRequest = releaseIds.includes( pullRequest.number );
		if ( hasPullRequest ) {
			pull( releaseIds, pullRequest.number );
			return true;
		}
		return false;
	} );
};

const fetchAllPullRequests = async ( releaseId ) => {
	// first get all release issue ids
	const releaseIds = await fetchAllIssuesForRelease( releaseId );
	let maxPages = Math.ceil( releaseIds.length / 100 ) + 2;
	const fetchResults = async ( before ) => {
		const query = getQuery( before );
		const results = await authedGraphql( query );
		const pullRequests = extractPullRequestsMatchingReleaseIssue(
			releaseIds,
			results.repository.pullRequests.nodes
		);
		if ( maxPages === 0 ) {
			return pullRequests;
		}
		maxPages--;
		const nextResults = await fetchResults(
			results.repository.pullRequests.pageInfo.startCursor
		);
		return pullRequests.concat(
			extractPullRequestsMatchingReleaseIssue( releaseIds, nextResults )
		);
	};
	let results = [];
	try {
		results = await fetchResults();
	} catch ( e ) {
		console.log( e.request );
		console.log( e.message );
		console.log( e.data );
	}
	return results;
};

module.exports = {
	fetchAllPullRequests,
};
