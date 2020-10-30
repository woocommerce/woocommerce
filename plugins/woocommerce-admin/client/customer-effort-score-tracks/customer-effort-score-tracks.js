/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { recordEvent } from '@woocommerce/tracks';
import CustomerEffortScore from '@woocommerce/customer-effort-score';

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object}   props                  Component props.
 * @param {boolean}  props.initiallyVisible Whether or not the tracks modal is initially visible.
 * @param {string}   props.trackName        The name sent to Tracks.
 * @param {Object}   props.trackProps       Additional props sent to Tracks.
 * @param {string}   props.label            The label displayed in the modal.
 */
function CustomerEffortScoreTracks( {
	initiallyVisible,
	trackName,
	trackProps,
	label,
} ) {
	const [ visible, setVisible ] = useState( initiallyVisible );

	const trackCallback = ( score ) => {
		recordEvent( trackName, {
			score,
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
};

export default CustomerEffortScoreTracks;
