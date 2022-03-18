/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './data/constants';
import './data';

function Features() {
	const { features = {}, modifiedFeatures = [] } = useSelect( ( select ) => {
		const { getFeatures, getModifiedFeatures } = select( STORE_KEY );
		return {
			features: getFeatures(),
			modifiedFeatures: getModifiedFeatures(),
		};
	} );

	const { toggleFeature, resetModifiedFeatures } = useDispatch( STORE_KEY );

	return (
		<div id="wc-admin-test-helper-features">
			<h2>
				Features
				<Button
					disabled={ modifiedFeatures.length === 0 }
					onClick={ () => resetModifiedFeatures() }
					isSecondary
					style={ { marginLeft: '24px' } }
				>
					Reset to defaults
				</Button>
			</h2>
			<table className="features wp-list-table striped table-view-list widefat">
				<thead>
					<tr>
						<th>Feature Name</th>
						<th>Enabled?</th>
						<th>Toggle</th>
					</tr>
				</thead>
				<tbody>
					{ Object.keys( features ).map( ( feature_name ) => {
						return (
							<tr key={ feature_name }>
								<td className="feature-name">
									{ feature_name }
								</td>
								<td>{ features[ feature_name ].toString() }</td>
								<td>
									<Button
										onClick={ () => {
											toggleFeature( feature_name );
										} }
										isPrimary
									>
										Toggle
									</Button>
								</td>
							</tr>
						);
					} ) }
				</tbody>
			</table>
		</div>
	);
}

export default Features;
