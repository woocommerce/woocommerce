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
			const { prData, isCommunityPR, prOwner, branch, fileName } =
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

			Logger.notice(
				`PR #${ prNumber } ${
					isCommunityPR ? 'is' : 'is not'
				} a community PR. Making a clone of ${ prOwner }/${ name } and adding a changelog to branch ${ branch } in a file called ${ fileName }`
			);

			const significance = getChangelogSignificance( prData.body );
			const type = getChangelogType( prData.body );
			const message = getChangelogMessage( prData.body );
			const comment = getChangelogComment( prData.body );

			Logger.notice( significance );
			Logger.notice( type );
			Logger.notice( message );
			Logger.notice( comment );
			// const { body } = prData;
			// Logger.notice( JSON.stringify( body, null, 2 ) );
		}
	);
