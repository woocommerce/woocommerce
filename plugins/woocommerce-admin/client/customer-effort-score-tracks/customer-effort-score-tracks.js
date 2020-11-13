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
import { __ } from '@wordpress/i18n';

const SHOWN_FOR_ACTIONS_OPTION_NAME = 'woocommerce_ces_shown_for_actions';
const ADMIN_INSTALL_TIMESTAMP_OPTION_NAME =
	'woocommerce_admin_install_timestamp';
const ALLOW_TRACKING_OPTION_NAME = 'woocommerce_allow_tracking';

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object}   props                    Component props.
 * @param {string}   props.action             The action name sent to Tracks.
 * @param {Object}   props.trackProps         Additional props sent to Tracks.
 * @param {string}   props.label              The label displayed in the modal.
 * @param {string}   props.onSubmitLabel      The label displayed upon survey submission.
 * @param {Array}    props.cesShownForActions The array of actions that the CES modal has been shown for.
 * @param {boolean}  props.allowTracking      Whether tracking is allowed or not.
 * @param {boolean}  props.resolving          Are values still being resolved.
 * @param {number}   props.storeAge           The age of the store in months.
 * @param {Function} props.updateOptions      Function to update options.
 * @param {Function} props.createNotice       Function to create a snackbar.
 */
function CustomerEffortScoreTracks( {
	action,
	trackProps,
	label,
	onSubmitLabel,
	cesShownForActions,
	allowTracking,
	resolving,
	storeAge,
	updateOptions,
	createNotice,
} ) {
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

	const openedCallback = () => {
		// Use the `shown` state value to only update the shown_for_actions
		// option once.
		if ( shown ) {
			return;
		}

		setShown( true );
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				action,
				...cesShownForActions,
			],
		} );
	};

	const trackCallback = ( score ) => {
		recordEvent( 'ces_feedback', {
			action,
			score,
			store_age: storeAge,
			...trackProps,
		} );
		createNotice( 'success', onSubmitLabel );
	};

	return (
		<CustomerEffortScore
			trackCallback={ trackCallback }
			label={ label }
			openedCallback={ openedCallback }
			icon={
				<span
					style={ { height: 21, width: 21 } }
					role="img"
					aria-label={ __( 'Pencil icon', 'woocommerce-admin' ) }
				>
					✏️
				</span>
			}
		/>
	);
}

CustomerEffortScoreTracks.propTypes = {
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
	 * The label for the snackbar that appears upon survey submission.
	 */
	onSubmitLabel: PropTypes.string.isRequired,
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
	/**
	 * Function to create a snackbar
	 */
	createNotice: PropTypes.func,
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
		const { createNotice } = dispatch( 'core/notices' );

		return {
			updateOptions,
			createNotice,
		};
	} )
)( CustomerEffortScoreTracks );
