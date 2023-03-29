/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Button, ToggleControl } from '@wordpress/components';

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

	const sortedFeatureNames = Object.keys( features ).sort();

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
			<ul className="features">
				{ sortedFeatureNames.map( ( feature_name ) => {
					return (
						<li key={ feature_name } className="feature-name">
							<ToggleControl
								label={ feature_name }
								checked={ features[ feature_name ] }
								onChange={ () => {
									toggleFeature( feature_name );
								} }
							/>
						</li>
					);
				} ) }
			</ul>
		</div>
	);
}

export default Features;
