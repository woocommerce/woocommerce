/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { getQueryArg } from '@wordpress/url';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Modal from '~/settings-payments/onboarding/components/modal';
import WooPaymentsOnboarding from './components/onboarding';
import { WooPaymentsModalProps } from '~/settings-payments/onboarding/types';
import { OnboardingProvider } from './data/onboarding-context';

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

	// Open modal when on an onboarding route
	React.useEffect( () => {
		if (
			location.pathname.startsWith( wooPaymentsOnboardingPath ) &&
			! isOpen &&
			// Prevent the onboarding modal from reopening if the WPCom connection remains unestablished and the user has returned from Jetpack.
			! ( ! hasWPComConnection && isJetpackReturn )
		) {
			setIsOpen( true );
		}

		// Trigger a snackbar error notification when the user aborts the WPCom connection process.
		if ( ! hasWPComConnection && isJetpackReturn ) {
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
	] );

	// If the modal is open, without an onboarding route, add an onboarding route
	React.useEffect( () => {
		if (
			isOpen &&
			! location.pathname.startsWith( wooPaymentsOnboardingPath )
		) {
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
	}, [ isOpen, location.pathname, history ] );

	// Handle modal close by navigating away from onboarding routes
	const handleClose = () => {
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
			<OnboardingProvider closeModal={ handleClose }>
				<WooPaymentsOnboarding />
			</OnboardingProvider>
		</Modal>
	);
}
