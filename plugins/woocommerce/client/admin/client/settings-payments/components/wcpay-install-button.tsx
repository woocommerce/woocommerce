/**
 * External dependencies
 */
import React, { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	PAYMENT_GATEWAYS_STORE_NAME,
	PLUGINS_STORE_NAME,
} from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { resolveSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

const slug = 'woocommerce-payments';
export const WCPayInstallButton = () => {
	const [ installing, setInstalling ] = useState( false );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	const redirectToSettings = async () => {
		const paymentGateway = await resolveSelect(
			PAYMENT_GATEWAYS_STORE_NAME
		).getPaymentGateway( slug.replace( /-/g, '_' ) );

		if ( paymentGateway?.settings_url ) {
			window.location.href = paymentGateway.settings_url;
		}
	};

	const installWooCommercePayments = async () => {
		if ( installing ) return;

		setInstalling( true );
		recordEvent( 'settings_payments_recommendations_setup', {
			extension_selected: slug,
		} );

		try {
			await installAndActivatePlugins( [ slug ] );
			redirectToSettings();
		} catch ( error ) {
			if ( error instanceof Error ) {
				createNotice( 'error', error.message );
			}
			setInstalling( false );
		}
	};

	return (
		<Button
			className="button alignright"
			onClick={ installWooCommercePayments }
			variant="secondary"
			isBusy={ installing }
			aria-disabled={ installing }
		>
			{ __( 'Install', 'woocommerce' ) }
		</Button>
	);
};
