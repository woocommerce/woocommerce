/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { getAllWorkflows } from '../../lib/data';
import { Logger } from '../../../core/logger';

const program = new Command( 'list' )
	.description( 'List all Github workflows in a repository' )
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
	.action( async ( { owner, name } ) => {
		Logger.startTask( 'Listing all workflows' );
		const allWorkflows = await getAllWorkflows( owner, name );
		Logger.notice(
			`There are ${ allWorkflows.length } workflows in the repository.`
		);
		Logger.table(
			[ 'Workflow Name', 'configuration file', 'Id' ],
			allWorkflows.map( ( workflow ) => [
				workflow.name,
				workflow.path.replace( '.github/workflows/', '' ),
				workflow.id,
			] )
		);
		Logger.endTask();
	} );

export default program;
