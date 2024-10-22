/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';
import { Button, Card, CardHeader, SelectControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { EllipsisMenu, List, Pill } from '@woocommerce/components';
import { WooPaymentMethodsLogos } from '@woocommerce/onboarding';
import { Gridicon } from '@automattic/components';

/**
 * Internal dependencies
 */

interface PaymentGateway {
	id: string;
	title: string;
	description: string;
	order: string | number;
	enabled: boolean;
	method_title: string;
	method_description: string;
	needs_setup: boolean;
	image?: string;
	image_72x72?: string;
	square_image?: string;
	actionText?: string;
	recommended?: boolean;
}

interface WooPaymentsData {
	isSupported: boolean;
	isOnboarded: boolean;
	isInTestMode: boolean;
}

const parseScriptTag = ( elementId: string ) => {
	const scriptTag = document.getElementById( elementId );
	return scriptTag ? JSON.parse( scriptTag.textContent || '' ) : [];
};

export const PaymentGateways = () => {
	const [ paymentGateways, setPaymentGateways ] = useState( [] );
	const [ wooPaymentsData, setWooPaymentsData ] = useState( {} );

	const recommendedGateways = [ 'woocommerce_payments' ];

	useEffect( () => {
		setWooPaymentsData(
			parseScriptTag( 'experimental_wc_settings_payments_woopayments' )
		);
		setPaymentGateways(
			parseScriptTag( 'experimental_wc_settings_payments_gateways' )
		);
	}, [] );

	const enableGateway = ( gateway: PaymentGateway ) => () => {
		console.log( 'Enable gateway', gateway );
	};

	const manageGateway = ( gateway: PaymentGateway ) => () => {
		console.log( 'Manage gateway', gateway );
	};

	const setupLivePayments = () => {
		console.log( 'Setup live payments' );
	};

	console.log( 'paymentGateways', paymentGateways );
	console.log( 'wooPaymentsData', wooPaymentsData );

	// Transform plugins comply with List component format.
	const paymentGatewaysList = paymentGateways.map(
		( gateway: PaymentGateway ) => {
			return {
				key: gateway.id,
				title: (
					<>
						{ gateway.method_title }
						{ recommendedGateways.includes( gateway.id ) && (
							// TODO: Support recommended flag
							<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
						) }
					</>
				),
				content: (
					<>
						{ decodeEntities( gateway.method_description ) }
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
						<>
							<Button
								variant={
									gateway.enabled ? 'secondary' : 'primary'
								}
								onClick={
									gateway.enabled
										? manageGateway( gateway )
										: enableGateway( gateway )
								}
								isBusy={ false }
								disabled={ false }
							>
								{ gateway.enabled
									? __( 'Manage', 'woocommerce' )
									: __( 'Enable', 'woocommerce' ) }
							</Button>
							{ gateway.id === 'woocommerce_payments' &&
								wooPaymentsData.isInTestMode && (
									<Button
										variant="primary"
										onClick={ setupLivePayments }
										isBusy={ false }
										disabled={ false }
									>
										{ __(
											'Set up live payments',
											'woocommerce'
										) }
									</Button>
								) }
							<EllipsisMenu
								label={ __(
									'Task List Options',
									'woocommerce'
								) }
								renderContent={ () => (
									<div>
										<Button>
											{ __(
												'Learn more',
												'woocommerce'
											) }
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
						</>
					</div>
				),
				// TODO add drag-and-drop icon before image (future PR)
				before: (
					<img
						src={
							gateway.square_image ||
							gateway.image_72x72 ||
							gateway.image ||
							'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/wcpay.svg'
						}
						alt={ gateway.title + ' logo' }
					/>
				),
			};
		}
	);

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
		after: <Gridicon icon="chevron-right" />,
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
						className="woocommerce-select-control__country"
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
