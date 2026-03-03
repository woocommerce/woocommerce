/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';
import { getQueryArg } from '@wordpress/url';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Modal from '~/settings-payments/onboarding/components/modal';
import WooPaymentsOnboarding from './components/onboarding';
import { WooPaymentsModalProps } from '~/settings-payments/onboarding/types';
import {
	OnboardingProvider,
	useOnboardingContext,
} from './data/onboarding-context';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import { steps } from './steps';
import WooPaymentsOnboardingModalSnackbar from './components/snackbar';

const SnackbarWrapper = () => {
	const { snackbar } = useOnboardingContext();
	if ( ! snackbar.show ) return null;

	return (
		<WooPaymentsOnboardingModalSnackbar
			className={ snackbar.className || '' }
			duration={ snackbar.duration }
		>
			{ snackbar.message }
		</WooPaymentsOnboardingModalSnackbar>
	);
};

/**
 * Modal component for WooPayments onboarding
 */
export default function WooPaymentsModal( {
	isOpen,
	setIsOpen,
	providerData,
}: WooPaymentsModalProps ): React.ReactNode {
	const location = useLocation();
	const history = getHistory();
	const wooPaymentsOnboardingPath = '/woopayments/onboarding';
	const { createErrorNotice } = dispatch( 'core/notices' );
	const isJetpackReturn =
		getQueryArg( window.location.href, 'wpcom_connection_return' ) || false;
	const hasWPComConnection =
		providerData?.onboarding?.state?.wpcom_has_working_connection || false;
	const { sessionEntryPoint } = useOnboardingContext();

	// Handle modal and URL synchronization.
	React.useEffect( () => {
		const query = getQuery() as { path?: string };
		const isOnOnboardingPath =
			query.path && query.path.includes( wooPaymentsOnboardingPath );

		// Open modal when on an onboarding route
		if (
			isOnOnboardingPath &&
			! isOpen &&
			// Prevent the onboarding modal from reopening if the WPCom connection remains unestablished and the user has returned from Jetpack.
			! ( ! hasWPComConnection && isJetpackReturn )
		) {
			recordPaymentsOnboardingEvent(
				'woopayments_onboarding_modal_opened',
				{
					source: sessionEntryPoint,
				}
			);

			setIsOpen( true );
		}

		// If modal is open, but we're not on an onboarding route, navigate to onboarding.
		if ( isOpen && ! isOnOnboardingPath ) {
			const newPath = getNewPath(
				{ path: wooPaymentsOnboardingPath },
				wooPaymentsOnboardingPath,
				{
					page: 'wc-settings',
					tab: 'checkout',
				}
			);
			history.push( newPath );
		}

		// Trigger a snackbar error notification when the user aborts the WPCom connection process.
		if ( ! hasWPComConnection && isJetpackReturn ) {
			recordPaymentsOnboardingEvent(
				'woopayments_onboarding_wpcom_connection_cancelled',
				{
					source: sessionEntryPoint,
				}
			);

			createErrorNotice( __( 'Setup was cancelled!', 'woocommerce' ), {
				type: 'snackbar',
				explicitDismiss: false,
			} );
		}
	}, [
		location,
		isOpen,
		setIsOpen,
		isJetpackReturn,
		hasWPComConnection,
		createErrorNotice,
		history,
	] );

	// Handle modal close by navigating away from onboarding routes
	const handleClose = () => {
		recordPaymentsOnboardingEvent( 'woopayments_onboarding_modal_closed', {
			source: sessionEntryPoint,
		} );

		const newPath = getNewPath( {}, '/wp-admin/admin.php', {
			page: 'wc-settings',
			tab: 'checkout',
		} );
		history.push( newPath );
		setIsOpen( false );
	};

	if ( ! isOpen ) return null;

	return (
		<Modal setIsOpen={ handleClose }>
			<OnboardingProvider
				closeModal={ handleClose }
				onboardingSteps={ steps }
			>
				<WooPaymentsOnboarding />
				<SnackbarWrapper />
			</OnboardingProvider>
		</Modal>
	);
}
