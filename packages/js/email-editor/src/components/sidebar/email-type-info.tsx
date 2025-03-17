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
import { Icon, megaphone } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { EditTemplateModal } from './edit-template-modal';
import { SelectTemplateModal } from '../template-select';
import { recordEvent } from '../../events';

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
							<Icon icon={ megaphone } />
						</span>
						<div className="woocommerce-email-type-info-content">
							<h2>{ __( 'Newsletter', 'woocommerce' ) }</h2>
							<span>
								{ __(
									'Send or schedule a newsletter to connect with your subscribers.',
									'woocommerce'
								) }
							</span>
						</div>
					</PanelRow>
					{ template && (
						<PanelRow>
							<Flex justify={ 'start' }>
								<FlexItem className="editor-post-panel__row-label">
									{ __( 'Template', 'woocommerce' ) }
								</FlexItem>
								<FlexItem>
									<DropdownMenu
										icon={ null }
										text={ template?.title }
										toggleProps={ { variant: 'tertiary' } }
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
											</>
										) }
									</DropdownMenu>
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
