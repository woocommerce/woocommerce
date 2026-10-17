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
import { useRevokeQRLoginAccess } from './useRevokeQRLoginAccess';
import type { QRLoginDeviceInfo } from './useQRLoginToken';
import { buildQRLoginDeviceLine } from './qrLoginDeviceCopy';

type QRLoginSuccessStepProps = {
	apUuid: string | null;
	deviceInfo: QRLoginDeviceInfo | null;
};

/**
 * Step 3 of the modal flow — shown after the mobile app exchanges the QR
 * token for an Application Password.
 *
 * Shows the device that signed in on a single line (device details + app
 * version), mirroring the standalone flow's `QRLoginConsumedPanel`. Alongside
 * it sits the "It wasn't you? Revoke access" pair, which sits on one row to
 * make better use of the vertical space the stepper already consumes.
 *
 * The "Revoke access" CTA opens a confirmation modal before issuing the
 * DELETE call — a stray click should not silently sign the merchant out of
 * their own phone.
 */
export const QRLoginSuccessStep = ( {
	apUuid,
	deviceInfo,
}: QRLoginSuccessStepProps ) => {
	const deviceDetails = buildQRLoginDeviceLine( deviceInfo );
	const [ isConfirmingRevoke, setIsConfirmingRevoke ] =
		useState< boolean >( false );
	const { revoke, isRevoking, isRevoked, errorMessage } =
		useRevokeQRLoginAccess();

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
				{ deviceDetails && (
					<p className="qr-login-success-step__device-details">
						{ deviceDetails }
					</p>
				) }

				<div className="qr-login-success-step__revoke-row">
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
				</div>

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
