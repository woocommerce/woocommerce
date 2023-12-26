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

export const DesignChangeWarningModal = ( {
	setOpenDesignChangeWarningModal,
	sendEvent,
	classname = 'woocommerce-customize-store__design-change-warning-modal',
}: {
	setOpenDesignChangeWarningModal: ( arg0: boolean ) => void;
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	classname?: string;
} ) => {
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
						"The Store Designer will create a new store design for you, and you'll lose any changes you've made to your active theme. If you'd prefer to continue editing your theme, you can do so via the <EditorLink>Editor</EditorLink>.",
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

export const StartNewDesignWarningModal = ( {
	setOpenDesignChangeWarningModal,
	sendEvent,
	classname = 'woocommerce-customize-store__design-change-warning-modal',
}: {
	setOpenDesignChangeWarningModal: ( arg0: boolean ) => void;
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	classname?: string;
} ) => {
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
						"The Store Designer will create a new store design for you, and you'll lose any changes you've made to your active theme. If you'd prefer to continue editing your theme, you can do so via the <EditorLink>Editor</EditorLink>.",
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

export const StartOverWarningModal = ( {
	setOpenDesignChangeWarningModal,
	sendEvent,
	classname = 'woocommerce-customize-store__design-change-warning-modal',
}: {
	setOpenDesignChangeWarningModal: ( arg0: boolean ) => void;
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	classname?: string;
} ) => {
	return (
		<Modal
			className={ classname }
			title={ __(
				'Are you sure you want to start over?',
				'woocommerce'
			) }
			onRequestClose={ () => setOpenDesignChangeWarningModal( false ) }
			shouldCloseOnClickOutside={ false }
		>
			<p>
				{ createInterpolateElement(
					__(
						"You'll be asked to provide your business info again, and will lose your existing AI design. If you want to customize your existing design, you can do so via the <EditorLink>Editor</EditorLink>.",
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
					onClick={ () => {
						sendEvent( { type: 'DESIGN_WITH_AI' } );
						recordEvent(
							'customize_your_store_intro_start_again_click'
						);
					} }
					variant="primary"
				>
					{ __( 'Start again', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
