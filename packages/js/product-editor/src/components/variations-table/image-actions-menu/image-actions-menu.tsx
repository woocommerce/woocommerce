/**
 * External dependencies
 */
import { Dropdown, MenuGroup } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MediaItem } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { MediaLibraryMenuItem } from '../../menu-items/media-library-menu-item';
import { UploadFilesMenuItem } from '../../menu-items/upload-files-menu-item';
import { mapUploadImageToImage } from '../../../utils/map-upload-image-to-image';
import { VariationQuickUpdateMenuItem } from '../variation-actions-menus';
import type { ImageActionsMenuProps } from './types';

export function ImageActionsMenu( {
	selection,
	onChange,
	onDelete,
	...props
}: ImageActionsMenuProps ) {
	const [ isUploading, setIsUploading ] = useState( false );

	function uploadSuccessHandler( onClose: () => void ) {
		return function handleUploadSuccess( files: MediaItem[] ) {
			const image =
				( files.length && mapUploadImageToImage( files[ 0 ] ) ) ||
				undefined;
			const variation = {
				id: selection[ 0 ].id,
				image,
			};

			setIsUploading( false );

			onChange( [ variation ], false );
			onClose();
		};
	}

	function mediaLibraryMenuItemSelectHandler( onClose: () => void ) {
		return function handleMediaLibraryMenuItemSelect( media: never ) {
			const variation = {
				id: selection[ 0 ].id,
				image: mapUploadImageToImage( media ) || undefined,
			};
			onChange( [ variation ], false );
			onClose();
		};
	}

	return (
		<Dropdown
			{ ...props }
			// @ts-expect-error missing prop in types.
			popoverProps={ {
				placement: 'bottom-end',
			} }
			renderToggle={ ( toggleProps ) =>
				props.renderToggle( { ...toggleProps, isBusy: isUploading } )
			}
			contentClassName="woocommerce-image-actions-menu__menu-content"
			renderContent={ ( { onClose } ) => (
				<div className="components-dropdown-menu__menu">
					<MenuGroup>
						<UploadFilesMenuItem
							allowedTypes={ [ 'image' ] }
							multiple={ false }
							info={ __(
								'1000 pixels wide or larger',
								'woocommerce'
							) }
							onUploadProgress={ () => {
								setIsUploading( true );
								onClose();
							} }
							onUploadSuccess={ uploadSuccessHandler( onClose ) }
							onUploadError={ () => {
								setIsUploading( false );
								onClose();
							} }
						/>

						<MediaLibraryMenuItem
							allowedTypes={ [ 'image' ] }
							multiple={ false }
							value={ selection[ 0 ].id }
							onSelect={ mediaLibraryMenuItemSelectHandler(
								onClose
							) }
						/>
					</MenuGroup>

					<VariationQuickUpdateMenuItem.Slot
						group={ 'image-actions-menu' }
						onChange={ onChange }
						onClose={ onClose }
						selection={ selection }
						supportsMultipleSelection={ false }
					/>
				</div>
			) }
		/>
	);
}
