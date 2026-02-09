/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import {
	getIssuesByLabel,
	updateIssue,
	getRepositoryLabel,
} from '../../../core/github/repo';
import { Logger } from '../../../core/logger';

export const replaceLabelsCommand = new Command( 'replace-labels' )
	.description( 'Replace labels of issues' )
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
	.option( '-l --label <label>', 'Label to filter by and replace' )
	.option(
		'-r --replacement-label <replacementLabel>',
		'Label to use for replacement'
	)
	.option(
		'--remove-if-starts-with <removeIfStartsWith>',
		'Only remove the label if it already contains a label that starts with.'
	)
	.action( async ( options ) => {
		const { owner, name, replacementLabel, removeIfStartsWith } = options;
		const label = options.label?.toLowerCase();

		if ( ! label ) {
			Logger.warn(
				`No label supplied, going off the latest release version`
			);
			return;
		}

		Logger.startTask( `Querying by label: "${ label }"` );
		const { results } = await getIssuesByLabel( { owner, name }, label );
		Logger.endTask();

		if ( results.length === 0 ) {
			Logger.warn( `No issues found by label: "${ label }"` );
			process.exit( 0 );
		}

		try {
			Logger.startTask(
				`Checking if "${ replacementLabel }" exists in ${ name } repository.`
			);
			await getRepositoryLabel(
				{ owner, name },
				replacementLabel.toLowerCase()
			);
			Logger.endTask();
		} catch ( e ) {
			Logger.endTask();
			Logger.warn(
				`"${ replacementLabel }" does not exist in ${ name } repository. Please create the label first.`
			);
			process.exit( 0 );
		}

		for ( const issue of results ) {
			// Get labels by name only and filter out the existing label.
			const labels = issue.labels
				.map( ( l ) => ( typeof l === 'string' ? l : l.name ) )
				.filter( ( l ) => l.toLowerCase() !== label );

			/**
			 * Check if label with prefix already exists, in that case we only remove the label.
			 * Ex: Multiple teams may be assigned to one issue, when replace one team for another
			 * we did want to keep the team that already exists.
			 */
			const containsSimilarLabelAlready =
				removeIfStartsWith &&
				labels.find( ( l ) => l.startsWith( removeIfStartsWith ) );

			if ( ! containsSimilarLabelAlready ) {
				labels.push( replacementLabel );
			}
			Logger.notice(
				`Updating issue ${ issue.number } labels to: ${ labels }`
			);
			const result = await updateIssue( { owner, name }, issue.number, {
				labels,
			} );
			if ( result && result.status === 200 ) {
				Logger.notice(
					`Successfully updated issue ${ issue.number }: ${ result.data.html_url }`
				);
			} else {
				Logger.error( `Failed updating ${ issue.number }` );
			}
		}

		process.exit( 0 );
	} );
