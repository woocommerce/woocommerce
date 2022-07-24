/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

function isOptionValueTruthy( value ) {
	return value === true || value === 'yes' || value === '1' || value === 1;
}

const Tracks = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { isResolving, recentTracksEnabled } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			isResolving: ! hasFinishedResolution( 'getOption', [
				'wc_beta_tester_recent_tracks_enabled',
			] ),
			recentTracksEnabled: isOptionValueTruthy(
				getOption( 'wc_beta_tester_recent_tracks_enabled' )
			),
		};
	} );

	if ( isResolving ) {
		return null;
	}
	const toggleRecentTracks = () => {
		const updatedRecentTracksEnabled = isOptionValueTruthy(
			recentTracksEnabled
		)
			? 0
			: 1;

		updateOptions( {
			wc_beta_tester_recent_tracks_enabled: updatedRecentTracksEnabled,
		} );
	};

	return (
		<div>
			<h2>Tracks</h2>
			<ToggleControl
				label={ 'Enable recent Tracks events' }
				checked={ recentTracksEnabled }
				onChange={ toggleRecentTracks }
			/>
		</div>
	);
};

export default Tracks;
