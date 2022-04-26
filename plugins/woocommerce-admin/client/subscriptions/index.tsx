/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import unconnectedImage from './subscriptions-empty-state-unconnected.svg';
import './style.scss';

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

const GetStartedButton = () => {
	const [ isGettingStarted, setIsGettingStarted ] = useState( false );

	return (
		<div className="wcpay-empty-subscriptions__button_container">
			<Button
				disabled={ isGettingStarted }
				href="https://example.com"
				isBusy={ isGettingStarted }
				isPrimary
				onClick={ () => {
					setIsGettingStarted( true );
				} }
			>
				{ __( 'Get started', 'woocommerce-payments' ) }
			</Button>
		</div>
	);
};

const SubscriptionsPage = () => (
	<div className="wcpay-empty-subscriptions__container">
		<img src={ unconnectedImage } alt="" />
		<p className="wcpay-empty-subscriptions__description">
			{ __(
				'Track recurring revenue and manage active subscriptions directly from your store’s dashboard — powered by WooCommerce Payments.',
				'woocommerce-payments'
			) }
		</p>
		<TOS />
		<GetStartedButton />
	</div>
);

export default SubscriptionsPage;
