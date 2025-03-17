/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Flex, FlexItem, Modal } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent, recordEventOnce } from '../../events';

export function EditTemplateModal( { close } ) {
	recordEventOnce( 'edit_template_modal_opened' );

	const { onNavigateToEntityRecord, template } = useSelect( ( sel ) => {
		const { getEditorSettings } = sel( editorStore );
		const editorSettings = getEditorSettings();
		return {
			onNavigateToEntityRecord:
				// @ts-expect-error onNavigateToEntityRecord type is not defined
				editorSettings.onNavigateToEntityRecord,
			template: sel( storeName ).getCurrentTemplate(),
		};
	}, [] );

	return (
		<Modal size="medium" onRequestClose={ close } __experimentalHideHeader>
			<p>
				{ __(
					'Note that the same template can be used by multiple emails, so any changes made here may affect other emails on the site. To switch back to editing the page content click the ‘Back’ button in the toolbar.',
					'woocommerce'
				) }
			</p>
			<Flex justify={ 'end' }>
				<FlexItem>
					<Button
						variant="tertiary"
						onClick={ () => {
							recordEvent(
								'edit_template_modal_cancel_button_clicked'
							);
							close();
						} }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
				</FlexItem>
				<FlexItem>
					<Button
						variant="primary"
						onClick={ () => {
							recordEvent(
								'edit_template_modal_continue_button_clicked',
								{ templateId: template.id }
							);
							onNavigateToEntityRecord( {
								postId: template.id,
								postType: 'wp_template',
							} );
						} }
						disabled={ ! template.id }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</FlexItem>
			</Flex>
		</Modal>
	);
}
