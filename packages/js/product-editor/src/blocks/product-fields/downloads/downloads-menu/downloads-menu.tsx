/**
 * External dependencies
 */
import { Button, Dropdown, MenuGroup } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { DownloadsMenuProps } from './types';
import { UploadFilesMenuItem } from '../upload-files-menu-item';
import { MediaLibraryMenuItem } from '../media-library-menu-item';
import { InsertUrlMenuItem } from '../insert-url-menu-item';

export function DownloadsMenu( {
	allowedTypes,
	maxUploadFileSize,
	onUploadSuccess,
	onUploadError,
}: DownloadsMenuProps ) {
	return (
		<Dropdown
			position="bottom left"
			contentClassName="woocommerce-downloads-menu__menu-content"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					aria-expanded={ isOpen }
					icon={ isOpen ? chevronUp : chevronDown }
					variant="secondary"
					onClick={ onToggle }
					className="woocommerce-downloads-menu__toogle"
				>
					<span>{ __( 'Add new', 'woocommerce' ) }</span>
				</Button>
			) }
			renderContent={ ( { onClose } ) => (
				<div className="components-dropdown-menu__menu">
					<MenuGroup>
						<UploadFilesMenuItem
							allowedTypes={ allowedTypes }
							maxUploadFileSize={ maxUploadFileSize }
							onUploadSuccess={ ( files ) => {
								onUploadSuccess( files );
								onClose();
							} }
							onUploadError={ onUploadError }
						/>

						<MediaLibraryMenuItem
							allowedTypes={ allowedTypes }
							onUploadSuccess={ ( files ) => {
								onUploadSuccess( files );
								onClose();
							} }
						/>

						<InsertUrlMenuItem
							onUploadSuccess={ ( files ) => {
								onUploadSuccess( files );
								onClose();
							} }
							onUploadError={ onUploadError }
						/>
					</MenuGroup>
				</div>
			) }
		/>
	);
}
