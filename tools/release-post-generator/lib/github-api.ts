/**
 * External dependencies
 */
import { Octokit } from '@octokit/rest';

const filterUniqBy = ( arr: Record< string, unknown >[], key: string ) => {
	const seen = new Set();
	return arr.filter( ( item ) => {
		const k = item[ key ];
		if ( seen.has( k ) ) {
			return false;
		}
		seen.add( k );
		return true;
	} );
};

// Make auth optional since we're often accessing public data
const octokit = new Octokit( {
	auth: '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',
} );

const PAGE_SIZE = 100;

export const getContributors = async (
	orgName: string,
	repoName: string,
	baseRef: string,
	headRef: string
) => {
	const {
		data: { total_commits, commits },
	} = await octokit.repos.compareCommitsWithBasehead( {
		owner: orgName,
		repo: repoName,
		basehead: `${ baseRef }...${ headRef }`,
		per_page: PAGE_SIZE,
	} );

	const pages = Math.ceil( total_commits / PAGE_SIZE );
	const allAuthors = [];

	// add page 1 commits
	allAuthors.push(
		...commits
			.filter( ( commit ) => {
				return (
					!! commit.author && ! commit.author.login.includes( 'bot' )
				);
			} )
			.map( ( commit ) => commit.author )
	);

	for ( let i = 2; i <= pages; i++ ) {
		const {
			data: { commits: pageCommits },
		} = await octokit.repos.compareCommitsWithBasehead( {
			owner: orgName,
			repo: repoName,
			basehead: `${ baseRef }...${ headRef }`,
			per_page: PAGE_SIZE,
			page: i,
		} );

		allAuthors.push(
			...pageCommits
				.filter( ( commit ) => {
					return (
						!! commit.author &&
						! commit.author.login.includes( 'bot' )
					);
				} )
				.map( ( commit ) => commit.author )
		);
	}

	return {
		totalCommits: total_commits,
		contributors: filterUniqBy(
			allAuthors as Array< Record< string, unknown > >,
			'login'
		),
		org: orgName,
		repo: repoName,
		baseRef,
		headRef,
	};
};
