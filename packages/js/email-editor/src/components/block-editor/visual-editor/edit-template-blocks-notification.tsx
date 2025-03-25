/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import {
	__experimentalConfirmDialog as ConfirmDialog,
	Button,
	Modal,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { storeName } from '../../../store';
import { recordEvent, recordEventOnce } from '../../../events';

/**
 * Component that:
 *
 * - Displays a 'Edit your template to edit this block' dialog when the user
 *   is focusing on editing email content and double clicks on a disabled
 *   template block.
 *
 *   @see https://github.com/WordPress/gutenberg/blob/c754c783a9004db678fcfebd9a21a22820f2115c/packages/editor/src/components/visual-editor/edit-template-blocks-notification.js
 *
 * @param {Object}                                 props
 * @param {import('react').RefObject<HTMLElement>} props.contentRef Ref to the block
 *                                                                  editor iframe canvas.
 */
export default function EditTemplateBlocksNotification( { contentRef } ) {
	const { onNavigateToEntityRecord, templateId, canUpdateTemplates } =
		useSelect( ( select ) => {
			// @ts-expect-error getCurrentTemplateId is missing in types.
			const { getEditorSettings, getCurrentTemplateId } =
				select( editorStore );

			return {
				// onNavigateToEntityRecord is missing in EditorSettings.
				// prettier-ignore
				onNavigateToEntityRecord: // @ts-expect-error onNavigateToEntityRecord is not typed on EditorSettings.
				getEditorSettings().onNavigateToEntityRecord,
				templateId: getCurrentTemplateId(),
				canUpdateTemplates: select( storeName ).canUserEditTemplates(),
			};
		}, [] );

	const [ isDialogOpen, setIsDialogOpen ] = useState<
		'' | 'confirm' | 'info'
	>( '' );

	useEffect( () => {
		const handleDblClick = ( event ) => {
			if ( ! event.target.classList.contains( 'is-root-container' ) ) {
				return;
			}
			setIsDialogOpen( canUpdateTemplates ? 'confirm' : 'info' );
			recordEventOnce( 'edit_template_blocks_notification_opened' );
		};

		const canvas = contentRef.current;
		canvas?.addEventListener( 'dblclick', handleDblClick );
		return () => {
			canvas?.removeEventListener( 'dblclick', handleDblClick );
		};
	}, [ contentRef, canUpdateTemplates ] );

	return (
		<>
			{ isDialogOpen === 'info' && (
				<Modal
					onRequestClose={ () => {
						setIsDialogOpen( '' );
						recordEvent(
							'edit_template_blocks_notification_none_admin_role_notice_closed'
						);
					} }
					__experimentalHideHeader
				>
					<p>
						{ __(
							'You are attempting to edit a part of the template. Only site admins can edit email templates.',
							'woocommerce'
						) }
					</p>

					<div className="woocommerce-send-preview-modal-footer">
						<Button
							variant="tertiary"
							onClick={ () => {
								setIsDialogOpen( '' );
								recordEvent(
									'edit_template_blocks_notification_none_admin_role_notice_closed'
								);
							} }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }

			<ConfirmDialog
				isOpen={ isDialogOpen === 'confirm' }
				confirmButtonText={ __( 'Edit template', 'woocommerce' ) }
				onConfirm={ () => {
					setIsDialogOpen( '' );
					onNavigateToEntityRecord( {
						postId: templateId,
						postType: 'wp_template',
					} );
					recordEvent(
						'edit_template_blocks_notification_edit_template_button_clicked',
						{ templateId }
					);
				} }
				onCancel={ () => {
					setIsDialogOpen( '' );
					recordEvent(
						'edit_template_blocks_notification_cancel_button_clicked',
						{ templateId }
					);
				} }
				size="medium"
			>
				{ __(
					'The block you’ve selected is part of a template that might be used in other emails. Are you sure you want to edit the template?',
					'woocommerce'
				) }
			</ConfirmDialog>
		</>
	);
}
