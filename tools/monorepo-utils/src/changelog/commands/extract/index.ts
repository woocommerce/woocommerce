/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { getPullRequestData } from './lib';

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
			// const project = 'woocommerce';
			// const message = `Add test changelog for PR #${ prNumber }. This is generated using an action`;
			// const significance = 'patch';
			// const type = 'fix';
			const { prData, isCommunityPR, prOwner, branch, fileName } =
				await getPullRequestData( { owner, name }, prNumber );
			Logger.notice(
				`PR #${ prNumber } ${
					isCommunityPR ? 'is' : 'is not'
				} a community PR. Making a clone of ${ prOwner }/${ name } and adding a changelog to branch ${ branch } in a file called ${ fileName }`
			);
			// const { body } = prData;
			// Logger.notice( JSON.stringify( body, null, 2 ) );
		}
	);
