/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import {
	getWorkflowRunData,
	logWorkflowRunResults,
	getWorkflowData,
} from '../../lib';
import { Logger } from '../../../core/logger';
import config from '../../config';

const program = new Command( 'profile' )
	.description( 'Profile Github workflows' )
	.argument( '<start>', 'Start date in YYYY-MM-DD format' )
	.argument( '<end>', 'End date in YYYY-MM-DD format' )
	.argument( '[ids...]', 'Workflow IDs. Defaults to required workflows' )
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
	.action( async ( start, end, ids, { owner, name } ) => {
		const { requiredWorkflows } = config;
		const workflowIds = ids.length ? ids : requiredWorkflows;

		Promise.all(
			workflowIds.map( async ( id: number | string ) => {
				const workflowData = await getWorkflowData( id );
				Logger.notice(
					`Processing workflow id ${ id }: "${ workflowData.name }" from ${ start } to ${ end }`
				);
				const result = await getWorkflowRunData( {
					id,
					owner,
					name,
					start,
					end,
				} );
				return result;
			} )
		).then( ( results ) => logWorkflowRunResults( results ) );
	} );

export default program;
