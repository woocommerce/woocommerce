/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { Sender } from 'xstate';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineEvents } from '..';
import { ADMIN_URL } from '~/utils/admin-settings';
import { getNewPath } from '@woocommerce/navigation';

export const ExitWarningModal = ( {
	setShowExitModal,
	classname = 'woocommerce-customize-store__design-change-warning-modal',
}: {
	setShowExitModal: ( arg0: boolean ) => void;
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	classname?: string;
} ) => {
	return (
		<Modal
			className={ classname }
			title={ __( 'Are you sure you want to exit?', 'woocommerce' ) }
			onRequestClose={ () => setShowExitModal( false ) }
			shouldCloseOnClickOutside={ false }
		>
			<p>
				{ __(
					"You'll lose any changes you've made to your store's design and will start the process again.",
					'woocommerce'
				) }
			</p>
			<div className="woocommerce-customize-store__design-change-warning-modal-footer">
				<Button href={ getNewPath( {}, '/', {} ) } variant="link">
					{ __( 'Exit and lose changes', 'woocommerce' ) }
				</Button>
				<Button
					onClick={ () => setShowExitModal( false ) }
					variant="primary"
				>
					{ __( 'Continue designing', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
