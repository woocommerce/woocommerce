/**
 * External dependencies
 */
import { createElement, useState, useEffect } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CustomerFeedbackModal } from '../customer-feedback-modal';

const noop = () => {};

type CustomerEffortScoreProps = {
	recordScoreCallback: (
		score: number,
		secondScore: number,
		comments: string
	) => void;
	title?: string;
	description?: string;
	showDescription?: boolean;
	noticeLabel?: string;
	firstQuestion: string;
	secondQuestion?: string;
	onNoticeShownCallback?: () => void;
	onNoticeDismissedCallback?: () => void;
	onModalShownCallback?: () => void;
	onModalDismissedCallback?: () => void;
	icon?: React.ReactElement | null;
	shouldShowComments?: (
		firstQuestionScore: number,
		secondQuestionScore: number
	) => boolean;
	getExtraFieldsToBeShown?: (
		extraFieldsValues: { [ key: string ]: string },
		setExtraFieldsValues: ( values: { [ key: string ]: string } ) => void,
		errors: Record< string, string > | undefined
	) => JSX.Element;
	validateExtraFields?: ( values: { [ key: string ]: string } ) => {
		[ key: string ]: string;
	};
};

/**
 * Use `CustomerEffortScore` to gather a customer effort score.
 *
 * NOTE: This should live in @woocommerce/customer-effort-score to allow
 * reuse.
 *
 * @param {Object}   props                           Component props.
 * @param {Function} props.recordScoreCallback       Function to call when the score should be recorded.
 * @param {string}   [props.title]                   The title displayed in the modal.
 * @param {string}   props.description               The description displayed in the modal.
 * @param {boolean}  props.showDescription           Show description in the modal.
 * @param {string}   props.noticeLabel               The notice label displayed in the notice.
 * @param {string}   props.firstQuestion             The first survey question.
 * @param {string}   [props.secondQuestion]          The second survey question.
 * @param {Function} props.onNoticeShownCallback     Function to call when the notice is shown.
 * @param {Function} props.onNoticeDismissedCallback Function to call when the notice is dismissed.
 * @param {Function} props.onModalShownCallback      Function to call when the modal is shown.
 * @param {Function} props.onModalDismissedCallback  Function to call when modal is dismissed.
 * @param {Function} props.shouldShowComments        Callback to determine if comments section should be shown.
 * @param {Object}   props.icon                      Icon (React component) to be shown on the notice.
 * @param {Function} props.getExtraFieldsToBeShown   Function that returns the extra fields to be shown.
 * @param {Function} props.validateExtraFields       Function that validates the extra fields.
 */
const CustomerEffortScore: React.VFC< CustomerEffortScoreProps > = ( {
	recordScoreCallback,
	title,
	description,
	showDescription = true,
	noticeLabel,
	firstQuestion,
	secondQuestion,
	onNoticeShownCallback = noop,
	onNoticeDismissedCallback = noop,
	onModalShownCallback = noop,
	onModalDismissedCallback = noop,
	icon,
	shouldShowComments = ( firstQuestionScore, secondQuestionScore ) =>
		[ firstQuestionScore, secondQuestionScore ].some(
			( score ) => score === 1 || score === 2
		),
	getExtraFieldsToBeShown,
	validateExtraFields,
} ) => {
	const [ shouldCreateNotice, setShouldCreateNotice ] = useState( true );
	const [ visible, setVisible ] = useState( false );
	const { createNotice } = useDispatch( 'core/notices2' );

	useEffect( () => {
		if ( ! shouldCreateNotice ) {
			return;
		}

		createNotice( 'success', noticeLabel || title, {
			actions: [
				{
					label: __( 'Give feedback', 'woocommerce' ),
					onClick: () => {
						setVisible( true );
						onModalShownCallback();
					},
				},
			],
			icon,
			explicitDismiss: true,
			onDismiss: onNoticeDismissedCallback,
		} );

		setShouldCreateNotice( false );

		onNoticeShownCallback();
	}, [ shouldCreateNotice ] );

	if ( shouldCreateNotice ) {
		return null;
	}

	if ( ! visible ) {
		return null;
	}

	return (
		<CustomerFeedbackModal
			title={ title }
			description={ description }
			showDescription={ showDescription }
			firstQuestion={ firstQuestion }
			secondQuestion={ secondQuestion }
			recordScoreCallback={ recordScoreCallback }
			onCloseModal={ onModalDismissedCallback }
			shouldShowComments={ shouldShowComments }
			getExtraFieldsToBeShown={ getExtraFieldsToBeShown }
			validateExtraFields={ validateExtraFields }
		/>
	);
};

CustomerEffortScore.propTypes = {
	/**
	 * The function to call to record the score.
	 */
	recordScoreCallback: PropTypes.func.isRequired,
	/**
	 * The title displayed in the modal.
	 */
	title: PropTypes.string,
	/**
	 * The function to call when the notice is shown.
	 */
	onNoticeShownCallback: PropTypes.func,
	/**
	 * The function to call when the notice is dismissed.
	 */
	onNoticeDismissedCallback: PropTypes.func,
	/**
	 * The function to call when the modal is shown.
	 */
	onModalShownCallback: PropTypes.func,
	/**
	 * Icon (React component) to be displayed.
	 */
	icon: PropTypes.element,
	/**
	 * The first survey question.
	 */
	firstQuestion: PropTypes.string.isRequired,
	/**
	 * The second survey question.
	 */
	secondQuestion: PropTypes.string,
	/**
	 * A function to determine whether or not the comments field shown be shown.
	 */
	shouldShowComments: PropTypes.func,
};

export { CustomerEffortScore };
