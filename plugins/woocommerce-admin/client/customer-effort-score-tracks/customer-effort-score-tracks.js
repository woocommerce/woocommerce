/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { recordEvent } from '@woocommerce/tracks';
import CustomerEffortScore from '@woocommerce/customer-effort-score';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME, MONTH } from '@woocommerce/data';

const ADMIN_INSTALL_TIMESTAMP_OPTION_NAME =
	'woocommerce_admin_install_timestamp';

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object}   props                  Component props.
 * @param {boolean}  props.initiallyVisible Whether or not the tracks modal is initially visible.
 * @param {string}   props.trackName        The name sent to Tracks.
 * @param {Object}   props.trackProps       Additional props sent to Tracks.
 * @param {string}   props.label            The label displayed in the modal.
 * @param {boolean}  props.resolving          Are values still being resolving.
 * @param {number}   props.storeAge           The age of the store in months.
 */
function CustomerEffortScoreTracks( {
	initiallyVisible,
	trackName,
	trackProps,
	label,
	resolving,
	storeAge,
} ) {
	const [ visible, setVisible ] = useState( initiallyVisible );

	if ( resolving ) {
		return null;
	}

	const trackCallback = ( score ) => {
		recordEvent( trackName, {
			score,
			store_age: storeAge,
			...trackProps,
		} );
	};

	return (
		<CustomerEffortScore
			trackCallback={ trackCallback }
			visible={ visible }
			toggleVisible={ () => setVisible( ! visible ) }
			label={ label }
		/>
	);
}

CustomerEffortScoreTracks.propTypes = {
	/**
	 * Whether or not the tracks is initially visible. True is used for when
	 * this is loaded on page load (in client/index.js). False is used if the
	 * tracks modal is loaded as part of the layout and displayed
	 * programmatically.
	 */
	initiallyVisible: PropTypes.bool,
	/**
	 * The name sent to Tracks.
	 */
	trackName: PropTypes.string.isRequired,
	/**
	 * Additional props sent to Tracks.
	 */
	trackProps: PropTypes.object,
	/**
	 * The label displayed in the modal.
	 */
	label: PropTypes.string.isRequired,
	/**
	 * Whether props are still being resolved.
	 */
	resolving: PropTypes.bool.isRequired,
	/**
	 * The age of the store in months.
	 */
	storeAge: PropTypes.number,
};

export default compose(
	withSelect( ( select ) => {
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const adminInstallTimestamp =
			getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) || 0;
		// Date.now() is ms since Unix epoch, adminInstallTimestamp is in
		// seconds since Unix epoch.
		const storeAgeInSeconds = Date.now() - adminInstallTimestamp * 1000;
		const storeAge = Math.round( storeAgeInSeconds / MONTH );

		const resolving = isResolving( 'getOption', [
			ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
		] );

		return {
			storeAge,
			resolving,
		};
	} )
)( CustomerEffortScoreTracks );
