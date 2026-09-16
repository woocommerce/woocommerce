/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { OnboardingModalProps } from '../../types';
import './style.scss';

export default function OnboardingModal( {
	setIsOpen,
	children,
}: OnboardingModalProps ) {
	return (
		<Modal
			className="settings-payments-onboarding-modal"
			isFullScreen
			__experimentalHideHeader
			onRequestClose={ () => setIsOpen( false ) }
			shouldCloseOnClickOutside={ false }
		>
			{ children }
		</Modal>
	);
}
