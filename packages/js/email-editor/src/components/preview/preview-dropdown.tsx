/**
 * External dependencies
 */
import { MenuGroup, MenuItem, DropdownMenu } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, external, check, mobile, desktop } from '@wordpress/icons';
import { PostPreviewButton } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';
import { SendPreviewEmail } from './send-preview-email';

export function PreviewDropdown() {
	const previewDeviceType = useSelect(
		( select ) => select( storeName ).getDeviceType(),
		[]
	);

	const { changePreviewDeviceType, togglePreviewModal } =
		useDispatch( storeName );

	const changeDeviceType = ( newDeviceType: string ) => {
		void changePreviewDeviceType( newDeviceType );
	};

	const deviceIcons = {
		mobile,
		desktop,
	};

	return (
		<>
			<DropdownMenu
				className="woocommerce-preview-dropdown"
				label={ __( 'Preview', 'woocommerce' ) }
				icon={ deviceIcons[ previewDeviceType.toLowerCase() ] }
				onToggle={ ( isOpened ) =>
					recordEvent( 'header_preview_dropdown_clicked', {
						isOpened,
					} )
				}
			>
				{ ( { onClose } ) => (
					<>
						<MenuGroup>
							<MenuItem
								className="block-editor-post-preview__button-resize"
								onClick={ () => {
									changeDeviceType( 'Desktop' );
									recordEvent(
										'header_preview_dropdown_desktop_selected'
									);
								} }
								icon={
									previewDeviceType === 'Desktop' && check
								}
							>
								{ __( 'Desktop', 'woocommerce' ) }
							</MenuItem>
							<MenuItem
								className="block-editor-post-preview__button-resize"
								onClick={ () => {
									changeDeviceType( 'Mobile' );
									recordEvent(
										'header_preview_dropdown_mobile_selected'
									);
								} }
								icon={ previewDeviceType === 'Mobile' && check }
							>
								{ __( 'Mobile', 'woocommerce' ) }
							</MenuItem>
						</MenuGroup>
						<MenuGroup>
							<MenuItem
								className="block-editor-post-preview__button-resize"
								onClick={ () => {
									void togglePreviewModal( true );
									recordEvent(
										'header_preview_dropdown_send_test_email_selected'
									);
									onClose();
								} }
							>
								{ __( 'Send a test email', 'woocommerce' ) }
							</MenuItem>
						</MenuGroup>
						<MenuGroup>
							<div className="edit-post-header-preview__grouping-external">
								<PostPreviewButton
									role="menuitem"
									forceIsAutosaveable={ true }
									aria-label={ __(
										'Preview in new tab',
										'woocommerce'
									) }
									textContent={
										<>
											{ __(
												'Preview in new tab',
												'woocommerce'
											) }
											<Icon icon={ external } />
										</>
									}
									onPreview={ () => {
										recordEvent(
											'header_preview_dropdown_preview_in_new_tab_selected'
										);
										onClose();
									} }
								/>
							</div>
						</MenuGroup>
					</>
				) }
			</DropdownMenu>
			<SendPreviewEmail />
		</>
	);
}
