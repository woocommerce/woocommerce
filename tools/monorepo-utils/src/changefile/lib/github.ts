/**
 * Internal dependencies
 */
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
	const fileName = `${ prNumber }-${ branch.replace( /\//g, '-' ) }`;
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
export const shouldAutomateChangelog = ( body: string ) => {
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
	const messageRegex = /#### Message ?(<!--(.*)-->)?(.*)#### Comment/gms;
	const match = messageRegex.exec( body );

	if ( ! match ) {
		Logger.error( 'No changelog message found' );
	}

	let message = match[ 3 ].trim();

	// Newlines break the formatting of the changelog, so we replace them with spaces.
	message = message.replace( /\r\n|\n/g, ' ' );

	return message;
};

/**
 * Get the changelog comment from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog comment.
 */
export const getChangelogComment = ( body: string ) => {
	const commentRegex = /#### Comment ?(<!--(.*)-->)?(.*)<\/details>/gms;
	const match = commentRegex.exec( body );

	let comment = match ? match[ 3 ].trim() : '';

	// Newlines break the formatting of the changelog, so we replace them with spaces.
	comment = comment.replace( /\r\n|\n/g, ' ' );

	return comment;
};

/**
 * Get the changelog details from a pull request description.
 *
 * @param {string} body Pull request description
 * @return {Object}     Changelog details
 */
export const getChangelogDetails = ( body: string ) => {
	return {
		significance: getChangelogSignificance( body ),
		type: getChangelogType( body ),
		message: getChangelogMessage( body ),
		comment: getChangelogComment( body ),
	};
};

/**
 * Determine if a pull request description contains changelog input errors.
 *
 * @param {Object} details              changelog details.
 * @param {string} details.significance changelog significance.
 * @param {string} details.type         changelog type.
 * @param {string} details.message      changelog message.
 * @param {string} details.comment      changelog comment.
 * @return {string|null} error message, or null if none found
 */
export const getChangelogDetailsError = ( {
	significance,
	type,
	message,
	comment,
}: {
	significance?: string;
	type?: string;
	message?: string;
	comment?: string;
} ) => {
	if ( comment && message ) {
		return 'Both a message and comment were found. Only one can be entered';
	}
	if ( comment && significance !== 'patch' ) {
		return 'Only patch changes can have a comment. Please change the significance to patch or remove the comment';
	}
	if ( ! significance ) {
		return 'No changelog significance found';
	}
	if ( ! type ) {
		return 'No changelog type found';
	}
	if ( ! comment && ! message ) {
		return 'No changelog message or comment found';
	}
	return null;
};
