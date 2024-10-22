/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PaymentGateway } from '@woocommerce/data';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */

export const PaymentGatewayButton = ( {
	id,
	enabled,
	settings_url,
	text_settings = __( 'Manage', 'woocommerce' ),
	text_enable = __( 'Enable', 'woocommerce' ),
}: Pick< PaymentGateway, 'id' | 'enabled' | 'settings_url' > & {
	text_settings?: string;
	text_enable?: string;
} ) => {
	const [ isEnabled, setIsEnabled ] = useState( enabled );
	const [ isLoading, setIsLoading ] = useState( false );

	const toggleEnabled = async () => {
		setIsLoading( true );

		if ( ! window.woocommerce_admin.nonces?.gateway_toggle ) {
			// eslint-disable-next-line no-console
			console.warn( 'Unexpected error: Nonce not found' );
			// Redirect to payment setting page if nonce is not found. Users should still be able to toggle the payment method from that page.
			window.location.href = settings_url;
			return;
		}

		try {
			const response = await fetch( window.woocommerce_admin.ajax_url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'woocommerce_toggle_gateway_enabled',
					security: window.woocommerce_admin.nonces?.gateway_toggle,
					gateway_id: id,
				} ),
			} );

			const result = await response.json();

			if ( result.success ) {
				if ( result.data === true ) {
					setIsEnabled( true );
				} else if ( result.data === false ) {
					setIsEnabled( false );
				} else if ( result.data === 'needs_setup' ) {
					window.location.href = settings_url;
				}
			} else {
				window.location.href = settings_url;
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error toggling gateway:', error );
		} finally {
			setIsLoading( false );
		}
	};

	const onClick = ( e: React.MouseEvent ) => {
		if ( ! isEnabled ) {
			e.preventDefault();
			toggleEnabled();
		}
	};

	return (
		<div className="woocommerce-list__item-after__actions">
			<Button
				variant={ isEnabled ? 'secondary' : 'primary' }
				isBusy={ isLoading }
				disabled={ isLoading }
				onClick={ onClick }
				href={ settings_url }
			>
				{ isEnabled ? text_settings : text_enable }
			</Button>
		</div>
	);
};
