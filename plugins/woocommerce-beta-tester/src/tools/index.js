/**
 * External dependencies
 */
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { Notice, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { default as commands } from './commands';
import { STORE_KEY } from './data/constants';
import './data';

function Tools( {
	actions,
	currentlyRunningCommands,
	messages,
	comandParams,
} ) {
	actions = actions();
	return (
		<div id="wc-admin-test-helper-tools">
			<h2>Tools</h2>
			<p>This section contains miscellaneous tools.</p>
			{ Object.keys( messages ).map( ( key ) => {
				return (
					<Notice
						status={ messages[ key ].status }
						key={ key }
						isDismissible={ false }
					>
						{ key }: { messages[ key ].message }
					</Notice>
				);
			} ) }
			<table className="tools wp-list-table striped table-view-list widefat">
				<thead>
					<tr>
						<th>Command</th>
						<th>Description</th>
						<th>Run</th>
					</tr>
				</thead>
				<tbody>
					{ commands.map(
						( { action, command, description }, index ) => {
							const params = comandParams[ action ] ?? false;
							return (
								<tr key={ index }>
									<td className="command">{ command }</td>
									<td>{ description }</td>
									<td>
										<Button
											onClick={ () =>
												actions[ action ]( params )
											}
											disabled={
												currentlyRunningCommands[
													command
												]
											}
											isPrimary
										>
											Run
										</Button>
									</td>
								</tr>
							);
						}
					) }
				</tbody>
			</table>
		</div>
	);
}

export default compose(
	withSelect( ( select ) => {
		const { getCurrentlyRunning, getMessages, getCommandParams } =
			select( STORE_KEY );
		return {
			currentlyRunningCommands: getCurrentlyRunning(),
			messages: getMessages(),
			comandParams: getCommandParams(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const actions = function () {
			return dispatch( STORE_KEY );
		};

		return {
			actions,
		};
	} )
)( Tools );
