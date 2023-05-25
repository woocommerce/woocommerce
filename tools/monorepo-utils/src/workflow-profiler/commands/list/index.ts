/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { getAllWorkflows } from '../../lib';
import { Logger } from '../../../core/logger';

const program = new Command( 'list' )
	.description( 'List all Github workflows in the WooCommerce Monorepo' )
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
			[ 'Workflow Name', 'Id' ],
			allWorkflows.map( ( workflow ) => [ workflow.name, workflow.id ] )
		);
		Logger.endTask();
	} );

export default program;
