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
import { trackEvent } from '../tracking';
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
						trackEvent(
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

export const ThemeSwitchWarningModal = ( {
	isNoAiFlow = true,
	setIsModalOpen,
	redirectToCYSFlow,
}: {
	isNoAiFlow?: boolean;
	setIsModalOpen: ( arg0: boolean ) => void;
	redirectToCYSFlow: () => void;
} ) => {
	return (
		<Modal
			className={
				'woocommerce-customize-store__theme-switch-warning-modal'
			}
			title={ __(
				'Are you sure you want to design a new theme?',
				'woocommerce'
			) }
			onRequestClose={ () => setIsModalOpen( false ) }
			shouldCloseOnClickOutside={ false }
		>
			<p>
				{ isNoAiFlow
					? __(
							'Your active theme will be changed and you could lose any changes you’ve made to it.',
							'woocommerce'
					  )
					: createInterpolateElement(
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
			<div className="woocommerce-customize-store__theme-switch-warning-modal-footer">
				<Button
					onClick={ () => {
						setIsModalOpen( false );
					} }
					variant="link"
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					onClick={ () => {
						setIsModalOpen( false );
						trackEvent(
							'customize_your_store_agree_to_theme_switch_click'
						);
						redirectToCYSFlow();
					} }
					variant="primary"
				>
					{ __( 'Design a new theme', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
