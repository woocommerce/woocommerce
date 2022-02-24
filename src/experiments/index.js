/**
 * External dependencies
 */
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './data/constants';
import './data';

function Experiments( { experiments, toggleExperiment } ) {
	return (
		<div id="wc-admin-test-helper-experiments">
			<h2>Experiments</h2>
			<table className="experiments wp-list-table striped table-view-list widefat">
				<thead>
					<tr>
						<th>Experiment</th>
						<th>Variation</th>
						<th>Toggle</th>
					</tr>
				</thead>
				<tbody>
					{ experiments.map(
						( { name, variation, source }, index ) => {
							return (
								<tr key={ index }>
									<td className="experiment-name">
										{ name }
									</td>
									<td align="center">{ variation }</td>
									<td align="center">
										<Button
											onClick={ () => {
												toggleExperiment(
													name,
													variation,
													source
												);
											} }
											isPrimary
										>
											Toggle
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
		const { getExperiments } = select( STORE_KEY );
		return {
			experiments: getExperiments(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { toggleExperiment } = dispatch( STORE_KEY );

		return {
			toggleExperiment,
		};
	} )
)( Experiments );
