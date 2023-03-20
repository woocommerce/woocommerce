/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { createElement, useState } from '@wordpress/element';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CustomerEffortScore } from '../';
import {
	ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
	ALLOW_TRACKING_OPTION_NAME,
	SHOWN_FOR_ACTIONS_OPTION_NAME,
} from '../../constants';
import { getStoreAgeInWeeks } from '../../utils';

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object}   props                    Component props.
 * @param {string}   props.action             The action name sent to Tracks.
 * @param {Object}   props.trackProps         Additional props sent to Tracks.
 * @param {string}   props.title              The title displayed in the modal.
 * @param {string}   props.noticeLabel        Label for notice, defaults to title.
 * @param {string}   props.description        Description shown in CES modal.
 * @param {string}   props.firstQuestion      The first survey question.
 * @param {string}   props.secondQuestion     The second survey question.
 * @param {string}   props.icon               Optional icon to show in notice.
 * @param {string}   props.onSubmitLabel      The label displayed upon survey submission.
 * @param {Array}    props.cesShownForActions The array of actions that the CES modal has been shown for.
 * @param {boolean}  props.allowTracking      Whether tracking is allowed or not.
 * @param {boolean}  props.resolving          Are values still being resolved.
 * @param {number}   props.storeAgeInWeeks    The age of the store in weeks.
 * @param {Function} props.updateOptions      Function to update options.
 * @param {Function} props.createNotice       Function to create a snackbar.
 */
function CustomerEffortScoreTracks( {
	action,
	trackProps,
	title,
	description,
	noticeLabel,
	firstQuestion,
	secondQuestion,
	icon,
	onSubmitLabel = __( 'Thank you for your feedback!', 'woocommerce' ),
	cesShownForActions,
	allowTracking,
	resolving,
	storeAgeInWeeks,
	updateOptions,
	createNotice,
} ) {
	const [ modalShown, setModalShown ] = useState( false );

	if ( resolving ) {
		return null;
	}

	// Don't show if tracking is disallowed.
	if ( ! allowTracking ) {
		return null;
	}

	// We only want to return null early if the modal was already shown
	// for this action *before* this component was initially instantiated.
	//
	// We want to make sure we still render CustomerEffortScore below
	// (we don't want to return null early), if the modal was shown for this
	// instantiation, so that the component doesn't go away while we are
	// still showing it.
	if (
		cesShownForActions &&
		cesShownForActions.indexOf( action ) !== -1 &&
		! modalShown
	) {
		return null;
	}

	const onNoticeShown = () => {
		recordEvent( 'ces_snackbar_view', {
			action,
			store_age: storeAgeInWeeks,
			ces_location: 'inside',
			...trackProps,
		} );
	};

	const addActionToShownOption = () => {
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				action,
				...( cesShownForActions || [] ),
			],
		} );
	};

	const onNoticeDismissed = () => {
		recordEvent( 'ces_snackbar_dismiss', {
			action,
			store_age: storeAgeInWeeks,
			ces_location: 'inside',
			...trackProps,
		} );

		addActionToShownOption();
	};

	const onModalDismissed = () => {
		recordEvent( 'ces_view_dismiss', {
			action,
			store_age: storeAgeInWeeks,
			ces_location: 'inside',
			...trackProps,
		} );
	};

	const onModalShown = () => {
		setModalShown( true );

		recordEvent( 'ces_view', {
			action,
			store_age: storeAgeInWeeks,
			ces_location: 'inside',
			...trackProps,
		} );

		addActionToShownOption();
	};

	const recordScore = ( score, secondScore, comments ) => {
		recordEvent( 'ces_feedback', {
			action,
			score,
			score_second_question: secondScore,
			score_combined: score + secondScore,
			comments: comments || '',
			store_age: storeAgeInWeeks,
			ces_location: 'inside',
			...trackProps,
		} );
		createNotice( 'success', onSubmitLabel );
	};

	return (
		<CustomerEffortScore
			recordScoreCallback={ recordScore }
			title={ title }
			description={ description }
			noticeLabel={ noticeLabel }
			firstQuestion={ firstQuestion }
			secondQuestion={ secondQuestion }
			onNoticeShownCallback={ onNoticeShown }
			onNoticeDismissedCallback={ onNoticeDismissed }
			onModalShownCallback={ onModalShown }
			onModalDismissedCallback={ onModalDismissed }
			icon={
				<span
					style={ { height: 21, width: 21 } }
					role="img"
					aria-label={ __( 'Pencil icon', 'woocommerce' ) }
				>
					{ icon || '✏' }
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
	title: PropTypes.string.isRequired,
	/**
	 * The label for the snackbar that appears upon survey submission.
	 */
	onSubmitLabel: PropTypes.string,
	/**
	 * The array of actions that the CES modal has been shown for.
	 */
	cesShownForActions: PropTypes.arrayOf( PropTypes.string ),
	/**
	 * Whether tracking is allowed or not.
	 */
	allowTracking: PropTypes.bool,
	/**
	 * Whether props are still being resolved.
	 */
	resolving: PropTypes.bool.isRequired,
	/**
	 * The age of the store in weeks.
	 */
	storeAgeInWeeks: PropTypes.number,
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
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const cesShownForActions = getOption( SHOWN_FOR_ACTIONS_OPTION_NAME );

		const adminInstallTimestamp =
			getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) || 0;
		const storeAgeInWeeks = getStoreAgeInWeeks( adminInstallTimestamp );

		const allowTrackingOption =
			getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';
		const allowTracking = allowTrackingOption === 'yes';

		const resolving =
			! hasFinishedResolution( 'getOption', [
				SHOWN_FOR_ACTIONS_OPTION_NAME,
			] ) ||
			storeAgeInWeeks === null ||
			! hasFinishedResolution( 'getOption', [
				ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
			] ) ||
			! hasFinishedResolution( 'getOption', [
				ALLOW_TRACKING_OPTION_NAME,
			] );

		return {
			cesShownForActions,
			allowTracking,
			storeAgeInWeeks,
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
