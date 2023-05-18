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
	const prData = await getPullRequest( { owner, name }, prNumber );
	const isCommunityPR = isCommunityPullRequest( prData );
	const headOwner = isCommunityPR ? prData.head.repo.owner.login : owner;
	const branch = prData.head.ref;
	const fileName = branch.replace( '/', '-' );
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

/**
 * Get the changelog type from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog type.
 */
export const getChangelogType = ( body: string ) => {
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
	return {
		significance: getChangelogSignificance( body ),
		type: getChangelogType( body ),
		message: getChangelogMessage( body ),
		comment: getChangelogComment( body ),
	};
};
