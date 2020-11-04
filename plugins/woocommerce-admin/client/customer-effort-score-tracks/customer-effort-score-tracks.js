/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { recordEvent } from '@woocommerce/tracks';
import CustomerEffortScore from '@woocommerce/customer-effort-score';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME, MONTH } from '@woocommerce/data';

const SHOWN_FOR_ACTIONS_OPTION_NAME = 'woocommerce_ces_shown_for_actions';
const ADMIN_INSTALL_TIMESTAMP_OPTION_NAME =
	'woocommerce_admin_install_timestamp';
const ALLOW_TRACKING_OPTION_NAME = 'woocommerce_allow_tracking';

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object}   props                    Component props.
 * @param {boolean}  props.initiallyVisible   Whether or not the tracks modal is initially visible.
 * @param {string}   props.action             The action name sent to Tracks.
 * @param {Object}   props.trackProps         Additional props sent to Tracks.
 * @param {string}   props.label              The label displayed in the modal.
 * @param {Array}    props.cesShownForActions The array of actions that the CES modal has been shown for.
 * @param {boolean}  props.allowTracking      Whether tracking is allowed or not.
 * @param {boolean}  props.resolving          Are values still being resolved.
 * @param {number}   props.storeAge           The age of the store in months.
 * @param {Function} props.updateOptions      Function to update options.
 */
function CustomerEffortScoreTracks( {
	initiallyVisible,
	action,
	trackProps,
	label,
	cesShownForActions,
	allowTracking,
	resolving,
	storeAge,
	updateOptions,
} ) {
	const [ visible, setVisible ] = useState( initiallyVisible );
	const [ shown, setShown ] = useState( false );

	if ( resolving ) {
		return null;
	}

	// Don't show if tracking is disallowed.
	if ( ! allowTracking ) {
		return null;
	}

	if ( cesShownForActions.indexOf( action ) !== -1 && ! shown ) {
		return null;
	}

	// Use the `shown` state value to only update the shown_for_actions option
	// once.
	if ( visible && ! shown ) {
		setShown( true );
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				action,
				...cesShownForActions,
			],
		} );
	}

	const trackCallback = ( score ) => {
		recordEvent( 'ces_feedback', {
			action,
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
	 * The action name sent to Tracks.
	 */
	action: PropTypes.string.isRequired,
	/**
	 * Additional props sent to Tracks.
	 */
	trackProps: PropTypes.object,
	/**
	 * The label displayed in the modal.
	 */
	label: PropTypes.string.isRequired,
	/**
	 * The array of actions that the CES modal has been shown for.
	 */
	cesShownForActions: PropTypes.arrayOf( PropTypes.string ).isRequired,
	/**
	 * Whether tracking is allowed or not.
	 */
	allowTracking: PropTypes.bool,

	/**
	 * Whether props are still being resolved.
	 */
	resolving: PropTypes.bool.isRequired,
	/**
	 * The age of the store in months.
	 */
	storeAge: PropTypes.number,
	/**
	 * Function to update options.
	 */
	updateOptions: PropTypes.func,
};

export default compose(
	withSelect( ( select ) => {
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const cesShownForActions =
			getOption( SHOWN_FOR_ACTIONS_OPTION_NAME ) || [];

		const adminInstallTimestamp =
			getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) || 0;
		// Date.now() is ms since Unix epoch, adminInstallTimestamp is in
		// seconds since Unix epoch.
		const storeAgeInMs = Date.now() - adminInstallTimestamp * 1000;
		const storeAge = Math.round( storeAgeInMs / MONTH );

		const allowTrackingOption =
			getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';
		const allowTracking = allowTrackingOption === 'yes';

		const resolving =
			isResolving( 'getOption', [ SHOWN_FOR_ACTIONS_OPTION_NAME ] ) ||
			isResolving( 'getOption', [
				ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
			] ) ||
			isResolving( 'getOption', [ ALLOW_TRACKING_OPTION_NAME ] );

		return {
			cesShownForActions,
			allowTracking,
			storeAge,
			resolving,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );

		return {
			updateOptions,
		};
	} )
)( CustomerEffortScoreTracks );
