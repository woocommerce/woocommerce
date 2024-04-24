/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import sanitizeHTML from '~/lib/sanitize-html';
import ExitSurveyModal from './exit-survey-modal';
import strings from './strings';
import { WCPayConnectCard } from '@woocommerce/onboarding';

interface Props {
	isSubmitted: boolean;
	handleSetup: () => void;
}

const Banner: React.FC< Props > = ( { isSubmitted, handleSetup } ) => {
	const { first_name } = getAdminSetting( 'currentUserData', {} );
	const { description, cta_label, tc_url } = getAdminSetting(
		'wcpayWelcomePageIncentive'
	);

	const [ isNoThanksClicked, setNoThanksClicked ] = useState( false );
	const [ isExitSurveyModalOpen, setExitSurveyModalOpen ] = useState( false );

	const isWooPayEligible = getAdminSetting( 'isWooPayEligible' );
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	const handleNoThanks = () => {
		setNoThanksClicked( true );
		setExitSurveyModalOpen( true );
	};

	return (
		<>
			<WCPayConnectCard
				actionButton={
					<div className="woopayments-welcome-page__offer">
						<div className="woopayments-welcome-page__offer-pill">
							{ strings.limitedTimeOffer }
						</div>
						<h2
							dangerouslySetInnerHTML={ sanitizeHTML(
								description +
									'<span class="tos-asterix">*</span>'
							) }
						/>
						<Button
							variant="primary"
							isBusy={ isSubmitted }
							disabled={ isSubmitted }
							onClick={ handleSetup }
						>
							{ cta_label }
						</Button>
						<Button
							variant="tertiary"
							isBusy={
								isNoThanksClicked && isExitSurveyModalOpen
							}
							disabled={
								isNoThanksClicked && isExitSurveyModalOpen
							}
							onClick={ handleNoThanks }
						>
							{ strings.noThanks }
						</Button>
						<p>{ strings.TosAndPp }</p>
						<p>{ strings.termsAndConditions( tc_url ) }</p>
					</div>
				}
				firstName={ first_name }
				businessCountry={ wccomSettings?.storeCountry ?? '' }
				isWooPayEligible={ isWooPayEligible }
				showNotice={ true }
			/>
			{ isExitSurveyModalOpen && (
				<ExitSurveyModal
					setExitSurveyModalOpen={ setExitSurveyModalOpen }
				/>
			) }
		</>
	);
};

export default Banner;
