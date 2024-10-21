/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import {
	Button,
	Modal,
	CheckboxControl,
	TextareaControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import strings from './strings';

/**
 * Provides a modal requesting customer feedback.
 *
 */
function ExitSurveyModal( {}: {
	// eslint-disable-next-line @typescript-eslint/ban-types
	setExitSurveyModalOpen: Function;
} ): JSX.Element | null {
	const incentive = getAdminSetting( 'wcpayWelcomePageIncentive' );
	const [ isOpen, setOpen ] = useState( true );
	const [ isHappyChecked, setHappyChecked ] = useState( false );
	const [ isInstallChecked, setInstallChecked ] = useState( false );
	const [ isMoreInfoChecked, setMoreInfoChecked ] = useState( false );
	const [ isAnotherTimeChecked, setAnotherTimeChecked ] = useState( false );
	const [ isSomethingElseChecked, setSomethingElseChecked ] =
		useState( false );
	const [ comments, setComments ] = useState( '' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const dismissedIncentives = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return (
			( getOption(
				'wcpay_welcome_page_incentives_dismissed'
			) as string[] ) || []
		);
	} );

	const closeModal = async () => {
		setOpen( false );

		// Record that the modal was dismissed.
		await updateOptions( {
			wcpay_welcome_page_incentives_dismissed: [
				...dismissedIncentives,
				incentive.id,
			],
		} );

		// Redirect back to the admin page.
		window.location.href = 'admin.php?page=wc-admin';
	};

	const exitSurvey = () => {
		recordEvent( 'wcpay_exit_survey', {
			just_remove: true,
			incentive_id: incentive.id,
		} );

		closeModal();
	};

	const sendFeedback = () => {
		recordEvent( 'wcpay_exit_survey', {
			happy: isHappyChecked ? 'Yes' : 'No',
			install: isInstallChecked ? 'Yes' : 'No',
			more_info: isMoreInfoChecked ? 'Yes' : 'No',
			another_time: isAnotherTimeChecked ? 'Yes' : 'No',
			something_else: isSomethingElseChecked ? 'Yes' : 'No',
			comments,
			incentive_id: incentive.id,
		} );

		if ( isMoreInfoChecked ) {
			// Record that the user would possibly consider installing WCPay with more information in the future.
			updateOptions( {
				wcpay_welcome_page_exit_survey_more_info_needed_timestamp:
					Math.floor( Date.now() / 1000 ),
			} );
		}
		closeModal();
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className="woopayments-welcome-page__survey"
			title={ strings.survey.title }
			onRequestClose={ closeModal }
			shouldCloseOnClickOutside={ false }
		>
			<p className="woopayments-welcome-page__survey-intro">
				{ strings.survey.intro }
			</p>

			<p className="woopayments-welcome-page__survey-question">
				{ strings.survey.question }
			</p>

			<div className="woopayments-welcome-page__survey-selection">
				<CheckboxControl
					label={ strings.survey.happyLabel }
					checked={ isHappyChecked }
					onChange={ setHappyChecked }
				/>
				<CheckboxControl
					label={ strings.survey.installLabel }
					checked={ isInstallChecked }
					onChange={ setInstallChecked }
				/>
				<CheckboxControl
					label={ strings.survey.moreInfoLabel }
					checked={ isMoreInfoChecked }
					onChange={ setMoreInfoChecked }
				/>
				<CheckboxControl
					label={ strings.survey.anotherTimeLabel }
					checked={ isAnotherTimeChecked }
					onChange={ setAnotherTimeChecked }
				/>
				<CheckboxControl
					label={ strings.survey.somethingElseLabel }
					checked={ isSomethingElseChecked }
					onChange={ setSomethingElseChecked }
				/>
			</div>

			<div className="woopayments-welcome-page__survey-comments">
				<TextareaControl
					label={ strings.survey.commentsLabel }
					value={ comments }
					onChange={ ( value: string ) => setComments( value ) }
					rows={ 3 }
				/>
			</div>

			<div className="woopayments-welcome-page__survey-buttons">
				<Button
					isTertiary
					isDestructive
					onClick={ exitSurvey }
					name="cancel"
				>
					{ strings.survey.cancelButton }
				</Button>
				<Button isSecondary onClick={ sendFeedback } name="send">
					{ strings.survey.submitButton }
				</Button>
			</div>
		</Modal>
	);
}

export default ExitSurveyModal;
