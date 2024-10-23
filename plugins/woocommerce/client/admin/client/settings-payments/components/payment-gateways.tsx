/**
 * External dependencies
 */
import { Gridicon } from '@automattic/components';
import { Button, Card, CardHeader, SelectControl } from '@wordpress/components';
import React from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { EllipsisMenu, List, Pill } from '@woocommerce/components';
import { WooPaymentMethodsLogos } from '@woocommerce/onboarding';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */

export const PaymentGateways = () => {
	// Mock payment gateways for now.
	// TODO Get the list of gateways via the API in future PR.
	const mockPaymentGateways = [
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
			content: __(
				"Safe and secure payments using credit cards or your customer's PayPal account.",
				'woocommerce'
			),
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
	const paymentGatewaysList = mockPaymentGateways.map( ( gateway ) => {
		return {
			key: gateway.id,
			title: (
				<>
					{ gateway.title }
					{ gateway.recommended && (
						<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
					) }
				</>
			),
			content: (
				<>
					{ decodeEntities( gateway.content ) }
					{ gateway.id === 'woocommerce_payments' && (
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
						{ gateway.actionText ||
							__( 'Get started', 'woocommerce' ) }
					</Button>
					<EllipsisMenu
						label={ __( 'Task List Options', 'woocommerce' ) }
						renderContent={ () => (
							<div>
								<Button>
									{ __( 'Learn more', 'woocommerce' ) }
								</Button>
								<Button>
									{ __(
										'See Terms of Service',
										'woocommerce'
									) }
								</Button>
							</div>
						) }
					/>
				</div>
			),
			// TODO add drag-and-drop icon before image (future PR)
			before: (
				<img
					src={
						gateway.square_image ||
						gateway.image_72x72 ||
						gateway.image
					}
					alt={ gateway.title + ' logo' }
				/>
			),
		};
	} );

	// Add offline payment provider.
	paymentGatewaysList.push( {
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
		after: (
			<a
				href={ getAdminLink(
					'admin.php?page=wc-settings&tab=checkout&section=offline'
				) }
			>
				<Gridicon icon="chevron-right" />
			</a>
		),
		// todo change logo to appropriate one.
		before: (
			<img
				src={
					'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/paypal.svg'
				}
				alt="offline payment methods"
			/>
		),
	} );

	return (
		<Card size="medium" className="settings-payment-gateways">
			<CardHeader>
				<div className="settings-payment-gateways__header-title">
					{ __( 'Payment providers', 'woocommerce' ) }
				</div>
				<div className="settings-payment-gateways__header-select-container">
					<SelectControl
						className="woocommerce-profiler-select-control__country"
						prefix={ __( 'Business location :', 'woocommerce' ) }
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
			<List items={ paymentGatewaysList } />
		</Card>
	);
};
