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
	.action( async ( start, end, { owner, name } ) => {
		const { requiredWorkflows } = config;

		Promise.all(
			requiredWorkflows.map( async ( id: number ) => {
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
				// Logger.endTask();
				return result;
			} )
		).then( ( results ) => logWorkflowRunResults( results ) );
	} );

export default program;
