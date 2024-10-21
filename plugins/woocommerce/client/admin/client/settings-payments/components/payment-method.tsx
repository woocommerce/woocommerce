/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { PaymentGateway } from '@woocommerce/data';
import { WooPaymentMethodsLogos } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import sanitizeHTML from '~/lib/sanitize-html';
import { WCPayInstallButton } from './wcpay-install-button';

export const PaymentMethod = ( {
	id,
	enabled,
	title,
	method_title,
	method_description,
	settings_url,
}: PaymentGateway ) => {
	const isWooPayEligible = getAdminSetting( 'isWooPayEligible', false );
	const [ isEnabled, setIsEnabled ] = useState( enabled );
	const [ isLoading, setIsLoading ] = useState( false );

	const toggleEnabled = async ( e: React.MouseEvent ) => {
		e.preventDefault();
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

	return (
		<tr data-gateway_id={ id }>
			<td className="sort ui-sortable-handle" width="1%"></td>
			<td className="name" width="">
				<div className="wc-payment-gateway-method__name">
					<a
						href={ settings_url }
						className="wc-payment-gateway-method-title"
					>
						{ method_title }
					</a>
					{ id !== 'pre_install_woocommerce_payments_promotion' &&
						method_title !== title && (
							<span className="wc-payment-gateway-method-name">
								&nbsp;–&nbsp;
								{ title }
							</span>
						) }
					{ id === 'pre_install_woocommerce_payments_promotion' && (
						<div className="pre-install-payment-gateway__subtitle">
							<WooPaymentMethodsLogos
								isWooPayEligible={ isWooPayEligible }
								maxElements={ 5 }
							/>
						</div>
					) }
				</div>
			</td>
			<td className="status" width="1%">
				<a
					className="wc-payment-gateway-method-toggle-enabled"
					href={ settings_url }
					onClick={ toggleEnabled }
				>
					<span
						className={ `woocommerce-input-toggle ${
							isEnabled
								? 'woocommerce-input-toggle--enabled'
								: 'woocommerce-input-toggle--disabled'
						} ${
							isLoading ? 'woocommerce-input-toggle--loading' : ''
						}` }
						/* translators: %s: payment method title */
						aria-label={
							isEnabled
								? sprintf(
										/* translators: %s: payment method title */
										__(
											'The "%s" payment method is currently enabled',
											'woocommerce'
										),
										method_title
								  )
								: sprintf(
										/* translators: %s: payment method title */
										__(
											'The "%s" payment method is currently disabled',
											'woocommerce'
										),
										method_title
								  )
						}
					>
						{ isEnabled
							? __( 'Yes', 'woocommerce' )
							: __( 'No', 'woocommerce' ) }
					</span>
				</a>
			</td>
			<td
				className="description"
				width=""
				dangerouslySetInnerHTML={ sanitizeHTML( method_description ) }
			/>
			<td className="action" width="1%">
				{ id === 'pre_install_woocommerce_payments_promotion' ? (
					<WCPayInstallButton />
				) : (
					<a
						className="button alignright"
						aria-label={
							enabled
								? sprintf(
										/* translators: %s: payment method title */
										__(
											'Manage the "%s" payment method',
											'woocommerce'
										),
										method_title
								  )
								: sprintf(
										/* translators: %s: payment method title */
										__(
											'Set up the "%s" payment method',
											'woocommerce'
										),
										method_title
								  )
						}
						href={ settings_url }
					>
						{ enabled
							? __( 'Manage', 'woocommerce' )
							: __( 'Finish setup', 'woocommerce' ) }
					</a>
				) }
			</td>
		</tr>
	);
};
