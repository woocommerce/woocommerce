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
import { CustomerFeedbackModal } from './customer-feedback-modal';

const noop = () => {};

type CustomerEffortScoreProps = {
	recordScoreCallback: ( score: number, comments: string ) => void;
	label: string;
	onNoticeShownCallback?: () => void;
	onNoticeDismissedCallback?: () => void;
	onModalShownCallback?: () => void;
	icon?: React.ReactNode;
};

/**
 * Use `CustomerEffortScore` to gather a customer effort score.
 *
 * NOTE: This should live in @woocommerce/customer-effort-score to allow
 * reuse.
 *
 * @param {Object}   props                           Component props.
 * @param {Function} props.recordScoreCallback       Function to call when the score should be recorded.
 * @param {string}   props.label                     The label displayed in the modal.
 * @param {Function} props.onNoticeShownCallback     Function to call when the notice is shown.
 * @param {Function} props.onNoticeDismissedCallback Function to call when the notice is dismissed.
 * @param {Function} props.onModalShownCallback      Function to call when the modal is shown.
 * @param {Object}   props.icon                      Icon (React component) to be shown on the notice.
 */
const CustomerEffortScore: React.FC< CustomerEffortScoreProps > = ( {
	recordScoreCallback,
	label,
	onNoticeShownCallback = noop,
	onNoticeDismissedCallback = noop,
	onModalShownCallback = noop,
	icon,
} ) => {
	const [ shouldCreateNotice, setShouldCreateNotice ] = useState( true );
	const [ visible, setVisible ] = useState( false );
	const { createNotice } = useDispatch( 'core/notices2' );

	useEffect( () => {
		if ( ! shouldCreateNotice ) {
			return;
		}

		createNotice( 'success', label, {
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
			label={ label }
			recordScoreCallback={ recordScoreCallback }
		/>
	);
};

CustomerEffortScore.propTypes = {
	/**
	 * The function to call to record the score.
	 */
	recordScoreCallback: PropTypes.func.isRequired,
	/**
	 * The label displayed in the modal.
	 */
	label: PropTypes.string.isRequired,
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
};

export { CustomerEffortScore };
