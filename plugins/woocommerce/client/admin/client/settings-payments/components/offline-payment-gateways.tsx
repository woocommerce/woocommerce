/**
 * External dependencies
 */
import { Card, CardHeader } from '@wordpress/components';
import React from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { List } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { PAYMENT_GATEWAYS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { PaymentGatewayButton } from './payment-gateway-button';

const assetUrl = getAdminSetting( 'wcAdminAssetUrl' );

export const OfflinePaymentGateways = () => {
	const { installedPaymentGateways } = useSelect( ( select ) => {
		return {
			getPaymentGateway: select( PAYMENT_GATEWAYS_STORE_NAME )
				.getPaymentGateway,
			installedPaymentGateways: select(
				PAYMENT_GATEWAYS_STORE_NAME
			).getPaymentGateways(),
		};
	}, [] );

	// Mock payment gateways for now.
	// TODO Get the list of gateways via the API in future PR.
	const mockOfflinePaymentGateways = [
		{
			id: 'bacs',
			title: __( 'Direct bank transfer', 'woocommerce' ),
			content: __(
				'Take payments in person via BACS. More commonly known as direct bank/wire transfer.',
				'woocommerce'
			),
			image: assetUrl + '/payment_methods/cod.svg',
			square_image: assetUrl + '/payment_methods/cod.svg',
			image_72x72: assetUrl + '/payment_methods/cod.svg',
			actionText: '',
		},
		{
			id: 'cheque',
			title: __( 'Check payments', 'woocommerce' ),
			content: __(
				'Take payments in person via checks. This offline gateway can also be useful to test purchases.',
				'woocommerce'
			),
			image: assetUrl + '/payment_methods/cheque.svg',
			square_image: assetUrl + '/payment_methods/cheque.svg',
			image_72x72: assetUrl + '/payment_methods/cheque.svg',
			actionText: '',
		},
		{
			id: 'cod',
			title: __( 'Cash on delivery', 'woocommerce' ),
			content: __(
				'Have your customers pay with cash (or by other means) upon delivery.',
				'woocommerce'
			),
			image: assetUrl + '/payment_methods/cod.svg',
			square_image: assetUrl + '/payment_methods/cod.svg',
			image_72x72: assetUrl + '/payment_methods/cod.svg',
			actionText: '',
		},
	];

	const availableOfflineGateways = mockOfflinePaymentGateways
		.map( ( gateway ) => {
			const installedGateway = installedPaymentGateways.find(
				( g ) => g.id === gateway.id
			);

			if ( ! installedGateway ) {
				return null;
			}

			return {
				enabled: installedGateway.enabled,
				settings_url: installedGateway.settings_url,
				...gateway,
			};
		} )
		.filter( Boolean );

	// Transform plugins comply with List component format.
	const paymentGatewaysList = availableOfflineGateways.map( ( gateway ) => {
		if ( ! gateway ) return null;
		return {
			key: gateway.id,
			title: <>{ gateway.title }</>,
			content: <>{ decodeEntities( gateway.content ) }</>,
			after: (
				<PaymentGatewayButton
					id={ gateway.id }
					enabled={ gateway.enabled }
					settings_url={ gateway.settings_url }
				/>
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

	return (
		<Card size="medium" className="settings-payment-gateways">
			<CardHeader>
				<div className="settings-payment-gateways__header-title">
					{ __( 'Payment methods', 'woocommerce' ) }
				</div>
			</CardHeader>
			<List items={ paymentGatewaysList } />
		</Card>
	);
};
