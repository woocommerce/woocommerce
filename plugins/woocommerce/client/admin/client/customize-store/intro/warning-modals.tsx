/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ADMIN_URL } from '~/utils/admin-settings';
import { trackEvent } from '../tracking';

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
