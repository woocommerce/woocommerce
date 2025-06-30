/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { createElement, useState } from '@wordpress/element';
import { optionsStore } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import apiFetch from '@wordpress/api-fetch';

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
 * @typedef {Object} CustomerEffortScoreTracksProps
 * @property {string}   action               - The action name sent to Tracks
 * @property {Object}   [trackProps]         - Additional props sent to Tracks
 * @property {string}   title                - The title displayed in the modal
 * @property {string}   [description]        - Description shown in CES modal
 * @property {string}   [noticeLabel]        - Label for notice, defaults to title
 * @property {string}   [firstQuestion]      - The first survey question
 * @property {string}   [secondQuestion]     - The second survey question
 * @property {string}   [icon]               - Optional icon to show in notice
 * @property {string}   [onSubmitLabel]      - The label displayed upon survey submission
 * @property {string[]} [cesShownForActions] - The array of actions that the CES modal has been shown for
 * @property {boolean}  [allowTracking]      - Whether tracking is allowed or not
 * @property {boolean}  resolving            - Whether props are still being resolved
 * @property {number}   [storeAgeInWeeks]    - The age of the store in weeks
 * @property {Function} [createNotice]       - Function to create a snackbar
 */

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {CustomerEffortScoreTracksProps} props Component props
 * @return {JSX.Element} The rendered component
 */
function _CustomerEffortScoreTracks( {
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

		if ( ! cesShownForActions || ! cesShownForActions.includes( action ) ) {
			apiFetch( {
				path: 'wc-admin/options',
				method: 'POST',
				data: {
					[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
						action,
						...( cesShownForActions || [] ),
					],
				},
			} );
		}
	};

	const onNoticeDismissed = () => {
		recordEvent( 'ces_snackbar_dismiss', {
			action,
			store_age: storeAgeInWeeks,
			ces_location: 'inside',
			...trackProps,
		} );
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

/**
 * @typedef {import('react').ComponentType} ComponentType
 *
 * @type {ComponentType<CustomerEffortScoreTracksProps>}
 */
export const CustomerEffortScoreTracks = compose(
	withSelect( ( select ) => {
		const { getOption, hasFinishedResolution } = select( optionsStore );

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
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( _CustomerEffortScoreTracks );
