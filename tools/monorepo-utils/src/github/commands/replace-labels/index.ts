/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { getIssuesByLabel, updateIssue } from '../../../core/github/repo';
import { Logger } from '../../../core/logger';

export const labelsCommand = new Command( 'replace-labels' )
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
	.option(
		'-s --source <source>',
		'Branch to create the release branch from. Default: trunk',
		'trunk'
	)
	.action( async ( options ) => {
		const { label, owner, name, replacementLabel, removeIfStartsWith } =
			options;

		if ( ! label ) {
			Logger.warn(
				`No label supplied, going off the latest release version`
			);
			return;
		}

		Logger.notice( `Querying by label: ${ label }` );
		const { results } = await getIssuesByLabel( { owner, name }, label );
		if ( results.length === 0 ) {
			Logger.warn( `No issues found by label: ${ label }` );
			process.exit( 0 );
		}

		for ( const issue of results ) {
			const labels = issue.labels
				.map( ( l ) => l.name )
				.filter( ( l ) => l !== label );
			const containsOtherTeamLabel =
				removeIfStartsWith &&
				labels.find( ( l ) => l.startsWith( removeIfStartsWith ) );
			if ( ! containsOtherTeamLabel ) {
				labels.push( replacementLabel ); // 'team: Kirigami & Origami' );
			}
			Logger.notice(
				`Updating issue ${ issue.number } labels to: ${ labels }`
			);
			const result = await updateIssue( { owner, name }, issue.number, {
				labels,
			} );
			if ( result.status === 200 ) {
				Logger.notice(
					`Successfully updated issue ${ issue.number }: ${ result.data.html_url }`
				);
			}
		}

		process.exit( 0 );
	} );
