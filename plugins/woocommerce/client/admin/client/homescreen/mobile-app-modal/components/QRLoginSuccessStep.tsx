/**
 * External dependencies
 */
import React, { useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { QRLoginDeviceInfo } from './useQRLoginToken';
import { useRevokeQRLoginAccess } from './useRevokeQRLoginAccess';
import {
	buildQRLoginDeviceHeadline,
	buildQRLoginDeviceSubline,
} from './qrLoginDeviceCopy';

type QRLoginSuccessStepProps = {
	deviceInfo: QRLoginDeviceInfo | null;
	apUuid: string | null;
};

/**
 * Step 3 of the modal flow — shown after the mobile app exchanges the QR
 * token for an Application Password. Mirrors the visual hierarchy of step 1
 * and step 2 (large heading + supporting copy + primary CTA) so the third
 * step feels like a natural progression rather than a side panel.
 *
 * The "Revoke access" CTA opens a confirmation modal before issuing the
 * DELETE call — a stray click should not silently sign the merchant out of
 * their own phone.
 */
export const QRLoginSuccessStep = ( {
	deviceInfo,
	apUuid,
}: QRLoginSuccessStepProps ) => {
	const [ isConfirmingRevoke, setIsConfirmingRevoke ] =
		useState< boolean >( false );
	const { revoke, isRevoking, isRevoked, errorMessage } =
		useRevokeQRLoginAccess();

	const headline = buildQRLoginDeviceHeadline( deviceInfo );
	const subline = buildQRLoginDeviceSubline( deviceInfo );

	const openConfirmDialog = () => {
		recordEvent( 'mobile_app_qr_direct_login_revoke_intent' );
		setIsConfirmingRevoke( true );
	};

	const closeConfirmDialog = () => {
		if ( isRevoking ) {
			return;
		}
		setIsConfirmingRevoke( false );
	};

	const confirmRevoke = async () => {
		if ( ! apUuid ) {
			return;
		}
		recordEvent( 'mobile_app_qr_direct_login_revoke_attempt' );
		await revoke( apUuid );
	};

	if ( isRevoked ) {
		return (
			<div
				className="qr-login-success-step qr-login-success-step--revoked"
				role="status"
				aria-live="polite"
			>
				<h2 className="qr-login-success-step__heading">
					{ __( 'Access revoked', 'woocommerce' ) }
				</h2>
				<p className="qr-login-success-step__description">
					{ __(
						'The mobile app will be signed out the next time it makes a request.',
						'woocommerce'
					) }
				</p>
			</div>
		);
	}

	return (
		<>
			<div
				className="qr-login-success-step"
				role="status"
				aria-live="polite"
			>
				<h2 className="qr-login-success-step__heading">{ headline }</h2>

				{ subline && (
					<p className="qr-login-success-step__device">{ subline }</p>
				) }

				<p className="qr-login-success-step__challenge">
					{ __( "It wasn't you?", 'woocommerce' ) }
				</p>

				<Button
					variant="primary"
					className="qr-login-success-step__revoke-button"
					onClick={ openConfirmDialog }
					disabled={ ! apUuid }
				>
					{ __( 'Revoke access', 'woocommerce' ) }
				</Button>

				{ errorMessage && (
					<p className="qr-login-success-step__error" role="alert">
						{ errorMessage }
					</p>
				) }
			</div>

			{ isConfirmingRevoke && (
				<Modal
					title={ __( 'Revoke access?', 'woocommerce' ) }
					onRequestClose={ closeConfirmDialog }
					className="qr-login-success-step__confirm-modal"
					shouldCloseOnEsc={ ! isRevoking }
					shouldCloseOnClickOutside={ ! isRevoking }
				>
					<p>
						{ __(
							'The mobile app will be signed out the next time it tries to reach your store. You can sign in again any time by scanning a new QR code.',
							'woocommerce'
						) }
					</p>
					<div className="qr-login-success-step__confirm-actions">
						<Button
							variant="tertiary"
							onClick={ closeConfirmDialog }
							disabled={ isRevoking }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ confirmRevoke }
							isBusy={ isRevoking }
							disabled={ isRevoking }
							className="qr-login-success-step__confirm-revoke-button"
						>
							{ __( 'Revoke access', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</>
	);
};
