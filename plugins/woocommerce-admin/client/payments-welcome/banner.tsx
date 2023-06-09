/**
 * External dependencies
 */
import { Card, CardBody, Button, CardDivider } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import strings from './strings';
import WooPaymentsLogo from './woopayments.svg';
import ExitSurveyModal from './exit-survey-modal';
import PaymentMethods from './payment-methods';

interface Props {
	isSubmitted: boolean;
	handleSetup: () => void;
}

const Banner: React.FC< Props > = ( { isSubmitted, handleSetup } ) => {
	const [ isNoThanksClicked, setNoThanksClicked ] = useState( false );

	const [ isExitSurveyModalOpen, setExitSurveyModalOpen ] = useState( false );

	const handleNoThanks = () => {
		setNoThanksClicked( true );
		setExitSurveyModalOpen( true );
	};

	return (
		<Card className="__CLASS__">
			<CardBody className="woopayments-welcome-page__header">
				<img src={ WooPaymentsLogo } />
				<h1>{ strings.heading }</h1>
			</CardBody>
			<CardBody className="woopayments-welcome-page__offer">
				<div className="woopayments-welcome-page__offer-pill">
					{ strings.limitedTimeOffer }
				</div>
				<h2>{ strings.offerHeading }</h2>
				<Button
					variant="primary"
					isBusy={ isSubmitted }
					disabled={ isSubmitted }
					onClick={ handleSetup }
				>
					{ strings.install }
				</Button>
				<Button
					variant="tertiary"
					isBusy={ isNoThanksClicked && isExitSurveyModalOpen }
					disabled={ isNoThanksClicked && isExitSurveyModalOpen }
					onClick={ handleNoThanks }
				>
					{ strings.noThanks }
				</Button>
				<p>{ strings.TosAndPp }</p>
				<p>{ strings.termsAndConditions }</p>
			</CardBody>
			<CardDivider />
			<CardBody className="woopayments-welcome-page__payments">
				<p>{ strings.paymentOptions }</p>
				<PaymentMethods />
			</CardBody>
			{ isExitSurveyModalOpen && (
				<ExitSurveyModal
					setExitSurveyModalOpen={ setExitSurveyModalOpen }
				/>
			) }
		</Card>
	);
};

export default Banner;
