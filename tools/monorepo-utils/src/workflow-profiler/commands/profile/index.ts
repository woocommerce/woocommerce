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
	.argument( '<id>', 'Workflow Id or filename.' )
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

		let runJobData = {};

		if ( showSteps ) {
			const { nodeIds } = workflowRunData;
			runJobData = await getRunJobData( nodeIds );
		}

		logWorkflowRunResults( workflowData.name, workflowRunData );

		if ( showSteps ) {
			logJobResults( runJobData );
			logStepResults( runJobData );
		}
	} );

export default program;
