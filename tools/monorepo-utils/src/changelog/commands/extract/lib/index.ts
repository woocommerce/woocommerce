/**
 * Internal dependencies
 */
import {
	getPullRequest,
	isCommunityPullRequest,
} from '../../../../core/github/repo';

export const getPullRequestData = async ( { owner, name }, prNumber ) => {
	const prData = await getPullRequest( { owner, name }, prNumber );
	const isCommunityPR = isCommunityPullRequest( prData );
	const prOwner = isCommunityPR ? prData.head.repo.owner.login : owner;
	const branch = prData.head.ref;
	const fileName = branch.replace( '/', '-' );
	return {
		prData,
		isCommunityPR,
		prOwner,
		branch,
		fileName,
	};
};
