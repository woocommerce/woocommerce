/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';
import { SendMagicLinkStates } from '../components';
import { MobileAppLoginStepper } from '../components/MobileAppLoginStepper';
import type { QRLoginConsumedSnapshot } from '../components/QRDirectLoginCode';

interface MobileAppLoginStepperPageProps {
	appInstalledClicked: boolean;
	isJetpackPluginInstalled: boolean;
	wordpressAccountEmailAddress: string | undefined;
	completeInstallationHandler: () => void;
	sendMagicLinkHandler: () => void;
	sendMagicLinkStatus: SendMagicLinkStates;
}

export const MobileAppLoginStepperPage = ( {
	appInstalledClicked,
	isJetpackPluginInstalled,
	wordpressAccountEmailAddress,
	completeInstallationHandler,
	sendMagicLinkHandler,
	sendMagicLinkStatus,
}: MobileAppLoginStepperPageProps ) => {
	// Captured the moment the QR component reports a successful exchange.
	// `signInResult` doubles as the trigger for advancing to step 3 — the
	// stepper renders the new success step iff this is non-null.
	const [ signInResult, setSignInResult ] =
		useState< QRLoginConsumedSnapshot | null >( null );

	const handleSignedIn = useCallback(
		( snapshot: QRLoginConsumedSnapshot ) => {
			// Only set on the first transition. The QR component's effect
			// fires on every render while in the consumed state; ignoring
			// subsequent calls keeps the success step from re-rendering with
			// the same data.
			setSignInResult( ( prev ) => prev ?? snapshot );
		},
		[]
	);

	let step: 'first' | 'second' | 'third';
	if ( signInResult ) {
		step = 'third';
	} else if ( appInstalledClicked ) {
		step = 'second';
	} else {
		step = 'first';
	}

	return (
		<ModalContentLayoutWithTitle>
			<div className="modal-subheader">
				<h3>
					{ __(
						'Run your store from anywhere with the Woo mobile app.',
						'woocommerce'
					) }
				</h3>
			</div>
			<MobileAppLoginStepper
				step={ step }
				isJetpackPluginInstalled={ isJetpackPluginInstalled }
				wordpressAccountEmailAddress={ wordpressAccountEmailAddress }
				signInResult={ signInResult }
				completeInstallationStepHandler={ completeInstallationHandler }
				sendMagicLinkHandler={ sendMagicLinkHandler }
				sendMagicLinkStatus={ sendMagicLinkStatus }
				onSignedIn={ handleSignedIn }
			/>
		</ModalContentLayoutWithTitle>
	);
};
