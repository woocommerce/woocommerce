/**
 * External dependencies
 */
import React from 'react';
import { Gridicon } from '@automattic/components';
import { Button, Card, CardHeader, SelectControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { EllipsisMenu, List, Pill } from '@woocommerce/components';
import { WooPaymentMethodsLogos } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */

export const MainPaymentMethods = () => {
	// Mock payment providers for now.
	// TODO Get the list of plugins via the API in future PR.
	const paymentProviders = [
		{
			id: 'woocommerce_payments',
			title: __( 'Accept payments with Woo', 'woocommerce' ),
			content: __(
				'Credit/debit cards, Apple Pay, Google Pay & more.',
				'woocommerce'
			),
			image: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/wcpay.svg',
			square_image:
				'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/woocommerce.svg',
			image_72x72:
				'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/wcpay.svg',
			actionText: '',
			recommended: true,
		},
		{
			id: 'ppcp-gateway',
			title: 'PayPal Payments',
			content:
				"Safe and secure payments using credit cards or your customer's PayPal account.",
			image: 'https://woocommerce.com/wp-content/plugins/woocommerce/assets/images/paypal.png',
			image_72x72:
				'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/72x72/paypal.png',
			square_image:
				'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/paypal.svg',
			actionText: '',
			recommended: false,
		},
	];

	// Transform plugins comply with List component format.
	const pluginsList = paymentProviders.map( ( plugin ) => {
		return {
			key: plugin.id,
			title: (
				<>
					{ plugin.title }
					{ plugin.recommended && (
						<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
					) }
				</>
			),
			content: (
				<>
					{ decodeEntities( plugin.content ) }
					{ plugin.id === 'woocommerce_payments' && (
						<WooPaymentMethodsLogos
							maxElements={ 10 }
							isWooPayEligible={ true }
						/>
					) }
				</>
			),
			after: (
				<div className="woocommerce-list__item-after__actions">
					<Button
						variant={ 'primary' }
						// onClick={ () => ( console.log('test') ) }
						isBusy={ false }
						disabled={ false }
					>
						{ plugin.actionText ||
							__( 'Get started', 'woocommerce' ) }
					</Button>
					<EllipsisMenu
						label={ __( 'Task List Options', 'woocommerce' ) }
						renderContent={ () => (
							<div>
								<Button>Learn more</Button>
								<Button>See Terms of Service</Button>
							</div>
						) }
					/>
				</div>
			),
			// TODO add drag-and-drop icon before image
			before: (
				<img
					src={
						plugin.square_image ||
						plugin.image_72x72 ||
						plugin.image
					}
					alt=""
				/>
			),
		};
	} );

	// Add offline payment provider.
	pluginsList.push( {
		key: 'offline',
		title: <>{ __( 'Offline payment methods', 'woocommerce' ) }</>,
		content: (
			<>
				{ __(
					'Take payments via multiple offline methods. These can also be useful to test purchases.',
					'woocommerce'
				) }
			</>
		),
		after: <Gridicon icon="chevron-right" />,
		// todo change logo to appropriate one.
		before: (
			<img
				src={
					'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/paypal.svg'
				}
				alt=""
			/>
		),
	} );

	return (
		<Card size="medium" className="main-payment-providers">
			<CardHeader>
				<div className="settings-payment-providers__header-title">
					{ __( 'Payment providers', 'woocommerce' ) }
				</div>
				<div className="settings-payment-providers__header-select-container">
					<SelectControl
						className="woocommerce-profiler-select-control__country"
						prefix={ 'Business location :' }
						placeholder={ '' }
						label={ '' }
						options={ [
							{ label: 'United States', value: 'US' },
							{ label: 'Canada', value: 'Canada' },
						] }
						onChange={ () => {} }
					/>
				</div>
			</CardHeader>
			<List items={ pluginsList } />
		</Card>
	);
};
