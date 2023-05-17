/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import {
	getPullRequestData,
	getChangelogSignificance,
	getShouldAutomateChangelog,
	getChangelogType,
	getChangelogMessage,
	getChangelogComment,
} from './lib';

export const extractCommand = new Command( 'extract' )
	.option(
		'-o --owner <owner>',
		'Repository owner. Default: woocommerce',
		'woocommerce'
	)
	.option(
		'-n --name <name>',
		'Repository name. Default: woocommerce',
		'woocommerce'
	)
	.argument( '<pr-number>', 'Pull request number' )
	.action(
		async (
			prNumber: string,
			options: {
				owner: string;
				name: string;
			}
		) => {
			const { owner, name } = options;
			const { prData, isCommunityPR, headOwner, branch, fileName } =
				await getPullRequestData( { owner, name }, prNumber );

			const shouldAutomateChangelog = getShouldAutomateChangelog(
				prData.body
			);

			if ( ! shouldAutomateChangelog ) {
				Logger.notice(
					`PR #${ prNumber } does not have the "Automatically create a changelog entry from the details" checkbox checked. No changelog will be created.`
				);
				process.exit( 0 );
			}

			const significance = getChangelogSignificance( prData.body );
			const type = getChangelogType( prData.body );
			const message = getChangelogMessage( prData.body );
			const comment = getChangelogComment( prData.body );

			const extractedData = {
				prNumber,
				headOwner,
				isCommunityPR,
				branch,
				fileName,
				significance,
				type,
				message,
				comment,
			};

			Logger.notice( JSON.stringify( extractedData, null, 2 ) );
		}
	);
