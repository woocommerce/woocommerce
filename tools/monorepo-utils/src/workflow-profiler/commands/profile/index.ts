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
	getRunJobData,
	getCompiledJobData,
	logJobResults,
	logStepResults,
} from '../../lib';
import { Logger } from '../../../core/logger';

import { octokitWithAuth } from '../../../core/github/api';

const program = new Command( 'profile' )
	.description( 'Profile Github workflows' )
	.argument( '<start>', 'Start date in YYYY-MM-DD format' )
	.argument( '<end>', 'End date in YYYY-MM-DD format' )
	.argument(
		'<id>',
		'Workflow ID. The required workflow ids are 22745783, 5687250, 23271226, and 5461563. For the rest, use the `list` command.'
	)
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
	.action( async ( start, end, id, { owner, name } ) => {
		const workflowData = await getWorkflowData( id );
		Logger.notice(
			`Processing workflow id ${ id }: "${ workflowData.name }" from ${ start } to ${ end }`
		);
		const workflowRunData = await getWorkflowRunData( {
			id,
			owner,
			name,
			start,
			end,
		} );

		logWorkflowRunResults( workflowRunData );

		const { nodeIds } = workflowRunData;
		const runJobData = await getRunJobData( nodeIds );
		const compiledJobData = getCompiledJobData( runJobData );
		// console.log( compiledJobData );

		logJobResults( compiledJobData );
		logStepResults( compiledJobData );
	} );

export default program;
