/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { Sender } from 'xstate';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineEvents } from '..';
import { ADMIN_URL } from '~/utils/admin-settings';

export const DesignChangeWarningModal = ( {
	isOpen = false,
	setOpenDesignChangeWarningModal,
	sendEvent,
	classname = 'woocommerce-customize-store__design-change-warning-modal',
}: {
	isOpen?: boolean;
	setOpenDesignChangeWarningModal: ( arg0: boolean ) => void;
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	classname?: string;
} ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className={ classname }
			title={ __(
				'Are you sure you want to start a new design?',
				'woocommerce'
			) }
			onRequestClose={ () => setOpenDesignChangeWarningModal( false ) }
			shouldCloseOnClickOutside={ false }
		>
			<p>
				{ createInterpolateElement(
					__(
						"The [AI designer*] will create a new store design for you, and you'll lose any changes you've made to your active theme. If you'd prefer to continue editing your theme, you can do so via the <EditorLink>Editor</EditorLink>.",
						'woocommerce'
					),
					{
						EditorLink: (
							<Link
								onClick={ () => {
									window.open(
										`${ ADMIN_URL }site-editor.php`,
										'_blank'
									);
									return false;
								} }
								href=""
							/>
						),
					}
				) }
			</p>
			<div className="woocommerce-customize-store__design-change-warning-modal-footer">
				<Button
					onClick={ () => setOpenDesignChangeWarningModal( false ) }
					variant="link"
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					onClick={ () => sendEvent( { type: 'DESIGN_WITH_AI' } ) }
					variant="primary"
				>
					{ __( 'Design with AI', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
