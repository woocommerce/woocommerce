/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { getIssuesByLabel } from '../../../core/github/repo';
import { Logger } from '../../../core/logger';

export const labelsCommand = new Command( 'labels' )
	.description( 'Create a new release branch' )
	.option( '-d --dryRun', 'Prepare the branch but do not create it.' )
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
	.option(
		'-l --label <label>',
		'Release branch to create. The branch will be determined from Github if none is supplied'
	)
	.option(
		'-s --source <source>',
		'Branch to create the release branch from. Default: trunk',
		'trunk'
	)
	.action( async ( options ) => {
		const { source, label, owner, name, dryRun } = options;

		if ( ! label ) {
			Logger.warn(
				`No label supplied, going off the latest release version`
			);
			return;
		}

		Logger.notice( `Querying by label: ${ label }` );
		const issues = await getIssuesByLabel( { owner, name }, label );

		process.exit( 0 );
	} );
