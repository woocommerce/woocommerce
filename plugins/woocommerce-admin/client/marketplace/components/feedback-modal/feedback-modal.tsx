/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Modal, Button, TextareaControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useContext, useEffect, useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './feedback-modal.scss';
import LikertScale from '../likert-scale/likert-scale';
import { MarketplaceContext } from '../../contexts/marketplace-context';

export default function FeedbackModal(): JSX.Element {
	const CUSTOMER_EFFORT_SCORE_ACTION = 'marketplace_redesign_2023';
	const LOCALSTORAGE_KEY_DISMISSAL_COUNT =
		'marketplace_redesign_2023_dismissals'; // Ensure we don't ask for feedback if the
	// user's already given feedback or declined to multiple times
	const LOCALSTORAGE_KEY_LAST_REQUESTED_DATE =
		'marketplace_redesign_2023_last_shown_date'; // Ensure we don't ask for feedback more
	// than once per day
	const SUPPRESS_IF_DISMISSED_X_TIMES = 1; // If the user dismisses the snackbar this many
	// times, stop asking for feedback
	const SUPPRESS_IF_AFTER_DATE = '2024-01-01'; // If this date is reached, stop asking for
	// feedback
	const SNACKBAR_TIMEOUT = 5000; // How long we wait before asking for feedback

	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading } = marketplaceContextValue;

	// Save that we dismissed the dialog or snackbar TODAY so we don't show it again until tomorrow (if ever)
	const dismissToday = () =>
		localStorage.setItem(
			LOCALSTORAGE_KEY_LAST_REQUESTED_DATE,
			new Date().toDateString()
		);

	// Returns the number of times that the request for feedback has been dismissed
	const dismissedTimes = () =>
		parseInt(
			localStorage.getItem( LOCALSTORAGE_KEY_DISMISSAL_COUNT ) || '0',
			10
		);

	// Increment the number of times that the request for feedback has been dismissed
	const incrementDismissedTimes = () => {
		dismissToday();
		localStorage.setItem(
			LOCALSTORAGE_KEY_DISMISSAL_COUNT,
			`${ dismissedTimes() + 1 }`
		);
	};

	// Dismiss forever (by incrementing the number of dismissals to a high number), e.g. when feedback is provided
	const dismissForever = () => {
		dismissToday();
		localStorage.setItem(
			LOCALSTORAGE_KEY_DISMISSAL_COUNT,
			`${ SUPPRESS_IF_DISMISSED_X_TIMES }`
		);
	};

	// Returns true if dismissed forever (either by dismissing at least SUPPRESS_IF_DISMISSED_X_TIMES times, or by submitting feedback)
	const isDismissedForever = () =>
		dismissedTimes() >= SUPPRESS_IF_DISMISSED_X_TIMES;

	const [ isOpen, setOpen ] = useState( false );
	const [ thoughts, setThoughts ] = useState( '' );
	const [ easyToFind, setEasyToFind ] = useState( 0 );
	const [ easyToFindValidiationFailed, setEasyToFindValidiationFailed ] =
		useState( false );
	const [ meetsMyNeeds, setMeetsMyNeeds ] = useState( 0 );
	const [ meetsMyNeedsValidiationFailed, setMeetsMyNeedsValidiationFailed ] =
		useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => {
		incrementDismissedTimes();
		setOpen( false );
	};
	const { createNotice } = useDispatch( 'core/notices' );

	function showSnackbar() {
		createNotice(
			'success',
			__( 'How easy is it to find an extension?', 'woocommerce' ),
			{
				type: 'snackbar',
				icon: (
					<>
						<svg
							color="#fff"
							strokeWidth="1.5"
							viewBox="0 0 28.873 8.9823"
							style={ { height: '8px', marginLeft: '-7px' } }
						>
							<path
								className="l"
								d="m4.1223 1.1216 19.12-0.014142 4.3982 3.38-4.3982 3.38-19.12-0.014142a3.34 3.34 0 0 1-2.39-0.97581 3.37 3.37 0 0 1 0.00707-4.773 3.34 3.34 0 0 1 2.383-0.98288z"
								stroke="#fff"
							/>
							<line
								className="l"
								x1="6.7669"
								x2="6.7669"
								y1="7.8533"
								y2="1.1216"
								stroke="#fff"
							/>
							<path
								className="l"
								d="m23.235 1.1146 4.4053 3.3729-4.3982 3.38a6.59 6.59 0 0 1-0.89096-3.3517 6.59 6.59 0 0 1 0.88388-3.4012z"
								stroke="#fff"
							/>
							<line
								className="l"
								x1="6.7669"
								x2="22.323"
								y1="4.4875"
								y2="4.4875"
								stroke="#fff"
							/>
						</svg>
					</>
				),
				explicitDismiss: true,
				onDismiss: incrementDismissedTimes,
				actions: [
					{
						onClick: openModal,
						label: __( 'Give feedback', 'woocommerce' ),
					},
				],
			}
		);
	}

	function maybeShowSnackbar() {
		// Don't show if we're still loading content
		if ( isLoading ) {
			return;
		}

		// Don't show if the user has already given feedback or otherwise suppressed
		if ( isDismissedForever() ) {
			return;
		}

		// Don't show if we've already shown today or user has declined today
		const today = new Date().toDateString();
		if (
			today ===
			localStorage.getItem( LOCALSTORAGE_KEY_LAST_REQUESTED_DATE )
		) {
			return;
		}

		const timer = setTimeout( showSnackbar, SNACKBAR_TIMEOUT );

		// Without this, navigating between screens will create a series of snackbars
		dismissToday();

		return () => {
			clearTimeout( timer );
		};
	}

	useEffect( maybeShowSnackbar, [ isLoading ] );

	// We don't want the "How easy was it to find an extension?" dialog to appear forever:
	const FEEDBACK_DIALOG_CAN_APPEAR =
		new Date( SUPPRESS_IF_AFTER_DATE ) > new Date();
	if ( ! FEEDBACK_DIALOG_CAN_APPEAR ) {
		return <></>;
	}

	function easyToFindChanged( value: number ) {
		setEasyToFindValidiationFailed( false );
		setEasyToFind( value );
	}

	function meetsMyNeedsChanged( value: number ) {
		setMeetsMyNeedsValidiationFailed( false );
		setMeetsMyNeeds( value );
	}

	function submit() {
		// Validate:
		if ( easyToFind === 0 || meetsMyNeeds === 0 ) {
			if ( easyToFind === 0 ) setEasyToFindValidiationFailed( true );
			if ( meetsMyNeeds === 0 ) setMeetsMyNeedsValidiationFailed( true );
			return;
		}

		// Send event to CES:
		recordEvent( 'ces_feedback', {
			action: CUSTOMER_EFFORT_SCORE_ACTION,
			score: easyToFind,
			score_second_question: meetsMyNeeds,
			score_combined: easyToFind + meetsMyNeeds,
			thoughts,
		} );
		// Close the modal:
		setOpen( false );
		// Ensure we don't ask for feedback again:
		dismissForever();
	}

	return (
		<>
			{ isOpen && (
				<Modal
					title={ __(
						'How easy was it to find an extension?',
						'woocommerce'
					) }
					onRequestClose={ closeModal }
					className="woocommerce-marketplace__feedback-modal"
				>
					<p>
						{ __(
							'Your feedback will help us create a better experience for people like you! Please tell us to what extent you agree or disagree with the statements below.',
							'woocommerce'
						) }
					</p>
					<LikertScale
						fieldName="extension_screen_easy_to_find"
						title={ __(
							'It was easy to find an extension',
							'woocommerce'
						) }
						onValueChange={ easyToFindChanged }
						validationFailed={ easyToFindValidiationFailed }
					/>
					<LikertScale
						fieldName="extension_screen_meets_my_needs"
						title={ __(
							'The Extensions screen’s functionality meets my needs',
							'woocommerce'
						) }
						onValueChange={ meetsMyNeedsChanged }
						validationFailed={ meetsMyNeedsValidiationFailed }
					/>
					<TextareaControl
						label={ __( 'Additional thoughts', 'woocommerce' ) }
						value={ thoughts }
						onChange={ ( value: string ) => setThoughts( value ) }
					/>
					<p className="woocommerce-marketplace__feedback-modal-buttons">
						<Button
							variant="tertiary"
							onClick={ closeModal }
							text={ __( 'Cancel', 'woocommerce' ) }
						/>
						<Button
							variant="primary"
							onClick={ submit }
							text={ __( 'Send', 'woocommerce' ) }
						/>
					</p>
				</Modal>
			) }
		</>
	);
}
