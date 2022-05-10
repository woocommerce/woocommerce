/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { Button, Card, CardBody, Notice } from '@wordpress/components';
import { PLUGINS_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Visa from './cards/visa.js';
import MasterCard from './cards/mastercard.js';
import Amex from './cards/amex.js';
import DinersClub from './cards/diners.js';
import Discover from './cards/discover.js';
import JCB from './cards/jcb.js';
import UnionPay from './cards/unionpay.js';
import './style.scss';
declare global {
	interface Window {
		wcWcpaySubscriptions: {
			newSubscriptionProductUrl: string;
			onboardingUrl: string;
			noThanksUrl: string;
			dismissOptionKey: string;
		};
	}
}

const {
	newSubscriptionProductUrl,
	onboardingUrl,
	noThanksUrl,
	dismissOptionKey,
} = window.wcWcpaySubscriptions;

type setHasErrorFunction = React.Dispatch< React.SetStateAction< boolean > >;

const ErrorNotice = () => {
	return (
		<Notice
			className="wcpay-empty-subscriptions__error"
			status="error"
			isDismissible={ false }
		>
			{ createInterpolateElement(
				__(
					'Installing WooCommerce Payments failed. To continue with WooCommerce Payments built-in subscriptions functionality, please install <a>WooCommerce Payments</a> manually.',
					'woocommerce'
				),
				{
					a: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href="https://wordpress.org/plugins/woocommerce-payments/"
							target="_blank"
							rel="noreferrer"
						/>
					),
				}
			) }
		</Notice>
	);
};

const NoThanksButton = () => {
	const [ isNoThanksClicked, setIsNoThanksClicked ] = useState( false );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	return (
		<Button
			isBusy={ isNoThanksClicked }
			isSecondary
			onClick={ () => {
				setIsNoThanksClicked( true );
				recordEvent(
					'wccore_subscriptions_empty_state_no_thanks_click',
					{}
				);
				updateOptions( {
					[ dismissOptionKey ]: 'yes',
					// @ts-expect-error updateOptions returns a Promise, but it is not typed in source.
				} ).then( () => {
					window.location.href = noThanksUrl;
				} );
			} }
		>
			{ __( 'No thanks', 'woocommerce' ) }
		</Button>
	);
};

type GetStartedButtonProps = {
	setHasError: setHasErrorFunction;
};

const GetStartedButton: React.FC< GetStartedButtonProps > = ( {
	setHasError,
} ) => {
	const [ isGettingStarted, setIsGettingStarted ] = useState( false );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );

	return (
		<Button
			disabled={ isGettingStarted }
			isBusy={ isGettingStarted }
			isPrimary
			onClick={ () => {
				setIsGettingStarted( true );
				recordEvent(
					'wccore_subscriptions_empty_state_get_started_click',
					{}
				);
				installAndActivatePlugins( [ 'woocommerce-payments' ] )
					.then( () => {
						/*
						 * TODO:
						 * Navigate to either newSubscriptionProductUrl or onboardingUrl
						 * depending on the which treatment the user is assigned to.
						 */
						window.location.href = newSubscriptionProductUrl;
					} )
					.catch( () => {
						recordEvent(
							'wccore_subscriptions_empty_state_get_started_error'
						);
						setIsGettingStarted( false );
						setHasError( true );
					} );
			} }
		>
			{ __( 'Get started', 'woocommerce' ) }
		</Button>
	);
};

const StepNumber: React.FC = ( { children } ) => (
	<span className="wcpay-empty-subscriptions-page-step-number">
		{ children }
	</span>
);

const TermsOfService = () => (
	<span className="wcpay-empty-subscriptions-page-terms-of-service">
		{ createInterpolateElement(
			__(
				'By clicking “Get started”, the WooCommerce Payments plugin will be installed and you agree to the <a>Terms of Service</a>',
				'woocommerce'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://wordpress.com/tos/"
						target="_blank"
						rel="noreferrer"
					/>
				),
			}
		) }
	</span>
);

type MainContentProps = {
	setHasError: setHasErrorFunction;
};

const MainContent: React.FC< MainContentProps > = ( { setHasError } ) => {
	return (
		<>
			<h2>
				{ __( 'Start selling subscriptions today', 'woocommerce' ) }
			</h2>
			<p>
				{ __(
					'With WooCommerce Payments, you can sell subscriptions with no setup costs or monthly fees. Create subscription products, track recurring revenue, and manage subscribers directly from your store’s dashboard.',
					'woocommerce'
				) }
				<br />
				<a
					href="https://woocommerce.com/document/payments/subscriptions/"
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'Learn more', 'woocommerce' ) }
				</a>
			</p>

			<h3>{ __( 'Accepted payment methods', 'woocommerce' ) }</h3>

			<div className="wcpay-empty-subscriptions-page-payment-methods">
				<Visa />
				<MasterCard />
				<Amex />
				<DinersClub />
				<Discover />
				<UnionPay />
				<JCB />
			</div>

			<hr />

			<p className="subscriptions__action">
				<TermsOfService />
			</p>

			<div className="wcpay-empty-subscriptions__button_container">
				<GetStartedButton setHasError={ setHasError } />
				<NoThanksButton />
			</div>
		</>
	);
};

const OnboardingSteps = () => (
	<>
		<h2>
			{ __(
				'You’re only steps away from selling subscriptions',
				'woocommerce'
			) }
		</h2>
		<div className="subscriptions-page-onboarding-steps">
			<div className="subscriptions-page-onboarding-steps-item">
				<StepNumber>1</StepNumber>
				<h3>
					{ __( 'Create and connect your account', 'woocommerce' ) }
				</h3>
				<p>
					{ __(
						'To ensure safe and secure transactions, a WordPress.com account is required.',
						'woocommerce'
					) }
				</p>
			</div>
			<div className="subscriptions-page-onboarding-steps-item">
				<StepNumber>2</StepNumber>
				<h3>
					{ __( 'Provide a few business details', 'woocommerce' ) }
				</h3>
				<p>
					{ __(
						'Next we’ll ask you to verify your business and payment details to enable deposits.',
						'woocommerce'
					) }
				</p>
			</div>
			<div className="subscriptions-page-onboarding-steps-item">
				<StepNumber>3</StepNumber>
				<h3>{ __( 'Create subscriptions', 'woocommerce' ) }</h3>
				<p>
					{ __(
						'Finally, publish subscription products to offer on your store.',
						'woocommerce'
					) }
				</p>
			</div>
		</div>
	</>
);

const SubscriptionsPage = () => {
	const [ hasError, setHasError ] = useState( false );

	useEffect( () => {
		recordEvent( 'wccore_subscriptions_empty_state_view' );
	}, [] );

	return (
		<>
			{ hasError && <ErrorNotice /> }
			<div className="subscriptions-page">
				<div className="subscriptions">
					<Card className="subscriptions__card">
						<CardBody>
							<div className="content">
								<MainContent setHasError={ setHasError } />
							</div>
						</CardBody>
					</Card>
					<Card className="subscriptions__steps">
						<CardBody>
							<OnboardingSteps />
						</CardBody>
					</Card>
				</div>
			</div>
		</>
	);
};

export default SubscriptionsPage;
