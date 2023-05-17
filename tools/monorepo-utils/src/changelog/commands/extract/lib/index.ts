/**
 * Internal dependencies
 */
import {
	getPullRequest,
	isCommunityPullRequest,
} from '../../../../core/github/repo';
import { Logger } from '../../../../core/logger';

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

export const getShouldAutomateChangelog = ( body ) => {
	const regex =
		/\[x\] Automatically create a changelog entry from the details/gm;
	return regex.test( body );
};

export const getChangelogSignificance = ( body ) => {
	const patchRegex = /\[x\] (Patch)\r\n/gms;
	const minorRegex = /\[x\] (Minor)\r\n/gms;
	const majorRegex = /\[x\] (Major)\r\n/gms;

	const match =
		patchRegex.exec( body ) ||
		minorRegex.exec( body ) ||
		majorRegex.exec( body );

	if ( ! match ) {
		Logger.error( 'No changelog significance found' );
	}

	return match[ 1 ].toLowerCase();
};

export const getChangelogType = ( body ) => {
	const fixRegex = /\[x\] (Fix) -/gm;
	const addRegex = /\[x\] (Add) -/gm;
	const updateRegex = /\[x\] (Update) -/gm;
	const devRegex = /\[x\] (Dev) -/gm;
	const tweakRegex = /\[x\] (Tweak) -/gm;
	const performanceRegex = /\[x\] (Performance) -/gm;
	const enhancementRegex = /\[x\] (Enhancement) -/gm;

	const match =
		fixRegex.exec( body ) ||
		addRegex.exec( body ) ||
		updateRegex.exec( body ) ||
		devRegex.exec( body ) ||
		tweakRegex.exec( body ) ||
		performanceRegex.exec( body ) ||
		enhancementRegex.exec( body );

	if ( ! match ) {
		Logger.error( 'No changelog type found' );
	}

	return match[ 1 ].toLowerCase();
};

export const getChangelogMessage = ( body ) => {
	const messageRegex = /#### Message\r\n<!--(.*)-->(.*)#### Comment/gms;
	const match = messageRegex.exec( body );

	if ( ! match ) {
		Logger.error( 'No changelog message found' );
	}

	return match[ 2 ].trim();
};

export const getChangelogComment = ( body ) => {
	const commentRegex = /#### Comment\r\n<!--(.*)-->(.*)<\/details>/gms;
	const match = commentRegex.exec( body );

	return match ? match[ 2 ].trim() : '';
};
