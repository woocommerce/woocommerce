/**
 * Internal dependencies
 */
import { sign } from 'crypto';
import { getPullRequest, isCommunityPullRequest } from '../../core/github/repo';
import { Logger } from '../../core/logger';

/**
 * Get relevant data from a pull request.
 *
 * @param {Object} options
 * @param {string} options.owner repository owner.
 * @param {string} options.name  repository name.
 * @param {string} prNumber      pull request number.
 * @return {Promise<object>}     pull request data.
 */
export const getPullRequestData = async (
	options: { owner: string; name: string },
	prNumber: string
) => {
	const { owner, name } = options;
	const prData = await getPullRequest( { owner, name, prNumber } );
	const isCommunityPR = isCommunityPullRequest( prData, owner, name );
	const headOwner = isCommunityPR ? prData.head.repo.owner.login : owner;
	const branch = prData.head.ref;
	const fileName = branch.replace( /\//g, '-' );
	const prBody = prData.body;
	const head = prData.head.sha;
	const base = prData.base.sha;
	return {
		prBody,
		isCommunityPR,
		headOwner,
		branch,
		fileName,
		head,
		base,
	};
};

/**
 * Determine if a pull request description activates the changelog automation.
 *
 * @param {string} body pull request description.
 * @return {boolean} if the pull request description activates the changelog automation.
 */
export const getShouldAutomateChangelog = ( body: string ) => {
	const regex =
		/\[x\] Automatically create a changelog entry from the details/gm;
	return regex.test( body );
};

/**
 * Get the changelog significance from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog significance.
 */
export const getChangelogSignificance = ( body: string ) => {
	const regex = /\[x\] (Patch|Minor|Major)\r\n/gm;
	const matches = body.match( regex );

	if ( matches === null ) {
		Logger.error( 'No changelog significance found' );
		// Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
		return;
	}

	if ( matches.length > 1 ) {
		Logger.error(
			'Multiple changelog significances found. Only one can be entered'
		);
		// Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
		return;
	}
	const significance = regex.exec( body );

	return significance[ 1 ].toLowerCase();
};

/**
 * Get the changelog type from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog type.
 */
export const getChangelogType = ( body: string ) => {
	const regex =
		/\[x\] (Fix|Add|Update|Dev|Tweak|Performance|Enhancement) -/gm;
	const matches = body.match( regex );

	if ( matches === null ) {
		Logger.error( 'No changelog type found' );
		// Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
		return;
	}

	if ( matches.length > 1 ) {
		Logger.error(
			'Multiple changelog types found. Only one can be entered'
		);
		// Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
		return;
	}

	const type = regex.exec( body );
	return type[ 1 ].toLowerCase();
};

/**
 * Get the changelog message from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog message.
 */
export const getChangelogMessage = ( body: string ) => {
	const messageRegex = /#### Message\r\n<!--(.*)-->(.*)#### Comment/gms;
	const match = messageRegex.exec( body );

	if ( ! match ) {
		Logger.error( 'No changelog message found' );
	}

	return match[ 2 ].trim();
};

/**
 * Get the changelog comment from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog comment.
 */
export const getChangelogComment = ( body: string ) => {
	const commentRegex = /#### Comment\r\n<!--(.*)-->(.*)<\/details>/gms;
	const match = commentRegex.exec( body );

	return match ? match[ 2 ].trim() : '';
};

export const getChangelogDetails = ( body: string ) => {
	const message = getChangelogMessage( body );
	const comment = getChangelogComment( body );

	if ( comment && message ) {
		Logger.error(
			'Both a message and comment were found. Only one can be entered'
		);
		// Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
		return;
	}

	const significance = getChangelogSignificance( body );

	if ( comment && significance !== 'patch' ) {
		Logger.error(
			'Only patch changes can have a comment. Please change the significance to patch or remove the comment'
		);
		// Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
		return;
	}

	return {
		significance,
		type: getChangelogType( body ),
		message,
		comment,
	};
};
