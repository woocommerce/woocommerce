/**
 * External dependencies
 */
import { Button, ExternalLink, Modal } from '@wordpress/components';
import { Children, createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { WooPaymentsAffectedPaymentMethod } from './affected-payment-methods';

const PROVIDER_NAME = 'WooPayments';
const WOOPAYMENTS_DOC_URL = 'https://woocommerce.com/document/woopayments/';
const WOOCOMMERCE_SUPPORT_URL =
	'https://woocommerce.com/my-account/create-a-ticket/?select=5278104';

const AffectedPaymentMethodIcon = ( {
	method,
}: {
	method: WooPaymentsAffectedPaymentMethod;
} ) => {
	if ( method.iconUrl ) {
		return (
			<img
				className="woopayments-settings-disable-modal__method-icon"
				src={ method.iconUrl }
				alt=""
			/>
		);
	}

	return (
		<span
			className="woopayments-settings-disable-modal__method-icon woopayments-settings-disable-modal__method-icon--fallback"
			aria-hidden="true"
		>
			{ method.label.charAt( 0 ).toUpperCase() }
		</span>
	);
};

export const WooPaymentsDisableConfirmationModal = ( {
	affectedMethods,
	onClose,
	onConfirm,
}: {
	affectedMethods: WooPaymentsAffectedPaymentMethod[];
	onClose: () => void;
	onConfirm: () => void;
} ) => {
	return (
		<Modal
			title={ sprintf(
				/* translators: %s: Payment provider name. */
				__( 'Disable %s', 'woocommerce' ),
				PROVIDER_NAME
			) }
			className="woopayments-settings-disable-modal"
			onRequestClose={ onClose }
		>
			<p>
				{ sprintf(
					/* translators: %s: Payment provider name. */
					__(
						'%s is currently powering multiple popular payment methods on your store. Without it, they will no longer be available to your customers, which may influence sales.',
						'woocommerce'
					),
					PROVIDER_NAME
				) }
			</p>
			<p>
				{ sprintf(
					/* translators: %s: Payment provider name. */
					__( 'Payment methods that need %s:', 'woocommerce' ),
					PROVIDER_NAME
				) }
			</p>
			{ affectedMethods.length > 0 && (
				<ul className="woopayments-settings-disable-modal__payment-methods-list">
					{ affectedMethods.map( ( method ) => (
						<li key={ method.id }>
							<AffectedPaymentMethodIcon method={ method } />
							<span>{ method.label }</span>
						</li>
					) ) }
				</ul>
			) }
			<p className="woopayments-settings-disable-modal__help">
				<strong>{ __( 'Need help?', 'woocommerce' ) }</strong>{ ' ' }
				{ Children.toArray(
					createInterpolateElement(
						sprintf(
							/* translators: %s: Payment provider name. */
							__(
								'Learn more about <wooPaymentsLink>%s</wooPaymentsLink> or <supportLink>contact WooCommerce Support</supportLink>.',
								'woocommerce'
							),
							PROVIDER_NAME
						),
						{
							wooPaymentsLink: (
								<ExternalLink href={ WOOPAYMENTS_DOC_URL }>
									<></>
								</ExternalLink>
							),
							supportLink: (
								<ExternalLink href={ WOOCOMMERCE_SUPPORT_URL }>
									<></>
								</ExternalLink>
							),
						}
					)
				) }
			</p>
			<div className="woopayments-settings-modal__actions">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button variant="primary" isDestructive onClick={ onConfirm }>
					{ __( 'Disable', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
