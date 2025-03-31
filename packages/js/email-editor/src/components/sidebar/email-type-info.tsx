/**
 * External dependencies
 */
import {
	Panel,
	PanelBody,
	PanelRow,
	Flex,
	FlexItem,
	DropdownMenu,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, postContent } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { EditTemplateModal } from './edit-template-modal';
import { SelectTemplateModal } from '../template-select';
import { recordEvent } from '../../events';
import { usePreviewTemplates } from '../../hooks';

const TypeInfoIcon = applyFilters(
	'woocommerce_email_editor_sidebar_email_type_info_icon',
	() => <Icon icon={ postContent } />
) as () => JSX.Element;

const TypeInfoContent = applyFilters(
	'woocommerce_email_editor_sidebar_email_type_info_content',
	() => (
		<>
			<h2>{ __( 'Email content', 'woocommerce' ) }</h2>
			<span>
				{ __(
					'This block represents the main content of your email, such as the invoice or order details. When the email is sent, it will be replaced with the actual email content.',
					'woocommerce'
				) }
			</span>
		</>
	)
) as () => JSX.Element;

export function EmailTypeInfo() {
	const { template, currentEmailContent, canUpdateTemplates } = useSelect(
		( select ) => {
			return {
				template: select( storeName ).getCurrentTemplate(),
				currentEmailContent:
					select( storeName ).getEditedEmailContent(),
				canUpdateTemplates: select( storeName ).canUserEditTemplates(),
			};
		},
		[]
	);
	const [ templates ] = usePreviewTemplates( 'swap' );

	const [ isEditTemplateModalOpen, setEditTemplateModalOpen ] =
		useState( false );
	const [ isSelectTemplateModalOpen, setSelectTemplateModalOpen ] =
		useState( false );

	return (
		<>
			<Panel className="woocommerce-email-sidebar-email-type-info">
				<PanelBody>
					<PanelRow>
						<span className="woocommerce-email-type-info-icon">
							<TypeInfoIcon />
						</span>
						<div className="woocommerce-email-type-info-content">
							<TypeInfoContent />
						</div>
					</PanelRow>
					{ template && (
						<PanelRow>
							<Flex justify={ 'start' }>
								<FlexItem className="editor-post-panel__row-label">
									{ __( 'Template', 'woocommerce' ) }
								</FlexItem>
								<FlexItem>
									{ ! (
										templates?.length > 1 ||
										canUpdateTemplates
									) && <b>{ template?.title }</b> }
									{ ( templates?.length > 1 ||
										canUpdateTemplates ) && (
										<DropdownMenu
											icon={ null }
											text={ template?.title }
											toggleProps={ {
												variant: 'tertiary',
											} }
											label={ __(
												'Template actions',
												'woocommerce'
											) }
											onToggle={ ( isOpen ) =>
												recordEvent(
													'sidebar_template_actions_clicked',
													{
														currentTemplate:
															template?.title,
														isOpen,
													}
												)
											}
										>
											{ ( { onClose } ) => (
												<>
													{ canUpdateTemplates && (
														<MenuItem
															onClick={ () => {
																recordEvent(
																	'sidebar_template_actions_edit_template_clicked'
																);
																setEditTemplateModalOpen(
																	true
																);
																onClose();
															} }
														>
															{ __(
																'Edit template',
																'woocommerce'
															) }
														</MenuItem>
													) }

													{ templates?.length > 1 && (
														<MenuItem
															onClick={ () => {
																recordEvent(
																	'sidebar_template_actions_swap_template_clicked'
																);
																setSelectTemplateModalOpen(
																	true
																);
																onClose();
															} }
														>
															{ __(
																'Swap template',
																'woocommerce'
															) }
														</MenuItem>
													) }
												</>
											) }
										</DropdownMenu>
									) }
								</FlexItem>
							</Flex>
						</PanelRow>
					) }
				</PanelBody>
			</Panel>
			{ isEditTemplateModalOpen && (
				<EditTemplateModal
					close={ () => {
						recordEvent( 'edit_template_modal_closed' );
						return setEditTemplateModalOpen( false );
					} }
				/>
			) }
			{ isSelectTemplateModalOpen && (
				<SelectTemplateModal
					onSelectCallback={ () =>
						setSelectTemplateModalOpen( false )
					}
					closeCallback={ () => setSelectTemplateModalOpen( false ) }
					previewContent={ currentEmailContent }
				/>
			) }
		</>
	);
}
