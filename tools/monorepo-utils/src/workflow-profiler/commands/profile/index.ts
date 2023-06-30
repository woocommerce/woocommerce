/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import {
	getWorkflowRunData,
	getWorkflowData,
	getRunJobData,
	getCompiledJobData,
} from '../../lib/data';
import {
	logWorkflowRunResults,
	logJobResults,
	logStepResults,
} from '../../lib/log';
import { Logger } from '../../../core/logger';

const program = new Command( 'profile' )
	.description( 'Profile GitHub workflows' )
	.argument( '<start>', 'Start date in YYYY-MM-DD format' )
	.argument( '<end>', 'End date in YYYY-MM-DD format' )
	.argument(
		'<id>',
		'Workflow ID. The required workflow ids are 22745783, 5687250, 23271226, and 5461563. For the rest, use the `list` command to see workflow names and ids.'
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
	.option( '-s --show-steps' )
	.action( async ( start, end, id, { owner, name, showSteps } ) => {
		const workflowData = await getWorkflowData( id, owner, name );
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

		let compiledJobData = {};

		if ( showSteps ) {
			const { nodeIds } = workflowRunData;
			const runJobData = await getRunJobData( nodeIds );
			compiledJobData = getCompiledJobData( runJobData );
		}

		logWorkflowRunResults( workflowData.name, workflowRunData );

		if ( showSteps ) {
			logJobResults( compiledJobData );
			logStepResults( compiledJobData );
		}
	} );

export default program;
