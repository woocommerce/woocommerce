/**
 * External dependencies
 */
import React from 'react';
import { useLocation } from 'react-router-dom';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Modal from '~/settings-payments/onboarding/components/modal';
import WooPaymentsOnboarding from './components/onboarding';
import { WooPaymentsModalProps } from '~/settings-payments/onboarding/types';
import { OnboardingProvider } from './data/onboarding-context';
import '~/settings-payments/onboarding/style.scss';

/**
 * Modal component for WooPayments onboarding
 */
export default function WooPaymentsModal( {
	isOpen,
	setIsOpen,
}: WooPaymentsModalProps ): React.ReactNode {
	const location = useLocation();
	const history = getHistory();
	const wooPaymentsOnboardingPath = '/woopayments/onboarding';

	// Open modal when on an onboarding route
	React.useEffect( () => {
		if (
			location.pathname.startsWith( wooPaymentsOnboardingPath ) &&
			! isOpen
		) {
			setIsOpen( true );
		}
	}, [ location, isOpen, setIsOpen ] );

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
			<OnboardingProvider>
				<WooPaymentsOnboarding />
			</OnboardingProvider>
		</Modal>
	);
}
