/**
 * External dependencies
 */
import { Card, CardBody, CardHeader, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import strings from './strings';
import GiftIcon from './gift';
import WCPayOfferIcon from './wcpay-offer.svg';
import ExitSurveyModal from './exit-survey-modal';
import PaymentMethods from './payment-methods';

const LearnMore = () => {
	const handleClick = () => {
		recordEvent( 'wcpay_welcome_learn_more', {} );
	};
	return (
		<a
			onClick={ handleClick }
			href="https://woocommerce.com/payments/"
			target="_blank"
			rel="noreferrer"
		>
			{ strings.learnMore }
		</a>
	);
};

const Banner = ( {
	isSubmitted,
	handleSetup,
}: {
	isSubmitted: boolean;
	handleSetup: () => void;
} ) => {
	const [ isNoThanksClicked, setNoThanksClicked ] = useState( false );

	const [ isExitSurveyModalOpen, setExitSurveyModalOpen ] = useState( false );

	const handleNoThanks = () => {
		setNoThanksClicked( true );
		setExitSurveyModalOpen( true );
	};

	return (
		<Card className="connect-account__card">
			<CardHeader>
				<div>
					<h1 className="banner-heading-copy">
						{ strings.bannerHeading }
					</h1>
					<p className="wcpay-connect-account-page-terms-of-service">
						{ strings.terms }
					</p>
				</div>
				<div className="connect-account__action">
					<Button
						isSecondary
						isBusy={ isNoThanksClicked && isExitSurveyModalOpen }
						disabled={ isNoThanksClicked && isExitSurveyModalOpen }
						onClick={ handleNoThanks }
						className="btn-nothanks"
					>
						{ strings.nothanks }
					</Button>
					<Button
						isPrimary
						isBusy={ isSubmitted }
						disabled={ isSubmitted }
						onClick={ handleSetup }
						className="btn-install"
					>
						{ strings.button }
					</Button>
					{ isExitSurveyModalOpen && (
						<ExitSurveyModal
							setExitSurveyModalOpen={ setExitSurveyModalOpen }
						/>
					) }
				</div>
			</CardHeader>
			<CardBody>
				<div className="content">
					<p className="onboarding-description">
						{ strings.onboarding.description } <LearnMore />
					</p>

					<p className="accepted-payment-methods">
						{ strings.paymentMethodsHeading }
					</p>

					<PaymentMethods />
				</div>
			</CardBody>
			<CardBody>
				<div className="limited-time-offer">
					<div className="offer-header">
						<GiftIcon className="gift-icon" />
						{ strings.limitedTimeOffer }
					</div>
					<h1 className="offer-copy">{ strings.bannerCopy }</h1>
					<p>{ strings.discountCopy }</p>
					<p>{ strings.termsAndConditions }</p>
				</div>
				<img
					className="banner-offer-icon"
					src={ WCPayOfferIcon }
					alt="WCPay Offer icon"
				/>
			</CardBody>
		</Card>
	);
};

export default Banner;
