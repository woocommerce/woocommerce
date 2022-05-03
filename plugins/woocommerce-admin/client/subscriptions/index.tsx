/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import unconnectedImage from './subscriptions-empty-state-unconnected.svg';
import './style.scss';

declare global {
	interface Window {
		wcWcpaySubscriptions: {
			newSubscriptionProductUrl: string;
			onboardingUrl: string;
		};
	}
}

const {
	newSubscriptionProductUrl,
	onboardingUrl,
} = window.wcWcpaySubscriptions;

const ErrorNotice = ( { isError }: { isError: boolean } ) => {
	if ( ! isError ) {
		return null;
	}

	return (
		<Notice
			className="wcpay-empty-subscriptions__error"
			status="error"
			isDismissible={ false }
		>
			{ createInterpolateElement(
				__(
					'WooCommerce Payments failed to install. To continue with WooCommerce Payments built-in subscriptions functionality, please install <a>WooCommerce Payments</a> manually.',
					'woocommerce-payments'
				),
				{
					a: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href="https://woocommerce.com/payments"
							target="_blank"
							rel="noreferrer"
						/>
					),
				}
			) }
		</Notice>
	);
};

const TOS = () => (
	<p className="wcpay-empty-subscriptions__tos">
		{ createInterpolateElement(
			__(
				'By clicking "Get started", you agree to the <a>Terms of Service</a>',
				'woocommerce-payments'
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
	</p>
);

// eslint-disable-next-line @typescript-eslint/ban-types
const GetStartedButton = ( { setIsError }: { setIsError: Function } ) => {
	const [ isGettingStarted, setIsGettingStarted ] = useState( false );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );

	return (
		<div className="wcpay-empty-subscriptions__button_container">
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
							// eslint-disable-next-line no-console
							console.log( 'It was a success!' );
						} )
						.catch( () => {
							setIsGettingStarted( false );
							setIsError( true );
						} );
				} }
			>
				{ __( 'Get started', 'woocommerce-payments' ) }
			</Button>
		</div>
	);
};

const SubscriptionsPage = () => {
	const [ isError, setIsError ] = useState( false );

	return (
		<div className="wcpay-empty-subscriptions__container">
			<ErrorNotice isError={ isError } />
			<img src={ unconnectedImage } alt="" />
			<p className="wcpay-empty-subscriptions__description">
				{ __(
					'Track recurring revenue and manage active subscriptions directly from your store’s dashboard — powered by WooCommerce Payments.',
					'woocommerce-payments'
				) }
			</p>
			<TOS />
			<GetStartedButton setIsError={ setIsError } />
		</div>
	);
};

export default SubscriptionsPage;
